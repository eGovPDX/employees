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
          $('.layout__region--main', context).append(`<div id="back-to-top" class="btn btn-lg btn-dark"><a class="link-light text-decoration-none" href="#header">${buttonText}</a></div>`);
          isAttached = true;
        } else if (scrollPos <= showHeight && isAttached) {
          $('#back-to-top').remove();
          isAttached = false;
        }
      });
    }
  }
})(jQuery, Drupal);
