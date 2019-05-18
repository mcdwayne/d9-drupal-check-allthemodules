<?php

namespace Drupal\link_badges\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\views\Views;

/**
 * Views-based Link Badges
 */
class ViewsBadge extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    
    $badge_views = Views::getApplicableViews('uses_link_badge');
    foreach ($badge_views as $data) {
      list($view_id, $display_id) = $data;
      $view = Views::getView($view_id);
      $display = $view->getDisplay($display_id);
      $id = $view->storage->id() . ':' . $display_id;
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id] += array(
        'properties' => array('name' => $view->storage->id(), 'display_id' => $display_id),
        'label' => $view->storage->label() . ': ' . $display->options['title'],
      );
    }

    return $this->derivatives;
  }
}
