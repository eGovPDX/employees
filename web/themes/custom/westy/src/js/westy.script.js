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
import '../components/nav/_nav';

(function ($, Drupal) {

  'use strict';
  Drupal.behaviors.westyBackToTop = {
    attach: function (context, settings) {
      var viewHeight = $(window).height();
      //viewHeight is showing the entire pages height
      var showHeight = viewHeight * .75;
      var isAttached = false;

      $(window).once('backToTopShowButtonHandler').on('scroll', function () {
        var scrollPos = $(document).scrollTop();
        if (scrollPos > showHeight && !isAttached) {
          var buttonText = Drupal.t('Back to top');
          //Needs margin
          $('.layout__region--main', context).append(`<div id="back-to-top" class="btn btn-dark position-fixed bottom-50 end-0 zindex-popover"><a href=".page__content">${buttonText}</a></div>`);
          isAttached = true;
        } else if (scrollPos <= showHeight && isAttached) {
          $('#back-to-top').remove();
          isAttached = false;
        }
      });
    }
  }
  // Drupal.behaviors.westyBackToTop = {
  //   attach: function (context, settings) {
  //     var viewHeight = $(window).height();
  //     var showHeight = viewHeight * 1.5;
  //     var isAttached = false;

  //     $(window).once('backToTopShowButtonHandler').on('scroll', function () {
  //       var scrollPos = $(document).scrollTop();
  //       if (scrollPos > showHeight && !isAttached) {
  //         var buttonText = Drupal.t('Back to top');
  //         $('.block--westy-content', context).append(`<div id="back-to-top" class="btn btn-dark position-fixed zindex-fixed bottom-5 right-5"><a href=".page__content">${buttonText}</a></div>`);
  //         isAttached = true;
  //       } else if (scrollPos <= showHeight && isAttached) {
  //         $('#back-to-top').remove();
  //         isAttached = false;
  //       }
  //     });

  //     $('#back-to-top', context).once('backToTopClickHandler').on('click', function () {
  //       $(this).remove();
  //       isAttached = false;
  //     });
  //   }
  // };

})(jQuery, Drupal);
