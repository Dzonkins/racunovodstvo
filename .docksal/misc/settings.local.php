<?php

/**
 * @file
 * Lagoon Drupal 8 example settings.local.php file.
 *
 * This file will not be included and is just an example file.
 * If you would like to use this file, copy it to the name 'settings.local.php' (this file will be exluded from Git)
 */

// Disable render caches, necessary for twig files to be reloaded on every page view.
//$settings['cache']['bins']['render'] = 'cache.backend.null';
//$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';


/**
 * @file
 * Local development override configuration feature.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/default/settings.local.php'. Then, go to the bottom of
 * 'sites/default/settings.php' and uncomment the commented lines that mention
 * 'settings.local.php'.
 *
 * If you are using a site name in the path, such as 'sites/example.com', copy
 * this file to 'sites/example.com/settings.local.php', and uncomment the lines
 * at the bottom of 'sites/example.com/settings.php'.
 */

/**
 * Assertions.
 *
 * The Drupal project primarily uses runtime assertions to enforce the
 * expectations of the API by failing when incorrect calls are made by code
 * under development.
 *
 * @see http://php.net/assert
 * @see https://www.drupal.org/node/2492225
 *
 * If you are using PHP 7.0 it is strongly recommended that you set
 * zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
 * or runtime) on development machines and to 0 in production.
 *
 * @see https://wiki.php.net/rfc/expectations
 */
//assert_options(ASSERT_ACTIVE, TRUE);
//\Drupal\Component\Assertion\Handle::register();

/**
 * Enable local development services.
 */
//$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Disable the render cache.
 *
 * Note: you should test with the render cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the render cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Only use this setting once the site has been installed.
 */
# $settings['cache']['bins']['render'] = 'cache.backend.null';

/**
 * Disable caching for migrations.
 *
 * Uncomment the code below to only store migrations in memory and not in the
 * database. This makes it easier to develop custom migrations.
 */
# $settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';

/**
 * Disable Internal Page Cache.
 *
 * Note: you should test with Internal Page Cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the page cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Only use this setting once the site has been installed.
 */
# $settings['cache']['bins']['page'] = 'cache.backend.null';

/**
 * Disable Dynamic Page Cache.
 *
 * Note: you should test with Dynamic Page Cache enabled, to ensure the correct
 * cacheability metadata is present (and hence the expected behavior). However,
 * in the early stages of development, you may want to disable it.
 */
# $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

/**
 * Allow test modules and themes to be installed.
 *
 * Drupal ignores test modules and themes by default for performance reasons.
 * During development it can be useful to install test extensions for debugging
 * purposes.
 */
# $settings['extension_discovery_scan_tests'] = TRUE;

/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
$settings['rebuild_access'] = FALSE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;


/**
 * Trusted host configuration.
 *
 * Drupal core can use the Symfony trusted host mechanism to prevent HTTP Host
 * header spoofing.
 *
 * To enable the trusted host mechanism, you enable your allowable hosts
 * in $settings['trusted_host_patterns']. This should be an array of regular
 * expression patterns, without delimiters, representing the hosts you would
 * like to allow.
 *
 * For example:
 *
 * @code
 * $settings['trusted_host_patterns'] = array(
 *   '^www\.example\.com$',
 * );
 * @endcode
 * will allow the site to only run from www.example.com.
 *
 * If you are running multisite, or if you are running your site from
 * different domain names (eg, you don't redirect http://www.example.com to
 * http://example.com), you should specify all of the host patterns that are
 * allowed by your site.
 *
 * For example:
 * @code
 * $settings['trusted_host_patterns'] = array(
 *   '^example\.com$',
 *   '^.+\.example\.com$',
 *   '^example\.org$',
 *   '^.+\.example\.org$',
 * );
 * @endcode
 * will allow the site to run off of all variants of example.com and
 * example.org, with all subdomains included.
 */
$settings['trusted_host_patterns'] = [
  '^drupal11.docksal.site$',
  '^drupal11.docksal$',
  '^drupal-11-boilerplate.docksal.site$',
  '^drupal-11-boilerplate.docksal$',
];

// Docksal DB connection settings.
$databases['default']['default'] = [
  'database' => 'default',
  'username' => 'user',
  'password' => 'user',
  'host' => 'db',
  'collation' => 'utf8mb4_general_ci',
  'driver' => 'mysql',
];

// Workaround for permission issues with NFS shares
$settings['file_chmod_directory'] = 0777;
$settings['file_chmod_file'] = 0666;

# File system settings.
$config['system.file']['path']['temporary'] = 'sites/default/files/tmp';
$settings['file_temp_path'] = 'sites/default/files/tmp';


// Reverse proxy configuration (Docksal vhost-proxy)
if (PHP_SAPI !== 'cli') {
  $settings['reverse_proxy'] = TRUE;
  $settings['reverse_proxy_addresses'] = [$_SERVER['REMOTE_ADDR']];
  // HTTPS behind reverse-proxy
  if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' && !empty($settings['reverse_proxy']) && in_array($_SERVER['REMOTE_ADDR'],
      $settings['reverse_proxy_addresses'])) {
    $_SERVER['HTTPS'] = 'on';
    // This is hardcoded because there is no header specifying the original port.
    $_SERVER['SERVER_PORT'] = 443;
  }
}

$settings['hash_salt'] = 'bUmc1PV9VzwAXban_VVRtSKKwOBp7QNugOCWzsUrRgyy7Zl7D2dJm-J8Wb5FWOUrDKH7zepC2A';

///**
// * Mail configuration for local mail.
// */
//$config['swiftmailer.transport']['transport'] = 'smtp';
//$config['swiftmailer.transport']['smtp_host'] = 'mail';
//$config['swiftmailer.transport']['smtp_port'] = '1025';
//$config['swiftmailer.transport']['smtp_encryption'] = 0;
//$config['swiftmailer.transport']['smtp_credential_provider'] = 'swiftmailer';
// Symfony mailer version.
$config['symfony_mailer.mailer_transport.smtp']['configuration']['port']="1025";
$config['symfony_mailer.mailer_transport.smtp']['configuration']['host']="mail";
$config['symfony_mailer.mailer_transport.smtp']['configuration']['user']="";
$config['symfony_mailer.mailer_transport.smtp']['configuration']['pass']="";
$config['symfony_mailer.mailer_transport.smtp']['configuration']['query']['query_peer'] = TRUE;

$config['file.settings']['make_unused_managed_files_temporary'] = TRUE;
ini_set('memory_limit', '2048M');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Zühlke Events
// Test
$settings['msd_api_endpoint'] = "https://groupit-crm-marketing-test-abstractlayer.azurewebsites.net/api";
$settings['msd_oauth_url'] = "https://login.windows.net/ccce7f5e-a35f-4bc3-8e63-b2215e7d14f9/oauth2/v2.0/token";
$settings['msd_oauth_grant_type'] = "client_credentials";
$settings['msd_oauth_scope'] = "d1117b19-9873-4a4d-9ff8-10e3203f10ec/.default";
$settings['msd_oauth_client_id'] = "d1117b19-9873-4a4d-9ff8-10e3203f10ec";
$settings['msd_oauth_client_secret'] = "zMo8Q~mPoZ~UR5VqQcqziHfr5_nxumM.Z4xrsc5m";

//// Live
//$settings['msd_api_endpoint'] = "https://groupit-crm-marketing-prod-abstractlayer.azurewebsites.net/api";
//$settings['msd_oauth_url'] = "https://login.windows.net/ccce7f5e-a35f-4bc3-8e63-b2215e7d14f9/oauth2/v2.0/token";
//$settings['msd_oauth_grant_type'] = "client_credentials";
//$settings['msd_oauth_scope'] = "f422c808-d93e-417b-b6ec-0dd19fe27249/.default";
//$settings['msd_oauth_client_id'] = "f422c808-d93e-417b-b6ec-0dd19fe27249";
//$settings['msd_oauth_client_secret'] = "oYA8Q~dqlEvDnJonVMUXHImCwA4YMc6N_ft3ycQ~";

$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/services.local.yml';

$settings['config_sync_directory'] = '../config/sync';

$settings['update_free_access'] = TRUE;
