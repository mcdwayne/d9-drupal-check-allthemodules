<?php

namespace Drupal\webform_prepopulate\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a local action plugin with the anchor fragment.
 */
class PrepopulateSettingsLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    $options['fragment'] = 'prepopulate';
    return $options;
  }

}
