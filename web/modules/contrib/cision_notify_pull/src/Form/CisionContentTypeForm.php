<?php

namespace Drupal\cision_notify_pull\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class CisionContentTypeForm.
 *
 * @package Drupal\cision_notify_pull\Form
 */
class CisionContentTypeForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cision_notify_pull.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cision_choose_content_type';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $types = node_type_get_names();
    $config = $this->config('cision_notify_pull.settings');
    $form['cision_notify_pull_allowed_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Content types'),
      '#default_value' => $config->get('allowed_type'),
      '#options' => $types,
      '#description' => $this->t('Choose content type to be imported from cision feed.'),
      '#required' => TRUE,
    ];

    $form['cision_notify_pull_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add log messages.'),
      '#default_value' => $config->get('debug'),
      '#description' => $this->t('Check this option add some logs messages into Drupal log system to check of Post data.'),
    ];
    $form['array_filter'] = ['#type' => 'value', '#value' => TRUE];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('cision_notify_pull.settings')
      ->set('allowed_type', $form_state->getValue('cision_notify_pull_allowed_type'))
      ->set('debug', $form_state->getValue('cision_notify_pull_debug'))
      ->save();

    parent::submitForm($form, $form_state);
    $response = new RedirectResponse('/admin/config/services/cision-mapping-target');
    $response->send();
  }

}
