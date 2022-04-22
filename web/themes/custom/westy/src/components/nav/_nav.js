// // Controls main nav function.
(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.mainnavSystem = {
    attach: function (context) {

      $(document).click(function (event) {
        var clickover = $(event.target);
        if (clickover.hasClass('nav-link dropdown-toggle')) {
          $('.block--westy-main-menu ul li.navbar-nav').removeClass('show').addClass('hidden');
          if (clickover.hasClass('show')) {
            clickover.parent('li.navbar-nav').addClass('show').removeClass('hidden');
          }
          else {
            $('.block--westy-main-menu ul li.navbar-nav').removeClass('hidden');
          }
        }
        else {
          $('.block--westy-main-menu ul li.navbar-nav').removeClass('show hidden');
        }
      });

      $('#nav-btn button').click(function (e) {

        $('.block--westy-main-menu ul li.navbar-nav').removeClass('show hidden');

        if ($('body').hasClass('main-expand')) {
          $('body').removeClass('menu-expand main-expand');
          $('.navbar-collapse').addClass('collapse').removeClass('show');
          $(this).addClass('collapsed').attr('aria-expanded', false);
        }
        else if ($('body').hasClass('search-expand')) {
          $('#nav-search button i').removeClass('gg-close').addClass('gg-search');
          $('body').removeClass('search-expand').addClass('menu-expand main-expand');
          $(this).removeClass('collapsed').attr('aria-expanded', true);
          $('.navbar-collapse').addClass('show').removeClass('collapse');
        }
        else {
          $('body').addClass('main-expand menu-expand');
          $(this).removeClass('collapsed').attr('aria-expanded', true);
          $('.navbar-collapse').addClass('show').removeClass('collapse');
        }

      });

      $('#nav-search button').click(function (e) {

        if ($('body').hasClass('main-expand')) {
          $(this).children('i').addClass('gg-close').removeClass('gg-search');
          $('body').addClass('search-expand').removeClass('main-expand');
          $('#nav-btn button').addClass('collapsed');
        }
        else if ($('body').hasClass('search-expand')) {
          $(this).children('i').addClass('gg-search').removeClass('gg-close');
          $('body').removeClass('search-expand menu-expand');
          $('.navbar-collapse').addClass('collapse').removeClass('show');
        }
        else {
          $('body').addClass('search-expand menu-expand');
          $(this).children('i').addClass('gg-close').removeClass('gg-search');
          $('.navbar-collapse').addClass('show').removeClass('collapse');
        }

      });

    }
  };
})(jQuery, Drupal);
