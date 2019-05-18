<?php

namespace Drupal\gridstack\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\Dejavu\BlazyVideoTrait;

/**
 * Plugin implementation of the 'GridStack' formatter to get ME within images.
 *
 * @todo TBD; deprecated and removed for core Media.
 */
class GridStackFileFormatter extends GridStackFileFormatterBase {

  use GridStackFormatterTrait;
  use BlazyVideoTrait;

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$build, $entity) {
    $settings = $build['settings'];
    /** @var Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    // EntityReferenceItem provides $item->entity Drupal\file\Entity\File.
    if ($item = $this->getImageItem($entity)) {
      $build['item'] = $item['item'];
      $build['settings'] = array_merge($settings, $item['settings']);
    }

    $this->blazyOembed->getMediaItem($build, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'fieldable_form' => TRUE,
      'multimedia'     => TRUE,
      'view_mode'      => $this->viewMode,
    ] + parent::getScopedFormElements();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'file';
  }

}
