<?php

namespace Drupal\token_custom_plus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the from to edit admin config settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['token_custom_plus.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'token_custom_plus_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('token_custom_plus.settings');

    $form['custom_token_clone_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clone name suffix'),
      '#description' => $this->t('The suffix to append to both the Administrative name and the Machine name, when cloning a custom token.'),
      '#default_value' => $config->get('custom_token_clone_suffix'),
    ];
    $form['custom_token_list_sort_by_type'] = [
      '#type' => 'radios',
      '#options' => [
        0 => $this->t('Alphabetically, by machine name'),
        1 => $this->t('Alphabetically, by type name first, then within a type, by machine name'),
      ],
      '#title' => $this->t('Sorting style when diplaying custom token list'),
      '#description' => $this->t('The second option will result in a grouping of tokens by type.'),
      '#default_value' => (int) $config->get('custom_token_list_sort_by_type'),
    ];
    $form['custom_token_list_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pager page size when displaying custom token list'),
      '#description' => $this->t('Leave blank for no limit, that is: always display all items on a single page, no matter how long.'),
      '#default_value' => $config->get('custom_token_list_limit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('token_custom_plus.settings')
      ->set('custom_token_clone_suffix', $form_state->getValue('custom_token_clone_suffix'))
      ->set('custom_token_list_sort_by_type', $form_state->getValue('custom_token_list_sort_by_type'))
      ->set('custom_token_list_limit', $form_state->getValue('custom_token_list_limit'))
      ->save();
  }

}
