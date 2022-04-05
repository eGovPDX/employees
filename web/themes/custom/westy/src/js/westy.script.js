/**
 * @file Global helper JS for the theme.
 */

// Import site-wide libraries.
import './_bootstrap.js';
import './light.js';
import './fontawesome.js';

// Import components
import '../components/alerts/_alerts';
import '../components/nav/_nav';

(function ($, Drupal) {

  'use strict';
  // // Enable Bootstrap Popover sitewide.
  // // Popovers are opt-in for performance reasons.
  // Drupal.behaviors.bsPopover = {
  //   attach: function (context) {
  //     $('[data-bs-toggle="popover"]').popover();
  //   }
  // };

  // // Enable Bootstrap Toast sitewide.
  // // Toasts are opt-in for performance reasons.
  // Drupal.behaviors.bsToast = {
  //   attach: function (context) {
  //     $('.toast').toast('show');
  //   }
  // };



})(jQuery, Drupal);
