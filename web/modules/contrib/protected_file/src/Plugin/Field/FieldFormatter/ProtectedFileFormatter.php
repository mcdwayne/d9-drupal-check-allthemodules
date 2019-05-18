<?php

namespace Drupal\protected_file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'protected_file_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "protected_file_formatter",
 *   label = @Translation("Protected File default formatter"),
 *   field_types = {
 *     "protected_file"
 *   }
 * )
 */
class ProtectedFileFormatter extends ProtectedFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'protected_file_new_window' => 1,
      'protected_file_path' => '/user/login',
      'redirect_to_file' => 0,
      'protected_file_modal' => 0,
      'protected_file_message' => 'You need to be logged in to be able to download this file',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['protected_file_new_window'] = [
      '#title' => $this->t('Open the file in a new window'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('protected_file_new_window'),
    ];

    $form['protected_file_path'] = [
      '#title' => $this->t('The path to redirect for users without permissions to download protected file'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('protected_file_path'),
    ];

    $form['redirect_to_file'] = [
      '#title' => $this->t('Redirect directly to private file after login'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('redirect_to_file'),
    ];

    $form['protected_file_modal'] = [
      '#title' => $this->t('Open the path set above in a modal'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('protected_file_modal'),
    ];

    $form['protected_file_message'] = [
      '#title' => $this->t('The message to show to user if the file is protected from download'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('protected_file_message'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Open file in a new window: @window', array('@window' => $this->getSetting('protected_file_new_window')));
    $summary[] = $this->t('Redirect path: @path', array('@path' => $this->getSetting('protected_file_path')));
    $summary[] = $this->t('Redirect to file: @file', array('@file' => $this->getSetting('redirect_to_file')));
    $summary[] = $this->t('Open path in a modal: @modal', array('@modal' => $this->getSetting('protected_file_modal')));
    $summary[] = $this->t('Message: @message', array('@message' => $this->getSetting('protected_file_message')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity = $items->getEntity();
    $field_name = $items->getFieldDefinition()->getName();
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $elements[$delta] = [
        '#theme' => 'protected_file_link',
        '#file' => $file,
        '#settings' => $this->getSettings(),
        '#protected' => $item->isProtected(),
        '#description' => $item->description,
        '#entity' => $entity,
        '#field_name' => $field_name,
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += ['#attributes' => []];
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;
  }

}
