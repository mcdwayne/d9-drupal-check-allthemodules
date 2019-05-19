<?php

namespace Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\media\MediaInterface;
use Drupal\blazy\Dejavu\BlazyVideoTrait;
use Drupal\slick_browser\SlickBrowserDefault;

/**
 * Displays Slick Browser Media thumbnail.
 *
 * No annotation discovery for an optional plugin without hard dependencies.
 */
class SlickBrowserFieldWidgetDisplayMedia extends SlickBrowserFieldWidgetDisplayBase {

  use BlazyVideoTrait;

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity) {
    $data['settings'] = $this->buildSettings();
    // @fixme broken.
    return $this->blazyEntity->build($data, $entity, $entity->label());
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return [
      'breakpoints'      => SlickBrowserDefault::getConstantBreakpoints(),
      'image_style_form' => TRUE,
      'thumb_positions'  => TRUE,
      'nav'              => TRUE,
      'view_mode'        => 'default',
    ] + parent::getScopedFormElements();
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->isSubclassOf(MediaInterface::class);
  }

}
