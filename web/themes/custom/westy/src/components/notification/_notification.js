'use strict';

const COOKIE_PREFIX = 'Drupal.visitor.westy_notification_dismissed.';

Drupal.behaviors.notification_handler = {
  /**
   * @param {HTMLElement} context
   * @param settings
   */
  attach: function (context) {
    //Find the notification element
    let alertElement = document.querySelectorAll('.westy-notification')

    alertElement.forEach(function (notification) {
      // current cookie
      const current_nid = notification['dataset']['nid']
      const changed = notification['dataset']['changed']
      const currentCookieTimestamp = COOKIE_PREFIX + current_nid + '=' + changed


      // find stored cookie
      let currentCookie = (list) => {
        const all_cookies = document.cookie.split(';')
        let find_notifications = []
        for (let cookie = 0; cookie < all_cookies.length; ++cookie) {
          if (all_cookies[cookie].includes(COOKIE_PREFIX)) {
            find_notifications.push(all_cookies[cookie])
          }
        }
        return find_notifications
      }

      let notificationCookies = currentCookie()

      for (let item = 0; item < notificationCookies.length; ++item) {
        // if any of the list equals a value in cached list remove dismissible
        if (!notificationCookies.includes(' ' + currentCookieTimestamp + '/')) {
          notification.classList.add('westy-notification--dismissible')
        }
      }
      // if there isn't cookie for the notification generate one
      if (notificationCookies.length == 0 && currentCookieTimestamp) {
        document.cookie = currentCookieTimestamp
      }

      // When close button is clicked remove the westy-notification class
      let closeButton = notification.querySelector('.westy-notification__close')
      closeButton.addEventListener('click', (event) => {
        event.preventDefault()
        notification.classList.remove('westy-notification--dismissible')

        // Set cookie value after close
        const nid = notification['dataset']['nid']
        const lastChangedTimestamp = notification['dataset']['changed']
        const path = (drupalSettings && drupalSettings.path && drupalSettings.path.baseUrl) || '/';
        document.cookie = COOKIE_PREFIX + nid + "=" + lastChangedTimestamp + path
        console.log(document.cookies)
      })
    })
  }
};