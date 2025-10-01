const AWS = require('aws-sdk');
const md5 = require('md5');
const { default: axios } = require('axios');

const API_URL = process.env.API_URL;
const USER_NAME = process.env.LAMBDA_USERNAME;
const PASSWORD = process.env.LAMBDA_PASSWORD;
const BUCKET = process.env.BUCKET;
const DOMAIN = process.env.DOMAIN;
const USER_STORAGE = process.env.USER_STORAGE;
const REGION = process.env.REGION;

const s3 = new AWS.S3({
    apiVersion: '2006-03-01',
    region: REGION,
});

const s3LifeboxStorage = new AWS.S3({
    apiVersion: '2006-03-01',
    region: REGION,
    credentials: {
        accessKeyId: process.env.ACCESS_KEY_ID,
        secretAccessKey: process.env.SECRET_ACCESS_KEY
    }
});

const simpleParser = require('mailparser').simpleParser;

const api = axios.create({
    baseURL: API_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

exports.handler = async (event) => {
    console.log(JSON.stringify(event, null, 2));

    let emailParsed;
    const record = event['Records'][0];
    const request = {
        Bucket: record.s3.bucket.name,
        Key: record.s3.object.key,
    };

    try {
        const data = await s3.getObject(request).promise();
        emailParsed = await simpleParser(data.Body);
    } catch (e) {
        console.log('Parse Email Error: ', e.stack);
        return e;
    }
    
    if (emailParsed) {
        console.log('Logging In As Lifebox Lambda Administrator');

        const userEmails = getRecipients(emailParsed.to);
        console.log({ userEmails });

        const token = await login();
        if (!token) {
            console.log('No API Token Found');
            return;
        }

        console.log('API TOKEN: ', token);

        for (let userEmail of userEmails) {
            const { username } = getUserDetailByEmail(userEmail);
            console.log({ username });
            
            const lifeboxAPI = await getApi(token);
            const user = await lifeboxAPI.post('/users/account', {
                username
            }).then(rs => rs.data).catch(e => console.log(e));

            console.log('Get User Details: ', user);

            if (user) {
                for (let attachment of emailParsed.attachments) {
                    const fileName = attachment.filename;
                    const contentType = attachment.contentType;
                    const buffer = attachment.content;
                    const fileSize = attachment.size;

                    const fileExtension = fileName.split('.').pop();
                    const s3Filename = getUserFileName(fileName, user.id);
                    const fileReference = USER_STORAGE + '/' + user.id + '/' + s3Filename;

                    try {
                        console.log({
                            s3Filename,
                            fileReference,
                            user,
                            fileSize,
                            fileExtension,
                        });

                        const rs = await createUserFile(
                            lifeboxAPI,
                            user,
                            fileName,
                            fileSize,
                            fileExtension,
                            fileReference
                        );

                        console.log('DB Create File Response: ', rs);

                        const rs3 = await uploadFile(
                            buffer,
                            s3Filename,
                            contentType,
                            '/' + USER_STORAGE + '/' + user.id
                        );

                        console.log('S3 Upload File Response: ', rs3);
                    } catch (e) {
                        console.log('FILE UPLOAD / SAVING ERROR: ', e.stack);
                    }
                }
            } else {
				console.log('No Lifebox User Found');
            }
        }
    }

    return {
        error: 'No Email Processed',
        status: 422
    };
};

/**
 * Helper function to store files to Lifebox API (create and save records to database)
 * @param object api, user
 * @param string fileName, fileSize, fileExtension, fileReference
 * @return response
 */
 function createUserFile(
    api,
    user,
    fileName,
    fileSize,
    fileExtension,
    fileReference
) {
    const data = {
        parent_id: user.inbox_id,
        user_id: user.id,
        file_extension: fileExtension,
        file_name: fileName,
        file_type: 'file',
        file_size: fileSize,
        file_status: 'close',
        file_reference: fileReference
    };
	
    console.log('Creating File Instance To Lifebox Database: ', data);
    return api.post('/files/create-file', data);
}

/**
 * Helper function to place custom auth header for Lifebox API call
 * @param string token
 * @return obj
 */
function getApi(token) {
    api.defaults.headers['Authorization'] = 'Bearer ' + token;
    return api;
}

/**
 * Helper function to login a dedicated Lifebox user for API access
 * @return string || null
 */
async function login() {
    const data = await api.post('/login', {
        'email': USER_NAME,
        'password': PASSWORD
    }).then(rs => rs.data);

    console.log('ACCESS TOKEN RESPONSE: ', data);
    return data.accessToken || null;
}

/**
 * Helper function to generate a unique and random file name
 * @param string fileName, userId
 * @return string
 */
function getUserFileName(fileName, userId) {
    return md5([fileName, userId, Date.now(), Math.random()].join('-'));
}

/**
 * Helper function to store files to Lifebox S3 Bucket
 * @param string file, fileName, contentType, path
 * @return array
 */
function uploadFile(
    file, 
    fileName, 
    contentType, 
    path = '/default'
) {
    console.log('Uploading File To Lifebox Storage....');

    return new Promise((resolve, reject) => {
        const data = {
            Key: fileName,
            Body: file,
            Bucket: BUCKET + path,
            CacheControl: 'no-cache',
            ContentType: contentType
        };

        console.log('File Object To Be Passed On S3: ', data);

        s3LifeboxStorage.upload(
            data, 
            function (err, data) {
                if (err) {
                    console.log('Error Uploading Object: ', err);
                    return reject(err);
                } else {
                    return resolve({
                        name: fileName,
                        path: data.Location
                    });
                }
            }
        );
    });
}

/**
 * Helper function to get the email address of the email's recipient/s
 * @param mixed to 
 * @return array
 */
function getRecipients(to) {
    const emails = [];

    if (!to) return [];
    if (!Array.isArray(to)) to = [to];

    for (let recipient of to) {
        let recipientData = recipient.value || [];
        for (let datum of recipientData) {
            let email = datum.address || [];
            if (email && email.indexOf(DOMAIN) > -1) emails.push(email);
        }
    }

    return emails;
}

/**
 * Helper function to get the username used on the recipient's email address
 * @param string email 
 * @return obj
 */
function getUserDetailByEmail(email) {
    if (!email) return null;
    if (email.indexOf('@') == -1) return null;
    if (email.split('@').length != 2) return null;

    const [username] = email.split('@');
    return { username };
}