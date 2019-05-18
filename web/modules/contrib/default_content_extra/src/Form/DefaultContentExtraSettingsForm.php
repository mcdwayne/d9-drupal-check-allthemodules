<?php

namespace Drupal\default_content_extra\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultContentExtraSettingsForm.
 *
 * @package Drupal\default_content_extra\Form
 */
class DefaultContentExtraSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'default_content_extra.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'default_content_extra_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('default_content_extra.settings');

    $form['path_alias'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Path aliases'),
      '#default_value' => $config->get('path_alias'),
      '#description' => $this->t('This includes extra path alias data when exporting content and checks for it when importing.'),
    );

    $form['delete_users'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Delete users 0 and 1'),
      '#default_value' => $config->get('delete_users'),
      '#description' => $this->t('Importing user 0 and 1 will fail so this will delete these json files after being exported when using the default-content-export-references command.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('default_content_extra.settings')
      ->set('path_alias', $form_state->getValue('path_alias'))
      ->set('delete_users', $form_state->getValue('delete_users'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
