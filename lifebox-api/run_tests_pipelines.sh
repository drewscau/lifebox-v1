#!/usr/bin/env sh
./vendor/bin/phpunit -c phpunit-pipelines.xml --exclude-group separateProcess
