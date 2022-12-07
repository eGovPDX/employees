/**
 * @file Global helper JS for the theme.
 */

// Import site-wide libraries.
import './_bootstrap.js';
import './light.js';
import './fontawesome.js';
import './back-to-top.js';

// Import components
import '../components/alerts/_alerts';
import '../components/notification/_notification';
import '../components/nav/_nav';

(function ($, Drupal) {

  // 'use strict';
  // Drupal.behaviors.notificatin_handler = {
  //   /**
  //    * @param {HTMLElement} context
  //    * @param settings
  //    */
    // attach(context, settings) {
    //   let closeButton = document.getElementsByClassName('westy-notification__close')
    //   let alertElement = document.getElementsByClassName('westy-notification')
    //   closeButton[0].addEventListener('click', (event) => {
    //     event.preventDefault()
    //     alertElement[0].classList.remove('westy-notification--dismissible')
    //   })
    // }
  // };

})(jQuery, Drupal);
