<?php

/**
 * @file
 * Contains \Drupal\menu_link_weight\Form\SettingsForm.
 */

namespace Drupal\menu_link_weight\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure menu link weight settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_link_weight_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['menu_link_weight.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('menu_link_weight.settings');

    $form['menu_parent_form_selector'] = array(
      '#type' => 'select',
      '#title' => t('Parent menu link selector'),
      '#default_value' => $config->get('menu_parent_form_selector') ? $config->get('menu_parent_form_selector') : 'default',
      '#options' => [
        'cshs' => $this->t('Client-side hierarchical select'),
      ],
      '#required' => TRUE,
      '#empty_option' => $this->t('Default'),
      '#empty_value' => 'default',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('menu_parent_form_selector') === 'cshs' && !\Drupal::moduleHandler()->moduleExists('cshs')) {
      $form_state->setErrorByName('menu_parent_form_selector', t('You have to install <a href="https://www.drupal.org/project/cshs">Client-side hierarchical select</a> before you can use this option.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('menu_link_weight.settings')
      ->set('menu_parent_form_selector', $form_state->getValue('menu_parent_form_selector'))
      ->save();

    parent::submitForm($form, $form_state);
  }


}
