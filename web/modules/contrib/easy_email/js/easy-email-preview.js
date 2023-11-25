(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.resizablePreview = {
    attach: function (context, settings) {
      $(context).find('.easy-email-resizable').once().each(function(i, element) {

        $(element).resizable({
          minHeight: 100,
          minWidth: 200
        });
        console.log(element);
      });
    }
  };

})(jQuery, Drupal);