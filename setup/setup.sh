#!/bin/sh

SETUP_PATH=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
. ${SETUP_PATH}/config.sh
current_path=`pwd`

Mage_Extract() {
    cd ${BASE_PATH}

    # Clone magento2
    if [ ! -d magento2 ]; then
        git clone ${MAGE_REPO_URI}
    fi

    # Create and switch to a local-only branch
    cd magento2
    if [ `git branch | grep -c "^* ${LOCAL_BRANCH}\$"` = 0 ]; then
        git checkout -b ${LOCAL_BRANCH}
    fi

    # Enable error reporting
    if [ ${ERROR_REPORTING} = Y ]; then
        if [ ! -f ${BASE_PATH}/magento/errors/local.xml ]; then
            cp ${BASE_PATH}/magento2/pub/errors/local.xml.sample ${BASE_PATH}/magento2/pub/errors/local.xml
        fi
    fi

    if [ ${MAGE_SAMPLE_DATA} = Y ]; then
        # Create source directory if needed
        mkdir -p ${BASE_PATH}/src

        cd ${BASE_PATH}/src
        # Download source
        if [ ! -f magento-sample-data-${MAGE_DATA_VERSION}.tar.gz ]; then
            wget http://www.magentocommerce.com/downloads/assets/${MAGE_DATA_VERSION}/magento-sample-data-${MAGE_DATA_VERSION}.tar.gz
        fi
        # Extract sample data if needed
        if [ ! -d magento-sample-data-${MAGE_DATA_VERSION} ]; then
            tar xzf magento-sample-data-${MAGE_DATA_VERSION}.tar.gz
        fi
        # Copy sample data to magento media folder
        cp -a magento-sample-data-${MAGE_DATA_VERSION}/media/* ${BASE_PATH}/magento2/pub/media/
    fi

    # Set permissions
    chmod -R a+rwX ${BASE_PATH}/magento2/pub/media ${BASE_PATH}/magento2/pub/static
    chmod a+rwX ${BASE_PATH}/magento2/var ${BASE_PATH}/magento2/var/.htaccess ${BASE_PATH}/magento2/app/etc

    cd ${current_path}
}

Mage_Patch() {
    # Check to see if we're on the local branch
    cd ${BASE_PATH}/magento2
    if [ `git branch | grep -c "^* ${LOCAL_BRANCH}\$"` = 1 ]; then
        Mage_Apply_Patch magento2_mysql_5_6_1.patch
    fi

    cd $current_path
}

Mage_Apply_Patch() {
    if [ `git shortlog | grep -c ${1}` -gt 0 ]; then
        echo "Patch ${1} already applied"
    else
        echo Applying patch $1
        git apply ${PATCHES_PATH}/${1}
        git commit -am $1
    fi
}

Mage_DB_Setup() {
    ${MYSQL} -e"CREATE DATABASE IF NOT EXISTS ${MYSQL_DB} DEFAULT CHARACTER SET utf8;"
    # Bring in sample data
    if [ ${MAGE_SAMPLE_DATA} = Y ]; then
        ${MYSQL} ${MYSQL_DB} < ${BASE_PATH}/src/magento-sample-data-${MAGE_DATA_VERSION}/magento_sample_data_for_${MAGE_DATA_VERSION}.sql
    fi
}

Mage_DB_User_Setup() {
    # Check for existing user
    if [ `${MYSQL} -e"SELECT user FROM mysql.user WHERE user='${MYSQL_USER}' AND host='${HOST}'" --xml | grep -c '<row>'` = 1 ]; then
        ${MYSQL} -e"DROP USER '${MYSQL_USER}'@'${HOST}';"
    fi
    ${MYSQL} -e"CREATE USER '${MYSQL_USER}'@'${HOST}' IDENTIFIED BY '${MYSQL_PASS}';"
    ${MYSQL} -e"GRANT ALL PRIVILEGES ON ${MYSQL_DB}.* TO '${MYSQL_USER}'@'${HOST}'; FLUSH PRIVILEGES;"
}

Mage_Install()
{
    # Note: don't indent below. My Mac wasn't able to parse the options when indented
    ${PHP} -f ${BASE_PATH}/magento2/dev/shell/install.php -- --license_agreement_accepted "yes" \
--locale ${LOCALE} --timezone ${TIMEZONE} --default_currency ${CURRENCY} \
--db_host ${MYSQL_HOST} --db_name ${MYSQL_DB} --db_user ${MYSQL_USER} --db_pass ${MYSQL_PASS} \
--url "http://${HOST_ALIAS}/" --skip_url_validation --use_rewrites "${USE_REWRITES}" \
--use_secure "${USE_SECURE}" --secure_base_url "https://${HOST_ALIAS}/" --use_secure_admin ${USE_SECURE_ADMIN} \
--admin_lastname "${ADMIN_LASTNAME}" --admin_firstname "${ADMIN_FIRSTNAME}" --admin_email "${ADMIN_EMAIL}" \
--admin_username "${ADMIN_USER}" --admin_password "${ADMIN_PASS}" \
--encryption_key "${ENCRYPTION_KEY}" \
--session_save "${SESSION_SAVE}" \
--enable_charts \
--admin_frontname "${ADMIN_FRONTNAME}"
}

Mage_Permissions() {
    chmod o-w ${BASE_PATH}/magento
    # Set permissions
    chmod -R a+rwX ${BASE_PATH}/magento/media
    chmod a+rwX ${BASE_PATH}/magento/var ${BASE_PATH}/magento/var/.htaccess ${BASE_PATH}/magento/app/etc
}

Mage_Clean() {
    # Check for existing database
    if [ `${MYSQL} -e"SHOW DATABASES" --xml | grep -c ">${MYSQL_DB}<"` = 1 ]; then
        ${MYSQL} -e"DROP DATABASE ${MYSQL_DB};"
    fi
    # Check for existing user
    if [ `${MYSQL} -e"SELECT user FROM mysql.user WHERE user='${MYSQL_USER}' AND host='${HOST}'" --xml | grep -c '<row>'` = 1 ]; then
        ${MYSQL} -e"DROP USER '${MYSQL_USER}'@'${HOST}';"
    fi

    # Use git to get back to normal
    if [ -d ${BASE_PATH}/magento2 ]; then
        cd ${BASE_PATH}/magento2
        git checkout master
        git branch -D ${LOCAL_BRANCH}
        rm -rf var/* app/etc/local.xml
        cd ${current_path}
    fi
}

Mage_Link_Modules() {
    # Packages
    cd ${BASE_PATH}/magento2/app/code
    for package in `ls -d ../../../packages/*`; do
        ln -s $package .
    done

    cd $current_path
}

case "$1" in
        doit)
            Mage_Extract
            Mage_Patch
            Mage_DB_Setup
            Mage_DB_User_Setup
            Mage_Install
            Mage_Link_Modules
            ;;
        extract)
            Mage_Extract
            ;;
        patch)
            Mage_Patch
            ;;
        dbsetup)
            Mage_DB_Setup
            Mage_DB_User_Setup
            ;;
        modules)
            Mage_Link_Modules
            ;;
        install)
            Mage_Install
            ;;
        permissions)
            Mage_Permissions
            ;;
        clean)
            Mage_Clean
            ;;
        *)
            echo $"Usage: $0 <doit|extract|dbsetup|modules|install|permissions|clean>"
            echo "  doit = Download, extract, patch, dbsetup and install"
            echo "  extract = Download and extract Magento and sample data"
            echo "  patch = Apply patches"
            echo "  dbsetup = Setup database and database user"
            echo "  modules = Link modules"
            echo "  install = Install using defaults"
            echo "  permissions = Set safe permissions"
            echo "  clean = Erase database and magento-1.x.x"
esac
