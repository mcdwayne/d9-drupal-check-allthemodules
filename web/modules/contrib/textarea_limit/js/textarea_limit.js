(function($) {
  Drupal.behaviors.textarea_limit = {
    attach: function(context, settings) {
      $('.character-limited').each(function() {
        var limit = $(this).find('.limit-count-number').data('limit'); 

        $(this).find('textarea').each(function() {
          var id = $(this).parents('.form-type-textarea').parent().find('.limit-text').find('.limit-count-number').attr('id');

          $(this).limit(limit, '#' + id);
        });
      });
    }
  }
})(jQuery);
