<?php

namespace Drupal\media_entity_threejs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'threejs Player (HTML5)' formatter.
 *
 * @FieldFormatter(
 *   id = "threejs_player",
 *   label = @Translation("threejs Player (HTML5)"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class ThreeJSPlayer extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings['provide_download_link'] = TRUE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['provide_download_link'] = [
      '#title' => $this->t('Provide Download Link'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('provide_download_link'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $provide_download_link = $this->getSetting('provide_download_link');

    if ($provide_download_link) {
      $summary[] = $this->t('Download link provided.');
    }


    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $provide_download_link = $this->getSetting('provide_download_link');
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $elements[$delta] = array(
        '#theme' => 'media_threejs_file_formatter',
        '#file' => $file,
        '#description' => $item->description,
        '#value' => $provide_download_link,
        '#cache' => array(
          'tags' => $file->getCacheTags(),
        ),
      );
      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += array('#attributes' => array());
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }
    return $elements;
  }

}
