#!/usr/bin/env sh
./vendor/bin/phpunit --exclude-group separateProcess
./vendor/bin/phpunit --group separateProcess --process-isolation
