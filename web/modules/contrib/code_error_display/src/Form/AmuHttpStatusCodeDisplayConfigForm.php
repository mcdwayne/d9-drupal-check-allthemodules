<?php

namespace Drupal\amu_http_status_code_display\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AmuHttpStatusCodeDisplayConfigForm
 *
 * @package Drupal\amu_http_status_code_display\Form
 */
class AmuHttpStatusCodeDisplayConfigForm extends ConfigFormBase {

  /**
   * @return string
   */
  public function getFormId() {
    return 'AmuHttpStatusCodeDisplayConfigForm';
  }

  /**
   * @param array $form The formID value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state The current state of the form.
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['404_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('404 error message'),
      '#description' => $this->t('error 404 message.'),
      '#default_value' => $this->t('404 error'),
    ];

    $form['403_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('403 error message'),
      '#description' => $this->t('error 403 message.'),
      '#default_value' => $this->t('403 error'),
    ];

    return $form;
  }

  /**
   * @param array $form The formID value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('amustatuscode.settings');
    $config->set('404_message', $form_state->getValue('404_message'));
    $config->set('403_message', $form_state->getValue('403_message'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * @return array
   */
  public function getEditableConfigNames() {
    return ['amustatuscode.settings'];
  }
}
