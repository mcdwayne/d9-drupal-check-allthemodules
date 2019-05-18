<?php

/**
 * @file
 * Contains
 *   \Drupal\comment_advanced\Plugin\Field\FieldFormatter\ConfigurableCommentFormatter.
 */

namespace Drupal\comment_advanced\Plugin\Field\FieldFormatter;

use Drupal\comment\Plugin\Field\FieldFormatter\CommentDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a configurable default comment formatter.
 *
 * @FieldFormatter(
 *   id = "comment_configurable_default",
 *   module = "comment",
 *   label = @Translation("Comment list (configurable labels)"),
 *   field_types = {
 *     "comment"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class ConfigurableCommentFormatter extends CommentDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['add_new_title'] = 'Add new comment';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($title = $this->getSetting('add_new_title')) {
      $summary[] = $title;
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['add_new_title'] = [
      '#type' => 'textfield',
      '#title' => t('Add new title'),
      '#default_value' => $this->getSetting('add_new_title'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $elements['#add_new_title'] = $this->getSetting('add_new_title');
    return $elements;
  }


}
