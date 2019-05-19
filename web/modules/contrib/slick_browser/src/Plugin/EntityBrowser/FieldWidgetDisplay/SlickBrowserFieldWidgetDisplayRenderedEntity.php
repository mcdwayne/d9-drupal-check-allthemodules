<?php

namespace Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay;

use Drupal\Core\Entity\EntityInterface;

/**
 * Displays Slick Browser as a rendered entity.
 */
class SlickBrowserFieldWidgetDisplayRenderedEntity extends SlickBrowserFieldWidgetDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity) {
    $settings['view_mode'] = isset($this->configuration['view_mode']) ? $this->configuration['view_mode'] : 'slick_browser';

    return $this->blazyEntity->getEntityView($entity, $settings, $entity->label());
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return ['view_mode' => 'slick_browser'] + parent::getScopedFormElements();
  }

}
