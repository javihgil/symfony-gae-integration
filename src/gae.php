<?php

/**
 * @return string
 */
function gae_get_version_suffix()
{
    $version = getenv('CURRENT_VERSION_ID');

    // CURRENT_VERSION_ID in PHP represents major and minor version
    if (1 === substr_count($version, '.')) {
        list($major, $minor) = explode('.', $version);

        return '-'.$major;
    }
}

/**
 * @return string
 */
function gae_get_bucket_name()
{
    return getenv('GCS_BUCKET_NAME');
}

/**
 * @return bool
 */
function gae_on_app_engine()
{
    return isset($_SERVER['SERVER_SOFTWARE']) && 0 === strpos($_SERVER['SERVER_SOFTWARE'], 'Google App Engine');
}

/**
 * @return bool
 */
function gae_on_dev_app_server()
{
    return isset($_SERVER['SERVER_SOFTWARE']) && 0 === strpos($_SERVER['SERVER_SOFTWARE'], 'Development/');
}
