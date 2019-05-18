<?php

namespace Drupal\peertube\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure peertube settings for this site.
 */
class PeertubeSettingsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'peertube_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'peertube.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('peertube.settings');

    $form['oembed_providers_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('oEmbed providers URL'),
      '#default_value' => $config->get('oembed_providers_url'),
      '#description' => $this->t('This URL will provide the default video providers different from PeerTube.'),
    );

    $form['peertube_instances'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Peertube instances'),
      '#default_value' => $config->get('peertube_instances'),
      '#description' => $this->t('Contribute all the domain names of the PeerTube instances you want to use. For now, only the last one will be supported due to a core issue.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('peertube.settings')
      ->set('oembed_providers_url', $form_state->getValue('oembed_providers_url'))
      ->set('peertube_instances', $form_state->getValue('peertube_instances'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
