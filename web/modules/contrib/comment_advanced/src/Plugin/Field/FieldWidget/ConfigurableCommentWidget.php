<?php

/**
 * @file
 * Contains
 *   \Drupal\comment_advanced\Plugin\Field\FieldWidget\ConfigurableCommentWidget.
 */

namespace Drupal\comment_advanced\Plugin\Field\FieldWidget;

use Drupal\comment\Plugin\Field\FieldWidget\CommentWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a widget which allows to change the details and radio title.
 *
 * @FieldWidget(
 *   id = "comment_configurable_default",
 *   label = @Translation("Comment configurable default"),
 *   field_types = {
 *     "comment"
 *   }
 * )
 */
class ConfigurableCommentWidget extends CommentWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['radios_title'] = 'Comments';
    $settings['details_title'] = 'Comment settings';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['radios_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title of the radios element'),
      '#default_value' => $this->getSetting('radios_title'),
    );
    $element['details_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title of the details title'),
      '#default_value' => $this->getSetting('details_title'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Radios title: @title', ['@title' => $this->getSetting('radios_title')]);
    $summary[] = t('Details title: @title', ['@title' => $this->getSetting('details_title')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['status']['#title'] = $this->getSetting('radios_title');
    $element['#title'] = $this->getSetting('details_title');

    return $element;
  }

}
