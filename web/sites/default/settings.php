<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

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

if (isset($_ENV['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
  // Redirect to https://$primary_domain in the Live environment
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'employees.portland.gov';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'test') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'employees-test.portland.gov';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'lando') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'employees.lndo.site';
  }
  else {
    // Redirect to HTTPS on every Pantheon environment.
    $primary_domain = $_SERVER['HTTP_HOST'];
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

// Set environment variable for config_split module
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  // Pantheon environments are 'live', 'test', 'dev', and '[multidev name]'
  $env = $_ENV['PANTHEON_ENVIRONMENT'];
}
else {
  // Treat local dev same as Pantheon 'dev'
  $env = 'dev';
}



// $databases['default']['default'] = array (
//   'database' => 'pantheon',
//   'username' => 'pantheon',
//   'password' => 'pantheon',
//   'prefix' => '',
//   'host' => 'database',
//   'port' => '3306',
//   'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
//   'driver' => 'mysql',
// );
// $config_directories['sync'] = 'sites/default/files/config_EIU_3aqFK8Mce5DfzNoGx8NhYGOiRv5Msjots_khrG8RcyFwWcIrmMWWh7qsmt3MRh9uCo0J0A/sync';
