<?php

/**
 * @file
 * Install, update and uninstall functions for the anonymous_login_extranet module.
 */

/**
 * Changing the format for storing paths in the configuration.
 */
function anonymous_login_extranet_post_update_path_array() {
  $config = \Drupal::configFactory()->getEditable('anonymous_login_extranet.settings');
  $paths = array_filter(array_map('trim', explode(PHP_EOL, $config->get('paths') ?? '')));
  $config->set('paths', $paths)->save();
}
