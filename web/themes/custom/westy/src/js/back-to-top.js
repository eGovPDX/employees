(function ($, Drupal) {

  Drupal.behaviors.westyBackToTop = {
    attach: function (context, settings) {
      var viewHeight = $(window).height();
      var showHeight = viewHeight * .75;
      var isAttached = false;

      $(window).once('backToTopShowButtonHandler').on('scroll', function () {
        var scrollPos = $(document).scrollTop();
        if (scrollPos > showHeight && !isAttached) {
          var buttonText = Drupal.t('Back to top');
          // $('.layout__region--main', context).append(`<div id="back-to-top" class="btn btn-lg btn-dark zindex-popover position-fixed bottom-0 end-50 mb-3 me-3"><a class="link-light" href="#header">${buttonText}</a></div>`);
          $('.layout__region--main', context).append(`<div id="back-to-top" class="btn btn-lg btn-dark zindex-popover position-fixed bottom-0 end-0 mb-3 me-3"><a class="link-light" href="#header">${buttonText}</a></div>`);
          isAttached = true;
        } else if (scrollPos <= showHeight && isAttached) {
          $('#back-to-top').remove();
          isAttached = false;
        }
      });
    }
  }
})(jQuery, Drupal);
