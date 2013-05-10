#!/bin/bash

BASE_PATH=/var/www/magento2
HOST=localhost
HOST_ALIAS=magento2.localhost
MYSQL="mysql"
MYSQL_ADMIN_USER=root
MYSQL_ADMIN_PASS=
MYSQL_USER=mage2
MYSQL_PASS=foobar
MYSQL_DB=magento2
MYSQL_HOST=localhost
TIMEZONE=America/Los_Angeles
CURRENCY=USD
USE_REWRITES=yes
USE_SECURE=yes
USE_SECURE_ADMIN=yes
LOCALE=en_US
LOCAL_BRANCH=buildcom

ADMIN_FIRSTNAME=Store
ADMIN_LASTNAME=Owner
ADMIN_EMAIL=foo@example.com
ADMIN_USER=admin
ADMIN_PASS=fooBAR123
# Admin password min length is 7

ENCRYPTION_KEY=duhEncKey
SESSION_SAVE=db
ADMIN_FRONTNAME=backend

PHP=php
MAGE_REPO_URI=git://github.com/magento/magento2.git
MAGE_DATA_VERSION=1.6.1.0
MAGE_SAMPLE_DATA=N
ERROR_REPORTING=Y
PATCHES_PATH=${BASE_PATH}/patches
PHP_VERSION=`echo "<?php echo phpversion();" | ${PHP}`
PHP_MAJOR_VERSION=`echo ${PHP_VERSION} | awk -F. '{print $1}'`
PHP_MINOR_VERSION=`echo ${PHP_VERSION} | awk -F. '{print $2}'`

MYSQL="${MYSQL} -u${MYSQL_ADMIN_USER}"
if [ ! -z $MYSQL_ADMIN_PASS ]; then
    MYSQL="${MYSQL_CMD} -p${MYSQL_ADMIN_PASS}"
fi
