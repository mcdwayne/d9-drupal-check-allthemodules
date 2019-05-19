<?php

/**
 * @file
 * Contains Drupal\toolbar_visibility\Form\DefaultForm.
 */

namespace Drupal\toolbar_visibility\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultForm.
 *
 * @package Drupal\toolbar_visibility\Form
 */
class DefaultForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'toolbar_visibility.default_config'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'default_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('toolbar_visibility.default_config');

    $themes = \Drupal::config('toolbar_visibility.default_config')->get('toolbar_visibility_theme');
    $list_themes = \Drupal::service('theme_handler')->listInfo();

    $all_themes = [];
    foreach ($list_themes as $list) {
      $all_themes[$list->getName()] = $list->getName();
    }

    $form['toolbar_visibility_theme'] = array(
      '#type' => 'select',
      '#title' => t('Select theme(s) where you want to remove Toolbar'),
      '#multiple' => TRUE,
      '#options' => $all_themes,
      '#default_value' => \Drupal::config('toolbar_visibility.default_config')
        ->get('toolbar_visibility_theme'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->cleanValues()->getValues();
    $this->config('toolbar_visibility.default_config')
      ->set('toolbar_visibility_theme', $values['toolbar_visibility_theme'])
      ->save();
  }
}
