<?php

namespace Drupal\noreferrer\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\PrivateKey;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a noreferrer Config form.
 */
class NoReferrerSettingsForm extends ConfigFormBase {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, PrivateKey $private_key) {
    parent::__construct($config_factory);
    $this->privateKey = $private_key;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('private_key')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'noreferrer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['noreferrer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['noopener'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Add <code>rel="noopener"</code> if link has a target'),
      '#default_value' => $this->config('noreferrer.settings')->get('noopener'),
      '#description'   => $this->t('If checked, the <code>rel="noopener"</code> link type will be added to links that have a target attribute.'),
    ];
    $form['noreferrer'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Add <code>rel="noreferrer"</code> to non-whitelisted links'),
      '#default_value' => $this->config('noreferrer.settings')->get('noreferrer'),
      '#description'   => $this->t('If checked, the <code>rel="noreferrer"</code> link type will be added to non-whitelisted external links.'),
    ];
    $form['whitelisted_domains'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Whitelisted domains'),
      '#default_value' => $this->config('noreferrer.settings')->get('whitelisted_domains'),
      '#description'   => $this->t('Enter a space-separated list of domains to which referrer URLs will be sent (e.g. <em>example.com example.org</em>). Links to all other domains will have a <code>rel="noreferrer"</code> link type added.'),
      '#maxlength'     => NULL,
    ];
    $form['publish'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Publish list of whitelisted domains'),
      '#default_value' => $this->config('noreferrer.settings')->get('publish'),
      '#description'   => $this->t('If checked, the list of whitelisted domains will be published at <a href="@url">@url</a> when saving this form.', [
        '@url' => file_create_url($this->publishUri()),
      ]),
    ];
    $form['subscribe_url'] = [
      '#type'          => 'url',
      '#title'         => $this->t('Subscribe to external list of whitelisted domains'),
      '#default_value' => $this->config('noreferrer.settings')->get('subscribe_url'),
      '#description'   => $this->t('If configured, the list of whitelisted domains will be retrieved from the given URL during each cron run.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('noreferrer.settings')
      ->set('noopener', $form_state->getValue('noopener'))
      ->set('noreferrer', $form_state->getValue('noreferrer'))
      ->set('publish', $form_state->getValue('publish'))
      ->set('subscribe_url', $form_state->getValue('subscribe_url'))
      ->set('whitelisted_domains', $form_state->getValue('whitelisted_domains'))
      ->save();
    if ($form_state->getValue('publish')) {
      $this->publish();
    }
    if ($url = $form_state->getValue('subscribe_url')) {
      noreferrer_subscribe($url);
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Publishes domain whitelist.
   */
  public function publish() {
    if ($whitelist = $this->config('noreferrer.settings')->get('whitelisted_domains')) {
      $whitelist = json_encode(explode(' ', $whitelist));
      file_unmanaged_save_data($whitelist, $this->publishUri(), FILE_EXISTS_REPLACE);
    }
  }

  /**
   * Returns domain whitelist URI.
   */
  public function publishUri() {
    // For security through obscurity purposes, the whitelist URL is secret.
    return 'public://noreferrer-whitelist-' . Crypt::hmacBase64('noreferrer-whitelist', $this->privateKey->get()) . '.json';
  }

}
