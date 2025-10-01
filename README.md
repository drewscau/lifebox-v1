# README #

##  Lifebox API ##

### Documentation ###

https://simpleclickteam.postman.co/home

### What is this repository for? ###

* Quick summary
* Version
* [Learn Markdown](https://bitbucket.org/tutorials/markdowndemo)

### How do I get set up? ###

* Summary of set up
* Configuration
* Dependencies
* Database configuration
* How to run tests
* Deployment instructions

### Contribution guidelines ###

* Writing tests
* Code review
* Other guidelines

### Who do I talk to? ###

* Repo owner or admin
* Other community or team contact

### docker local-env setup
```
$ docker-compose up -d —build site
$ docker-compose run —rm composer install
$ mkdir mysql
$ docker-compose run —rm artisan migrate
$ docker-compose run --rm artisan key:generate
$ docker-compose run --rm artisan migrate --database=mysql_tradebox

before running migrate, check your .env it should match the db settings in docker-compose.yml. If you did not modify the compose file, it should look like this:
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=homestead
DB_USERNAME=homestead
DB_PASSWORD=secret

db settings for tradebox
TRADEBOX_DB_CONNECTION=mysql_tradebox
TRADEBOX_DB_HOST=mysql_tradebox
TRADEBOX_DB_PORT=3306
TRADEBOX_DB_DATABASE=tradebox
TRADEBOX_DB_USERNAME=tradebox
TRADEBOX_DB_PASSWORD=secret

test at localhost:8080/api/ping

If already setup, no need to build for next time
$ docker-compose up -d

shutdown
$ docker-compose down
```

### php-cs setup and how to run
[Link to php-cs on github](https://github.com/squizlabs/PHP_CodeSniffer)
```
# installation, if you recently ran `composer install` you probably have this installed already
$ composer require --dev "squizlabs/php_codesniffer=*"
$ docker-compose run -rm php artisan passport:install
# if using docker dev-env
$ docker-compose run --rm composer require --dev "squizlabs/php_codesniffer=*"

# running php-cs for all files in a directory
$ ./vendor/bin/phpcs app/Http/Controllers

# with docker
$ docker-compose exec php ./vendor/bin/phpcs app/Http/Controllers

# running php-cs for a specific file
$ ./vendor/bin/phpcs app/Http/Controllers/VerificationController.php

# with docker
$ docker-compose exec php ./vendor/bin/phpcs app/Http/Controllers/VerificationController.php
```


## php artisan serve with ngRok
[https://ngrok.com/](https://ngrok.com/)

Follow the installation guide on https://ngrok.com/download. Install ngrok in your  `PATH` so you can run it from any directory. This is to server https server while in development

- [Download the ngrok ZIP file](https://ngrok.com/)
- Unzip the ngrok.exe file
- Place the ngrok.exe in a folder of your choosing
- Make sure the folder is in your PATH environment variable

### Test Your Installation
To test that ngrok is installed properly, open a new command window (command prompt or PowerShell) and run the following:
```
ngrok version
```

It should print a string like "ngrok version 2.x.x". If you get something like "'ngrok' is not recognized" it probably means you don't have the folder containing ngrok.exe in your PATH environment variable. You may also need to open a new command window.

### Starting ngrok from the command line
```
# on 1st tab run this command, as this will open on http://127.0.0.1:8000
php artisan serve

# on 2nd tab run this command
ngrok http -host-header="localhost:[port]" [port]
```

## Setup Firebase Push Notification
setup on .env file the Server Key that is needed for FCM (Firebase Cloud Messaging)
```
FCM_PUSH_SERVER_KEY=AAAAv0f83No:APA91bH.......
```