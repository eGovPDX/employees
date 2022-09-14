(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.notificatin_handler = {
    /**
     * @param {HTMLElement} context
     * @param settings
     */
    attach(context, settings) {
      //set an alert cookie
      let alertElement = document.getElementsByClassName('westy-notification')
      console.log(alertElement)

      // When close button is clicked remove the westy-notification class
      let closeButton = document.getElementsByClassName('westy-notification__close')
      closeButton[0].addEventListener('click', (event) => {
        event.preventDefault()
        alertElement[0].classList.remove('westy-notification--dismissible')
      })
      const nid = alertElement[0]['dataset']['nid']
      const lastChangedTimestamp = alertElement[0]['dataset']
      console.log(alertElement[0])
    }
  };

})(jQuery, Drupal);