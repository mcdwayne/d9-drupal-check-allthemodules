<?php

namespace Drupal\service_comment_count\Form;

use Artistan\ReviveXmlRpc\OpenAdsV2ApiXmlRpc;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Configure service_comment_count settings for this site.
 */
class ServiceCommentCountSettingsForm extends ConfigFormBase {

  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'service_comment_count_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'service_comment_count.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('service_comment_count.settings');

    $form['items_per_batch'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Items per Batch'),
      '#description' => $this->t('Numbers of items, you want to process in one batch. You should use a reasonable high number, to not exceed API limits. The module takes care that the maximum number of items per request will not exceed.'),
      '#default_value' => $config->get('items_per_batch'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('service_comment_count.settings');
    $config->set('items_per_batch', $form_state->getValue('items_per_batch'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
