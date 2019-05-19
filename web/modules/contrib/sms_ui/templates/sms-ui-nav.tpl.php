<div class="sms-ui-nav">
    <div class="sidebox-content"><?php print $menu; ?></div>
    <script>
    // Small javascript for hover effects
    Drupal.behaviors.sms_ui_nav = function() {
      $('.sms-ui-nav li:not(.parent, .hover-processed)')
      .hover(function () {
        $(this).addClass('hover');
      },
      function () {
        $(this).removeClass('hover');
      })
      .addClass('hover-processed');
    }
    </script>
    <div></div>
</div>

