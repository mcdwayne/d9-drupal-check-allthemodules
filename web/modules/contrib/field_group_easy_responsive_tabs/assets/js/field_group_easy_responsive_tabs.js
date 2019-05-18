(function ($) {

  /**
   * Behavior to initialize Field Group - Easy Responsive Tabs elements.
   *
   * @type {{attach: Drupal.behaviors.FieldGroupEasyResponsiveTabsToAccordion.attach}}
   */
  Drupal.behaviors.FieldGroupEasyResponsiveTabsToAccordion = {
    attach: function (context, settings) {
      $(context)
        .find('.field-group-easy-responsive-tabs')
        .once('field-group-easy-responsive-tabs')
        .each(function () {
          var $this = $(this);

          $(this).easyResponsiveTabs({
            type: $this.data('type') || null,
            width: $this.data('width') || null,
            fit: $this.data('fit') || null,
            closed: $this.data('closed') || null,
            tabidentify: $this.data('tabidentify') || null,
            activetab_bg: $this.data('activetab_bg') || null,
            inactive_bg: $this.data('inactive_bg') || null,
            active_border_color: $this.data('active_border_color') || null,
            active_content_border_color: $this.data('active_content_border_color') || null,
            activate: function () {
            }
          });
        })
    }
  };

})(jQuery);
