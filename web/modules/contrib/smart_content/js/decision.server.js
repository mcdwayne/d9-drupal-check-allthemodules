(function ($) {

  Drupal.behaviors.decisionAgentServerSide = {
    attach: function (context, settings) {
      Drupal.smart_content.SmartContentManager.attach('data-smart-content-server', context);
    }
  }
})(jQuery);