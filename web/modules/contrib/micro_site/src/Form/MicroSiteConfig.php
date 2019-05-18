<?php

namespace Drupal\micro_site\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

class MicroSiteConfig extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['micro_site.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_site_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('micro_site.settings');
    $form['base_url'] = [
      '#title' => $this->t('The base url of the master host which hosts sites entities.'),
      '#type' => 'textfield',
      '#default_value' => $config->get('base_url'),
      '#required' => TRUE,
    ];
    $form['base_scheme'] = [
      '#title' => $this->t('The master hosts scheme'),
      '#type' => 'radios',
      '#options' => [
        'http' => 'http',
        'https' => 'https',
      ],
      '#default_value' => $config->get('base_scheme'),
      '#required' => TRUE,
    ];

    $form['public_url'] = [
      '#title' => $this->t('The url on which the master host will be accessible. Must be the base url or a subdomain (i.e. the www version for example)'),
      '#type' => 'textfield',
      '#default_value' => $config->get('public_url'),
      '#required' => TRUE,
    ];

    $form['skip_validation_dns'] = [
      '#title' => $this->t('Skip the DNS validation when a micro site is registered'),
      '#description' => $this->t('By default, when a micro site is registered, an HTTP request is made against the micro site URL to check that it is well reachable. Check this option to disable this behavior. You should then ensure that each micro site is well reachable before being registered. Useful when the site itself may not be able to resolve this URL (because behind a reverse proxy or when using containers).'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('skip_validation_dns'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('micro_site.settings');
    $config->set('base_url', $form_state->getValue('base_url'));
    $config->set('base_scheme', $form_state->getValue('base_scheme'));
    $config->set('public_url', $form_state->getValue('public_url'));
    $config->set('skip_validation_dns', $form_state->getValue('skip_validation_dns'));
    $config->save();
  }

}
