<?php

namespace Drupal\library_select\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'library_select_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "library_file_formatter",
 *   label = @Translation("Library File Upload"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class LibraryFileFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Implement default settings.
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
        // Implement settings form.
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $file) {
      /** @var \Drupal\file\Entity\File $file_entity */
      $file_entity = ($file instanceof File) ? $file : File::load($file->fid);
      $relativePath = file_url_transform_relative(file_create_url($file_entity->getFileUri()));
      if ($file_entity->getMimeType() === 'text/css') {
        library_select_add_css($relativePath);
      }

      if ($file_entity->getMimeType() === 'application/javascript') {
        library_select_add_js($relativePath);
      }
    }

    return $elements;
  }

}
