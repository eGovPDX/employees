// // Controls sitewide alert banner function.
(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.alertSystem = {
    attach: function (context) {
      var alertData = {};

      try {
        alertData = JSON.parse(localStorage.getItem('alertUID')) || {};
      }
      catch (e) {
        console.log(e);
      }

      $.each(alertData, function (index, value) {
        $('#' + index).remove();
      });

      $(".alert-item .btn-close").click(function (e) {
        var alertUID = $(this).attr('data-alert');
        $('#' + alertUID).remove();
        alertData[alertUID] = 1;
        try {
          localStorage.setItem('alertUID', JSON.stringify(alertData));
        }
        catch (e) {
          return false;
        }
      });

      $('.alert-group').removeClass('hidden');
    }
  };
})(jQuery, Drupal);
