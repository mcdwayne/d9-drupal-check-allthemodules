<?php

namespace Drupal\tikitoki\FieldProcessor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;

/**
 * Class MediaFieldProcessor.
 *
 * @package Drupal\tikitoki\FieldProcessor
 */
class MediaFieldProcessor extends BaseFieldProcessor {
  /**
   * {@inheritdoc}
   */
  protected static $destinationId = 'media';

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = [];
    // Prepare some required data.
    $files = $this->field->getValue($this->viewsRow);
    $entity = $this->field->getEntity($this->viewsRow);
    // Handle the single-value fields as well.
    $files = is_array($files) ? $files : [$files];
    if (!empty($files)) {
      $files = File::loadMultiple($files);
      if (!empty($files)) {
        $index = 0;
        foreach ($files as $delta => $file) {
          if ($file instanceof File) {
            $value[] = [
              'id'      => $file->id(),
              'src'     => file_create_url($file->getFileUri()),
              'type'    => $this->getType(),
              'caption' => $this->getCaption($entity, $this->field->field, $index),
            ];
          }
          $index++;
        }
      }
    }
    return $value;
  }

  /**
   * Get field type.
   *
   * @return string
   *   Media field type.
   */
  private function getType() {
    $type = 'Image';
    // @TODO: add supporting other media field types.
    return $type;
  }

  /**
   * Get file's caption text.
   *
   * @return string
   *   File's caption text if any.
   */
  private function getCaption(EntityInterface $entity, $field_name, $delta) {
    if ($entity->hasField($field_name) && !empty($entity->{$field_name})) {
      return $entity->{$field_name}->get($delta)->getValue()['title'];
    }
  }

}
