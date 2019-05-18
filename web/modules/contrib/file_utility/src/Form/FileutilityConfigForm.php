<?php

namespace Drupal\file_utility\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FileutilityConfigForm.
 */
class FileutilityConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'file_utility.fileutilityconfigurations',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_utility_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('file_utility.fileutilityconfigurations');

    $form['allowed_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed Extensions'),
      '#description' => $this->t('Comma seprated file extension on which popup form works'),
      '#size' => 64,
      '#default_value' => $config->get('allowed_extensions'),
    ];

    $form['open_model_file'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open form on file click'),
      '#description' => $this->t('Check to open model form to save information about the user who download the file'),
      '#default_value' => $config->get('open_model_file'),
    ];

    $form['file_force_download'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('File Force Download'),
      '#description' => $this->t('Check to force download the file'),
      '#default_value' => $config->get('file_force_download'),
    ];

    $link = Link::fromTextAndUrl('Click Here', Url::fromUri("internal:/admin/people/permissions#module-file_utility", ['attributes' => ['target' => '_blank']]))->toString();

    $form['forgot_pass'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Set access permission of file by role @link and Search File Utility', ['@link' => $link]),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    if (empty($form_values['allowed_extensions'])) {
      $form_state->setErrorByName('allowed_extensions', $this->t('Fields cannot be empty.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('file_utility.fileutilityconfigurations')
      ->set('allowed_extensions', $form_state->getValue('allowed_extensions'))
      ->set('file_force_download', $form_state->getValue('file_force_download'))
      ->set('open_model_file', $form_state->getValue('open_model_file'))
      ->save();
  }

}
