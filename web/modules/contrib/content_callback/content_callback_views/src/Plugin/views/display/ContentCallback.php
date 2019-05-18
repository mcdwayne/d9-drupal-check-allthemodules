<?php

/**
 * @file
 * Definition of Drupal\content_callback_views\Plugin\views\display\ContentCallback.
 */

namespace Drupal\content_callback_views\Plugin\views\display;

use Drupal\views\Plugin\views\display\Embed;

/**
 * The plugin that handles a content callback display.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "content_callback",
 *   title = @Translation("Content callback"),
 *   help = @Translation("Provide a display which will be picked up by the content callback module."),
 *   theme = "views_view",
 *   uses_menu_links = FALSE
 * )
 */
class ContentCallback extends Embed {

  // This display plugin does nothing apart from exist.

}
