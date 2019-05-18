<?php

namespace Drupal\link_badges\Plugin\LinkBadge;

use Drupal\link_badges\Annotation\LinkBadge;
use Drupal\link_badges\LinkBadgeBase;

/**
 * Class ViewsBadge
 * 
 * @LinkBadge(
 *  id = "views_badge",
 *  deriver = "Drupal\link_badges\Plugin\Derivative\ViewsBadge"
 * )
 */
class ViewsBadge extends LinkBadgeBase {

  public $name;
  public $display_id;

  /**
   * {@inheritdoc}
   */
  public function getBadgeValue() {
    $result = views_embed_view($this->name, $this->display_id);
    return drupal_render($result);
  }
}
