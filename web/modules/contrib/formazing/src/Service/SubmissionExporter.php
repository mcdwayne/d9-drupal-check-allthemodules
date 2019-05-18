<?php

namespace Drupal\formazing\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\formazing\Entity\FieldFormazingEntity;
use Drupal\formazing\Entity\ResultFormazingEntity;
use Drupal\formazing\FieldSettings\SubmitField;

/**
 * Class SubmissionExporter
 *
 * @package Drupal\formazing\Service
 */
class SubmissionExporter {

  /**
   * File system helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * File being importer
   *
   * @var string
   */
  private $file;

  /**
   * OfferExporter constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   */
  public function __construct(FileSystemInterface $fileSystem) {
    $this->fileSystem = $fileSystem;
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $form
   *
   * @return bool
   */
  public function exportCsv(ContentEntityInterface $form) {
    $submissions = \Drupal::entityQuery('result_formazing_entity')
      ->condition('form_type', $form->id())
      ->execute();

    if (!$submissions) {
      return FALSE;
    }

    $fields = $this->getFormFields($form);
    $this->file = $this->fileSystem->tempnam('temporary://', "formazing_");
    $submissions = ResultFormazingEntity::loadMultiple($submissions);

    $file = fopen($this->file, 'w');
    fwrite($file,"\xEF\xBB\xBF");
    fputcsv($file, $fields, ';');
    // We build an array of fields we're looking for as keys.
    $fieldList = array_fill_keys(array_values($fields), NULL);

    // Loop on each submission to fill the file with arrays of field => value
    foreach ($submissions as $submission) {
      // Get your own copy, because it belongs to every submission !
      $values = $fieldList;
      // Decode saved values
      $savedValues = json_decode($submission->get('data')->value, TRUE);
      foreach ($savedValues as $value) {
        $field = $value['label'];
        if (!array_key_exists($field, $values) || $value['type'] === 'submit') {
          continue;
        }

        // Keep it !
        $values[$field] = $value['value'];
      }
      // Finally write our array of values (or null in case we didn't find a value)
      fputcsv($file, $values, ';');
    }

    return $this->file;
  }

  /**
   * Returns an array with the fields for the given form.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $form
   *
   * @return array
   */
  public function getFormFields(ContentEntityInterface $form) {
    $fields = \Drupal::entityQuery('field_formazing_entity')
      ->condition('formazing_id', $form->id())
      ->execute();

    if (!$fields) {
      return [];
    }

    $fields = FieldFormazingEntity::loadMultiple($fields);
    // Remove useless fields from the those we'll export.
    $fields = array_filter($fields, function (FieldFormazingEntity $field) {
      return $field->get('field_type')->value != SubmitField::class;
    });
    // Clean up a little bit.
    array_walk($fields, function (FieldFormazingEntity &$field) {
      $field = $field->getMachineName() ?: str_replace(' ', '_', strtolower($field->getName()));
    });

    return $fields;
  }
}
