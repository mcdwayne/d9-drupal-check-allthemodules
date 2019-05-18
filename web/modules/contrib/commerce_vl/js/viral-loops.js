/**
 * @file
 * Viral Loops JS code.
 */

(function ($, window, Drupal) {

  /**
   * Attaches Viral Loops JS.
   */
  Drupal.behaviors.dcomViralLoopsScripts = {
    attach: function (context) {
      $('.dcom-viral-loops', context)
        .once('dcom-viral-loops')
        .each(function () {
          var $this = $(this);
          var vl_widget_data = $this.data('vl-widget');
          var vl_client_identify_user_data = $this.data('vl-client-identify-user');
          var vl_logout = $this.data('vl-logout');
          if (vl_logout && !$.cookie('Drupal.visitor.vl_logout')) {
            return false;
          }
          var timeoutDelay = vl_widget_data['timeout_delay'] ? (vl_widget_data['timeout_delay'] * 1000) : 0;
          setTimeout(function() {
            // Viral Loops widget script.
            if (vl_widget_data) {
              !function(){var a=window.VL=window.VL||{};return a.instances=a.instances||{},a.invoked?void(window.console&&console.error&&console.error("VL snippet loaded twice.")):(a.invoked=!0,void(a.load=function(b,c,d){var e={};e.publicToken=b,e.config=c||{};var f=document.createElement("script");f.type="text/javascript",f.id="vrlps-js",f.defer=!0,f.src="https://app.viral-loops.com/client/vl/vl.min.js";var g=document.getElementsByTagName("script")[0];return g.parentNode.insertBefore(f,g),f.onload=function(){a.setup(e),a.instances[b]=e},e.identify=e.identify||function(a,b){e.afterLoad={identify:{userData:a,cb:b}}},e.pendingEvents=[],e.track=e.track||function(a,b){e.pendingEvents.push({event:a,cb:b})},e.pendingHooks=[],e.addHook=e.addHook||function(a,b){e.pendingHooks.push({name:a,cb:b})},e.$=e.$||function(a){e.pendingHooks.push({name:"ready",cb:a})},e}))}();var campaign=VL.load(vl_widget_data['campaign_id'],{autoLoadWidgets:!0});campaign.addHook("boot",function(){campaign.widgets.create("rewardingWidget",{container:"body",position:"bottom-left"}),campaign.widgets.create("rewardingWidgetTrigger",{container:"body",position:"bottom-left"})});
            }

            // Identifying the user on the client side.
            if (vl_client_identify_user_data) {
              campaign.identify({
                firstname: vl_client_identify_user_data['firstname'],
                lastname: vl_client_identify_user_data['lastname'],
                email: vl_client_identify_user_data['email'],
                createdAt: vl_client_identify_user_data['createdAt']
              });
            }

            // VL user logout script.
            if (vl_logout && vl_widget_data) {
              $.removeCookie('Drupal.visitor.vl_logout');
              campaign.onBoot = function() { campaign.logout({reloadWidgets: true}) }
            }
          }, timeoutDelay);
        });
    }
  };

})(jQuery, window, Drupal);
