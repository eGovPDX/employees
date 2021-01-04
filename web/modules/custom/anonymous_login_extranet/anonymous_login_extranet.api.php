<?php

/**
 * @file
 * Hooks related to the Anonymous login module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the list of included and excluded paths for redirection.
 *
 * Included paths are those that will redirect the user to the login page.
 *
 * Excluded paths are those that will not redirect the user.
 *
 * @param array $paths
 *   An array of paths, keyed with 'included' and 'excluded'.
 */
function hook_anonymous_login_extranet_paths_alter(array &$paths) {
  // Always include user test path.
  $paths['include'][] = '/test';

  // Never redirect on node paths.
  $paths['exclude'][] = '/node/*';
}

/**
 * Alters the list of IP address ranges.
 *
 * Matching IP addresses will not redirect the user to the login page.
 *
 * @param array $ip_ranges
 *   An array of IP address ranges, keyed by 'lower' and 'upper'.
 */
function hook_anonymous_login_extranet_ip_ranges_alter(array &$ip_ranges) {

}

/**
 * @} End of "addtogroup hooks".
 */
