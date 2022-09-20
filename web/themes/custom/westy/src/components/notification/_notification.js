'use strict';

const COOKIE_PREFIX = 'Drupal.visitor.westy_notification_dismissed.';

Drupal.behaviors.notificatin_handler = {
  /**
   * @param {HTMLElement} context
   * @param settings
   */
  attach(context, settings) {
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
        for (let i = 0; i < all_cookies.length; i++) {
          if (all_cookies[i].includes(COOKIE_PREFIX)) {
            find_notifications.push(cookies[i])
          }
        }
        return find_notifications
      }
      let notificationCookies = currentCookie()
      for (let k = 0; k < notificationCookies.length; ++k) {
        // if any of the list equals a value in cached list remove dismissible
        if (!notificationCookies.includes(' ' + currentCookieTimestamp)) {
          notification.classList.add('westy-notification--dismissible')
        }
      }

      // When close button is clicked remove the westy-notification class
      let closeButton = notification.querySelector('.westy-notification__close')
      closeButton.addEventListener('click', (event) => {
        event.preventDefault()
        notification.classList.remove('westy-notification--dismissible')

        // Set cookie value after close
        const nid = notification['dataset']['nid']
        const lastChangedTimestamp = notification['dataset']['changed']
        document.cookie = COOKIE_PREFIX + nid + "=" + lastChangedTimestamp
      })
    })
  }
};