<?php

/**
 * @file
 * Contains \Drupal\human\Form\HumanSettings.
 */

namespace Drupal\human\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class HumanSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'human_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('human.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['human.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['human_form'] = [
      '#type' => 'textarea',
      '#title' => t('Setup forms that will be checked by human module.'),
      '#default_value' => \Drupal::config('human.settings')->get('human_form'),
      '#description' => t('Enter the form ids for human module, seperate each form id with ",".'),
    ];
    return parent::buildForm($form, $form_state);
  }

}
