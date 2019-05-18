<?php

namespace Drupal\nice_filemime\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\BaseFieldFileFormatterBase;

/**
 * Formatter to render the Nice File MIME type, with an optional icon.
 *
 * @FieldFormatter(
 *   id = "nice_filemime",
 *   label = @Translation("Nice File MIME"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class NiceFileMimeFormatter extends BaseFieldFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition) && $field_definition->getName() === 'filemime';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['filemime_image'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['filemime_image'] = [
      '#title' => $this->t('Display an icon'),
      '#description' => $this->t('The icon is representing the file type, instead of the MIME text (such as "image/jpeg")'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('filemime_image'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $value = $item->value;

    // Return icon if settings are enabled.
    if ($this->getSetting('filemime_image') && $value) {
      $file_icon = [
        '#theme' => 'image__file_icon',
        '#file' => $item->getEntity(),
      ];

      return $file_icon;
    }

    // Get the nice file mime.
    $niceFileMime = \Drupal::service('nice_filemime.filemime')->getNiceFileMime($value);
    return $niceFileMime;
  }

}
