<?php

namespace Drupal\link_badges\Plugin\views\display;

use Drupal\views\Annotation\ViewsDisplay;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * The plugin that handles a link badge display.
 *
 * Link badges are displays that can be used to create
 * notification-style badges on link.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "link_badge",
 *   title = @Translation("Link Badge"),
 *   help = @Translation("Link badges can be used to create notification-style badges on links such as menus."),
 *   theme = "link_badges_views_view",
 *   uses_link_badge = TRUE
 * )
 *
 */
class LinkBadge extends DisplayPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::execute().
   */
  public function execute() {
    return $this->view->render($this->display['id']);
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::getType().
   */
  public function getType() {
    return 'link_badge';
  }

}

