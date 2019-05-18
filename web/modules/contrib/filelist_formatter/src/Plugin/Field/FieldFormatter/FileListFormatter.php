<?php

namespace Drupal\filelist_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'filelist_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "filelist_formatter",
 *   module = "filelist_formatter",
 *   label = @Translation("List"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileListFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'filelist_formatter_type' => 'ul',
      'filelist_formatter_class' => 'file-list',
      'filelist_formatter_filesize' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $settings = $this->getSettings();

    $form['filelist_formatter_type'] = [
      '#title' => $this->t('List type'),
      '#type' => 'select',
      '#options' => [
        'ul' => $this->t('Unordered HTML list (ul)'),
        'ol' => $this->t('Ordered HTML list (ol)'),
      ],
      '#default_value' => $settings['filelist_formatter_type'],
      '#required' => TRUE,
    ];
    $form['filelist_formatter_class'] = [
      '#title' => $this->t('List classes'),
      '#type' => 'textfield',
      '#size' => 40,
      '#description' => $this->t('A CSS class to use in the markup for the field list.'),
      '#default_value' => $settings['filelist_formatter_class'],
      '#required' => FALSE,
    ];
    $form['filelist_formatter_filesize'] = [
      '#title' => $this->t('Show filesize'),
      '#type' => 'checkbox',
      '#description' => $this->t('Display the file size.'),
      '#default_value' => $settings['filelist_formatter_filesize'],
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    switch ($settings['filelist_formatter_type']) {
      case 'ul':
        $summary[] = $this->t('Unordered HTML list');
        break;

      case 'ol':
        $summary[] = $this->t('Ordered HTML list');
        break;
    }

    if ($settings['filelist_formatter_class']) {
      $summary[] = $this->t('CSS Class') . ': ' . SafeMarkup::checkPlain($settings['filelist_formatter_class']);
    }
    if ($settings['filelist_formatter_filesize']) {
      $summary[] = $this->t('Show filesize');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    if (!$items->isEmpty()) {
      foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {

        $item = $file->_referringItem;
        if ($item->isDisplayed() && $item->entity) {

          $description = '';

          if ($item->getFieldDefinition()->getSetting('description_field')) {
            $description = $item->description;
          }

          if(empty($description)) {
            $description = $file->get('filename')->value;
          }

          if ($settings['filelist_formatter_filesize']) {
            $description .= ' (' . format_size($file->getSize()) . ')';
          }

          $elements[$delta] = [
            '#theme' => 'file_link',
            '#file' => $file,
            '#description' => $description,
            '#cache' => [
              'tags' => $file->getCacheTags(),
            ],
          ];
          // Pass field item attributes to the theme function.
          if (isset($item->_attributes)) {
            $elements[$delta] += ['#attributes' => []];
            $elements[$delta]['#attributes'] += $item->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and should not be rendered in the field
            // template.
            unset($item->_attributes);
          }
        }
      }
      $classes = explode(' ', $settings['filelist_formatter_class']);

      $elements = [
        '#theme' => 'item_list',
        '#items' => $elements,
        '#list_type' => $settings['filelist_formatter_type'],
        '#attributes' => [
          'class' => $classes,
        ],
      ];
    }

    return $elements;
  }

}
