<div class="sidebox-sms-ui">
    <!--<h3><?php print $block->subject; ?></h3>-->
    <div class="sidebox-content"><?php print $block->content; ?></div>
    <script>
    // Small javascript for hover effects
    Drupal.behaviors.sms_ui_nav = function() {
      $('.sidebox-sms-ui li:not(.parent, .hover-processed)')
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