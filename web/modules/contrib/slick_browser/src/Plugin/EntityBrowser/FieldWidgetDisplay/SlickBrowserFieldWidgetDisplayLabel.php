<?php

namespace Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay;

use Drupal\Core\Entity\EntityInterface;

/**
 * Displays a label of the entity.
 */
class SlickBrowserFieldWidgetDisplayLabel extends SlickBrowserFieldWidgetDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity) {
    return $entity->label();
  }

}
