<?php

namespace Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file\FileInterface;
use Drupal\blazy\Dejavu\BlazyVideoTrait;
use Drupal\slick_browser\SlickBrowserDefault;

/**
 * Displays Slick Browser thumbnail if applicable.
 *
 * The main difference from core EB is it strives to display a thumbnail image
 * before giving up to view mode because mostly dealing with small preview.
 */
class SlickBrowserFieldWidgetDisplayFile extends SlickBrowserFieldWidgetDisplayBase {

  use BlazyVideoTrait;

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity) {
    /** @var \Drupal\file\Entity\File $entity */
    $data = $this->getImageItem($entity);
    $data['settings'] = isset($data['settings']) ? array_merge($this->buildSettings(), $data['settings']) : $this->buildSettings();

    return $this->blazyEntity->build($data, $entity, $entity->getFilename());
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
    ] + parent::getScopedFormElements();
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->isSubclassOf(FileInterface::class);
  }

}
