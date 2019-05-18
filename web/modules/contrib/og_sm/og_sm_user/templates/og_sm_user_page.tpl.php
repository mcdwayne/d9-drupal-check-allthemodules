<?php
/**
 * @file
 * Markup for the site profile page.
 *
 * Variables:
 *
 * @var array $sections
 *   An array of renderable arrays containing section info.
 */
?>
<div class="og-sm-user">
  <div class="og-sm-user__sections">
    <?php print drupal_render($sections) ?>
  </div>
</div>
