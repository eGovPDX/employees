const COOKIE_PREFIX = 'Drupal.visitor.westy_notification_dismissed.';

Drupal.behaviors.notificatin_handler = {
  /**
   * @param {HTMLElement} context
   * @param settings
   */
  attach(context, settings) {
    //set an alert cookie
    let alertElement = document.getElementsByClassName('westy-notification')
    const current_nid = alertElement[0]['dataset']['nid']
    const changed = alertElement[0]['dataset']['changed']
    const currentCookieTimestamp = COOKIE_PREFIX + current_nid + changed

    // find stored cookie
    const docCookies = document.cookie.split(';')
    const currentCookie = (list) => {
      for (let i = 0; 1 < list.length; ++i) {
        if (list[i].includes(COOKIE_PREFIX)) {
          return docCookies[i]
        }
        else {
          return ''
        }
      }
    }
    const findCookie = currentCookie(docCookies)

    // make notification viewable if current cookie does not match with the stored cookie
    if (' ' + currentCookieTimestamp != findCookie) {
      alertElement[0].classList.add('westy-notification--dismissible')
    }

    // When close button is clicked remove the westy-notification class
    let closeButton = document.getElementsByClassName('westy-notification__close')
    closeButton[0].addEventListener('click', (event) => {
      event.preventDefault()
      alertElement[0].classList.remove('westy-notification--dismissible')

      // Set cookie value after close
      const nid = alertElement[0]['dataset']['nid']
      const lastChangedTimestamp = alertElement[0]['dataset']['changed']
      document.cookie = encodeURIComponent(COOKIE_PREFIX + nid + lastChangedTimestamp)
    })

  }
};