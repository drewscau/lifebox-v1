## LAMBDA NODEJS SETUP

Run the following commands during Lifebox API setup

```
$ php artisan db:migrate
$ php artisan db:seed --class=LambdaAPISeeder
```

It creates a user account / instance that serves as the API accessor / grantee to Lambda function/s.

### Create A Lambda Function For LifeBox APIs

- Go To Lambda > Functions, then click Create Function button
- Fill in necessary information, and select existing execution role too
- Adjust memory to 1024 MB and set timeout to 1 minute
- Then go to Lifebox > lambda-api folder, select all content and archive them as ZIP file
- Back to Lambda function, upload the said ZIP file there, that will deploy the lambda-api code
- A Lambda function must be made for each Lifebox API (Dev, Staging, Prelive, Live), hence changes in ENV

### Update Lambda Credentials

Set Environment Variables To AWS Lambda > Functions > {Lifebox-Lambda Function Name} > Configurations

```
DOMAIN=lifebox.net.au
USER_STORAGE=userstorage
LAMBDA_PASSWORD=1iF3B0x-L4Md4(0@
LAMBDA_USERNAME=lambda-api@lifebox.net.au

API_URL={the same as consumed API's APP_URL}/api
BUCKET={the same as consumed API's AWS_BUCKET}
ACCESS_KEY_ID={the same as consumed API's AWS_ACCESS_KEY_ID}
SECRET_ACCESS_KEY={the same as consumed API's AWS_SECRET_ACCESS_KEY}
REGION={the same as consumed API's AWS_DEFAULT_REGION}
```

### Configure SES Email Receiving Rule Sets

- Go to Lifebox SES > Email Receiving > Rule Sets
- If not set yet, create a Rule Set, add one rule for each API 
- Then select an S3 action and point your SES to the dedicated S3 bucket based on the API used
