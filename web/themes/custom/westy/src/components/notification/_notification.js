"use strict";

const COOKIE_PREFIX = "Drupal.visitor.westy_notification_dismissed.";

Drupal.behaviors.notificationHandler = {
  attach() {
    const notifications = document.querySelectorAll(".westy-dismissible");
    notifications.forEach((notification) => {
      const notificationId = notification["dataset"]["nid"];
      const notificationChanged = notification["dataset"]["changed"];
      const cookieStr =
        COOKIE_PREFIX + notificationId + "=" + notificationChanged;

      // If we haven't found a dismissal cookie for the notification and changed timestamp,
      // show the notification. (set as dismissible)
      if (!document.cookie.includes(cookieStr)) {
        notification.classList.add("westy-dismissible--shown");
      }

      const closeButton = notification.querySelector(
        ".westy-notification__close"
      );
      closeButton.addEventListener("click", (event) => {
        event.preventDefault();
        notification.classList.remove("westy-dismissible--shown");
        // Set dismissal cookie
        document.cookie = cookieStr;
      });
    });
  },
};
