<?php

namespace Drupal\entity_content_export\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define list delimiter formatter base.
 */
abstract class ListDelimiterFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'delimiter' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    $form['delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delimiter'),
      '#description' => $this->t('Input the delimiter that should be used as the 
        separator.'),
      '#size' => 5,
      '#required' => TRUE,
      '#default_value' => $settings['delimiter']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary =  parent::settingsSummary();
    $delimiter = $this->getSetting('delimiter');

    if (!isset($delimiter)) {
      $delimiter = $this->t('None');
    }
    $summary[] = $this->t('<strong>Delimiter</strong>: @delimiter', [
      '@delimiter' => $delimiter
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    if ($list = $this->getFieldItemArrayList($items)) {
      $element[] = [
        '#plain_text' => implode(',', $list)
      ];
    }

    return $element;
  }

  /**
   * Get field item array list.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items instance.
   *
   * @return array
   *   An array of list values.
   */
  abstract protected function getFieldItemArrayList(FieldItemListInterface $items);
}
