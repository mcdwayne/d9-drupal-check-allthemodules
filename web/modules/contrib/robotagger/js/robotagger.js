;(function($) {
  Drupal.behaviors.RoboTaggerHelper = {
    attach : function() {
      var $link = jQuery('.robotagger-backend-js-select-all');
      var sel = Drupal.t('Select all');
      var dsel = Drupal.t('Deselect all');
      $link.click(function() {
        var $this = jQuery(this);
        var $options = $this.parent().prev().find('input');
        if ($this.text() === sel) {
          $options.attr('checked', true);
          $this.text(dsel);
        } else {
          $options.attr('checked', false);
          $this.text(sel);
        }
      });
    }
  };
})(this.jQuery);