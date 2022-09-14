(function ($, Drupal) {
  'use strict';
  const COOKIE_PREFIX = 'Drupal.visitor.westy_notification_dismissed.';
  Drupal.behaviors.notificatin_handler = {
    /**
     * @param {HTMLElement} context
     * @param settings
     */
    attach(context, settings) {
      //set an alert cookie
      let alertElement = document.getElementsByClassName('westy-notification')

      // When close button is clicked remove the westy-notification class
      let closeButton = document.getElementsByClassName('westy-notification__close')
      closeButton[0].addEventListener('click', (event) => {
        event.preventDefault()
        alertElement[0].classList.remove('westy-notification--dismissible')
      })

      // Set cookie value after close
      // const nid = alertElement[0]['dataset']['nid']
      // const cookeChangedTimestamp = document.cookie(COOKIE_PREFIX + nid)
      // const lastChangedTimestamp = alertElement[0]['dataset']['changed']
      // const path = (drupalSettings && drupalSettings.path && drupalSettings.path.baseUrl) || '/';
      // alertElement.cookie(
      //   COOKIE_PREFIX + nid,
      //   lastChangedTimestamp,
      //   {
      //     path,
      //   }
      // )
    }
  };

})(jQuery, Drupal);