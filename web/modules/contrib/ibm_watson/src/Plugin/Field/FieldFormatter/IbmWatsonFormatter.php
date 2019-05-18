<?php

namespace Drupal\ibm_watson\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\file;

/**
 * Plugin implementation of the 'IBM Watson' formatter.
 *
 * @FieldFormatter(
 *   id = "ibm_watson",
 *   label = @Translation("IBM Watson audio"),
 *   field_types = {
 *     "ibm_watson"
 *   }
 * )
 */
class IbmWatsonFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'ibm_watson_link' => '',
      'ibm_watson_title' => 'View Transcript',
      'ibm_watson_autoplay' => '',
      'ibm_watson_loop' => '',
      'ibm_watson_showinfo' => '',
      'ibm_watson_showtimestamps' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['ibm_watson_title'] = [
      '#type' => 'textfield',
      '#title' => t('ibm_watson title'),
      '#default_value' => $this->getSetting('ibm_watson_title'),
    ];
    $elements['ibm_watson_autoplay'] = [
      '#type' => 'checkbox',
      '#title' => t('Play video automatically when loaded (autoplay).'),
      '#default_value' => $this->getSetting('ibm_watson_autoplay'),
    ];
    $elements['ibm_watson_loop'] = [
      '#type' => 'checkbox',
      '#title' => t('Loop the playback of the video (loop).'),
      '#default_value' => $this->getSetting('ibm_watson_loop'),
    ];
    $elements['ibm_watson_showinfo'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide transcript (showinfo).'),
      '#default_value' => $this->getSetting('ibm_watson_showinfo'),
    ];
    $elements['ibm_watson_showtimestamps'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide timestamps (showtimestamps).'),
      '#default_value' => $this->getSetting('ibm_watson_showtimestamps'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $cp = "";

    $parameters = [
      $this->getSetting('ibm_watson_title'),
      $this->getSetting('ibm_watson_autoplay'),
      $this->getSetting('ibm_watson_loop'),
      $this->getSetting('ibm_watson_showinfo'),
      $this->getSetting('ibm_watson_showtimestamps'),
    ];

    foreach ($parameters as $parameter) {
      if ($parameter) {
        $cp = t('custom parameters');
        break;
      }
    }
    $summary[] = t('ibm_watson audio: @cp', ['@cp' => $cp]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {}

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();
    foreach ($items as $delta => $item) {

      // $file = \Drupal\file\Entity\file::load($item->target_id);.
      $file = File::load($item->target_id);
      if (empty($file)) {
        return $element;
      }
      $uri = $file->getFileUri();

      $jsondata = json_decode($item->translate_text, TRUE);
      if (isset($jsondata['error'])) {
        $translate_text = $item->translate_text;
      }
      else {
        if (count($jsondata['results']) > 0) {
          $translate_text = $jsondata['results'];
        }
        else {
          $translate_text = ['error' => 'No result detected', 'code_description' => 'No result detected'];
        }

      }
      $element[$delta] = [
        '#theme' => 'ibm_watson_audio',
        '#target_id' => $item->target_id,
        '#url' => file_create_url($uri),
        '#mimetype' => $item->mimetype,
        '#translate_text' => $translate_text,
        '#settings' => $settings,
      ];
      $element[$delta]['#attached']['library'][] = 'ibm_watson/drupal.ibm-watson';

    }
    return $element;
  }

}
