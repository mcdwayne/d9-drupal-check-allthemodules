<?php

/**
 * @file
 * Contains \Drupal\offline_app\Plugin\Field\FieldFormatter\OfflineTextFormatter.
 */

namespace Drupal\offline_app\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;

/**
 * Plugin implementation of the 'offline_text' formatter.
 *
 * @FieldFormatter(
 *   id = "offline_text",
 *   label = @Translation("Offline text"),
 *   field_types = {
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class OfflineTextFormatter extends TextDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'allowed_tags' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['allowed_tags'] = array(
      '#title' => t('Allowed tags'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('allowed_tags'),
      '#description' => t('Enter a comma separated list of tags.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Allowed tags: @allowed_tags', array('@allowed_tags' => (!empty($this->getSetting('allowed_tags')) ? $this->getSetting('allowed_tags') : $this->t('None'))));
    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'processed_text',
        '#text' => strip_tags($item->value, $this->getSetting('allowed_tags')),
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      );
    }

    return $elements;
  }

}
