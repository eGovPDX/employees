/**
 * @file
 * Toc_js.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.toc_js = {
    attach: function(context, settings) {

      var verboseIdCache = {};

      $('.toc-js', context).each(function () {
        var nav = $(this).find('nav');
        var options = {
          listType: $(this).data('list-type') == 'ol' ? '<ol/>' : '<ul/>',
          selectors: $(this).data('selectors') || 'h2, h3',
          container: $(this).data('container') || 'body',
          scrollToOffset: $(this).data('scroll-to-offset') || 0,
          prefix: $(this).data('prefix') || 'toc',
          backToTop: !!$(this).data('back-to-top'),
          backToTopLabel: $(this).data('back-to-top-label') || 'Back to top',
          letterCase: $(this).data('letter-case') || 'lowercase',
          highlightOnScroll: !!$(this).data('highlight-on-scroll'),
          highlightOffset: $(this).data('highlight-offset') || 100,
        };

        if (!$(this).data('smooth-scrolling')) {
          options.smoothScrolling = false;
        }

        // Fix a bug because anchorName are not unique if heading has same text.
        // Override the anchorName function. See https://github.com/jgallen23/toc/pull/69
        options.anchorName = function(i, heading, prefix) {
          if (heading.id.length) {
            return heading.id;
          }
          var candidateId = $(heading).text().replace(/[^a-z0-9]/ig, ' ').replace(/\s+/g, '-').toLowerCase();
          if (verboseIdCache[candidateId]) {
            var j = 2;
            // Fix is here. Added '-' between candidateUd and j.
            while (verboseIdCache[candidateId + '-' + j]) {
              j++;
            }
            candidateId = candidateId + '-' + j;

          }
          verboseIdCache[candidateId] = true;

          return prefix + '-' + candidateId;
        };

        nav.toc(options);

        // Add aria attributes on toc.
        nav.find('ul').attr('role', 'menubar');
        nav.find('li').each(function () {
          $(this).attr('role', 'presentation');
        });
        nav.find('a').each(function () {
          $(this).attr('aria-label', $(this).text());
        });

        // Custom implementation.
        // Add back to top link on headings and id to toc element.
        // Remove when https://github.com/jgallen23/toc/pull/69 is merged.
        var opts = $.extend({}, nav.toc.defaults, options);
        if (opts.backToTop) {
          var scrollTo = function(e, callback) {
            if (opts.smoothScrolling && typeof opts.smoothScrolling === 'function') {
              e.preventDefault();
              var elScrollTo = $(e.target).attr('href');

              opts.smoothScrolling(elScrollTo, opts, callback);
            }
            $('li', self).removeClass(opts.activeClass);
            $(e.target).parent().addClass(opts.activeClass);
          };

          nav.find('a').each(function () {
            var container = $(opts.container);
            var anchor = $(this).attr('href').replace(/^#/, '');
            var anchorNameToTop = anchor + '-to-top';
            $(this).attr('id', anchorNameToTop);

            var $span = container.find('span[id="' + anchor + '"]');
            var anchorToTop = $('<a/>')
              .attr('href', '#' + anchorNameToTop)
              .addClass('back-to-top')
              .text(opts.backToTopLabel)
              .insertAfter($span)
              .bind('click', function(e) {
                scrollTo(e, function() {
                });
                nav.trigger('selected', $(this).attr('href'));
              });
          });
        }

      });
      // End custom back to top implementation.

      // Hide toc (and the title) if no heading found or minimum not reached.
      $('.toc-js', context).each(function () {
        var n = $(this).find('li').length;
        var minimum = $(this).data('selectors-minimum') || 0;
        var container = $(this).data('container') || 'body';
        if (n >= minimum) {
          $(this).show();
          $(container).find('a.back-to-top').show();
        }
      });

      // Stick the toc.
      function sticky_relocate() {

        $('.toc-js.sticky', context).each(function () {
          var sticky = $(this);
          var stopSelector = $(this).data('sticky-stop') || '';
          var stopPadding = $(this).data('sticky-stop-padding') || 0;
          var windowTop = $(window).scrollTop();
          var stickyTop = $('#sticky-anchor').offset().top;
          var stickyHeight = sticky.height();
          var stickOffset = $(this).data('sticky-offset') || 0;

          if ($(stopSelector).length) {
            var stickyStop = $(stopSelector).offset().top;
          }
          else {
            stickyStop = 0;
          }

          if (stickyStop > 0 && (windowTop + stickyHeight + stickOffset > stickyStop - stopPadding))
            sticky.css({top: (windowTop + stickyHeight - stickyStop + stopPadding) * -1});
          else if (windowTop + stickOffset > stickyTop) {
            sticky.addClass('is-sticked');
            sticky.css({top: stickOffset})
          } else {
            sticky.removeClass('is-sticked').css({top: 'initial'});
          }
        });

      }

      $(window).scroll(sticky_relocate);
      sticky_relocate();

    }
  };

}(jQuery));
