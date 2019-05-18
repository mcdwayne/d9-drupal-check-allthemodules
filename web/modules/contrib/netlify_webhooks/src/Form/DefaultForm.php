<?php

namespace Drupal\netlify_webhooks\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultForm.
 */
class DefaultForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'netlify_webhooks.default',
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
    $config = $this->config('netlify_webhooks.default');
    $form['build_hook_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Build Hook ID'),
      '#description' => $this->t('Final portion of the build hook URL from the build hooks section of the Netlify Continious Deployment settings. Follows the format https://api.netlify.com/build_hooks/build_hook_id. A post request will be made to this webhook URL when entities are updated by Drupal.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('build_hook_id'),
    ];
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

    $this->config('netlify_webhooks.default')
      ->set('build_hook_id', $form_state->getValue('build_hook_id'))
      ->save();
  }

}
