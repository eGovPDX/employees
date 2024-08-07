<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';
$settings['container_yamls'][] = __DIR__ . '/monolog.services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

// Set environment variable for config_split module
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  // Pantheon environments are 'live', 'test', 'dev', and '[multidev name]'
  $env = $_ENV['PANTHEON_ENVIRONMENT'];
}
else {
  // Default to lando if no Pantheon Environment is set.
  $env = 'lando';
  $_ENV['PANTHEON_ENVIRONMENT'] = 'lando';
}

// Set the default value to local URL before setting it for each environment
$primary_domain = 'employees.lndo.site';

if (isset($_ENV['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
  // Redirect to https://$primary_domain in the Live environment
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'employees.portland.gov';
    $config['environment_indicator.indicator']['bg_color'] = '#0f0f0f';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Production';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'test') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'test-employees.pantheonsite.io';
    $config['environment_indicator.indicator']['bg_color'] = '#ffb81c';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Test';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'dev') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'dev-employees.pantheonsite.io';
    $config['environment_indicator.indicator']['bg_color'] = '#3455eb';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Dev';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'sandbox') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'sandbox.employees.portland.gov';
    $config['environment_indicator.indicator']['bg_color'] = '#3455eb';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Sandbox';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'lando') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'employees.lndo.site';
    $config['environment_indicator.indicator']['bg_color'] = '#046a38';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Local';
  }
  else {
    // Redirect to HTTPS on every Pantheon environment.
    $primary_domain = $_SERVER['HTTP_HOST'];
    $config['environment_indicator.indicator']['bg_color'] = '#3455eb';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Multidev';
  }

  if ($_ENV['PANTHEON_ENVIRONMENT'] !== 'lando' &&
      ($_SERVER['HTTP_HOST'] != $primary_domain
        || !isset($_SERVER['HTTP_USER_AGENT_HTTPS'])
        || $_SERVER['HTTP_USER_AGENT_HTTPS'] != 'ON' ) ) {

    # Name transaction "redirect" in New Relic for improved reporting (optional)
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://'. $primary_domain . $_SERVER['REQUEST_URI']);
    exit();
  }
  // Append $primary_domain to Drupal 8 Trusted Host settings array
  if (is_array($settings)) {
    $settings['trusted_host_patterns'][] = '^'. preg_quote($primary_domain) .'$';
  }
}

// Override the SP Entity ID value for each environment
$config['samlauth.authentication']['sp_entity_id'] = $primary_domain;

// Make files with 0 usages temporary so they can be deleted automatically
$config['file.settings']['make_unused_managed_files_temporary'] = TRUE;

// Opt out of certain checks on the Pantheon Dashboard Site Status page
// https://docs.pantheon.io/drupal-launch-check#can-i-opt-out-of-a-specific-recommendation
$config['site_audit']['opt_out']['CachePageExpire'] = TRUE;

// Enable/disable config_split configurations based on the current environment
$config['config_split.config_split.config_multidev']['status'] = FALSE;
$config['config_split.config_split.config_dev']['status'] = FALSE;
$config['config_split.config_split.config_test']['status'] = FALSE;
$config['config_split.config_split.config_prod']['status'] = FALSE;
$config['config_split.config_split.config_local']['status'] = FALSE;
switch ($env) {
  case 'live':
    $config['config_split.config_split.config_prod']['status'] = TRUE;
    break;
  case 'test':
    $config['config_split.config_split.config_test']['status'] = TRUE;
    break;
  case 'dev':
    $config['config_split.config_split.config_dev']['status'] = TRUE;
    break;
  case 'lando':
    $config['config_split.config_split.config_local']['status'] = TRUE;
    break;
  default:  // Everything else (i.e. various multidev environments)
    $config['config_split.config_split.config_multidev']['status'] = TRUE;
    break;
}


// Overwrite Google Tag Manager environment setting in 'live' production site.
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    $config['google_tag.container.employees_portland.gov']['advanced_settings']['gtm']['GTM-558BXL5']['environment_id'] = 'env-1';
    $config['google_tag.container.employees_portland.gov']['advanced_settings']['gtm']['GTM-558BXL5']['environment_token'] = 'ooMp5LjvXgMdbFJ9wE617g';
  }
}

// Automatically generated include for settings managed by ddev.
$ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}

// Set the Solr site hash to a permanent value instead of using the auto-generated hash
$settings['search_api_solr.site_hash'] = 'employees';

// Set the configuration sync directory
$settings['config_sync_directory'] = 'sites/default/config';


// Configure Redis
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] !== 'lando') {
  // Include the Redis services.yml file. Adjust the path if you installed to a contrib or other subdirectory.
  $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

  //phpredis is built into the Pantheon application container.
  $settings['redis.connection']['interface'] = 'PhpRedis';
  // These are dynamic variables handled by Pantheon.
  $settings['redis.connection']['host']      = $_ENV['CACHE_HOST'];
  $settings['redis.connection']['port']      = $_ENV['CACHE_PORT'];
  $settings['redis.connection']['password']  = $_ENV['CACHE_PASSWORD'];

  $settings['redis_compress_length'] = 100;
  $settings['redis_compress_level'] = 1;

  $settings['cache']['default'] = 'cache.backend.redis'; // Use Redis as the default cache.
  $settings['cache_prefix']['default'] = 'pantheon-redis';

  $settings['cache']['bins']['form'] = 'cache.backend.database'; // Use the database for forms
}
