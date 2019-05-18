<?php

namespace Drupal\cdek_api\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a form to manage settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Indicates no caching.
   */
  const CACHE_TYPE_NONE = 1;

  /**
   * Indicates permanent caching.
   */
  const CACHE_TYPE_PERMANENT = 2;

  /**
   * Indicates caching with a custom cache lifetime.
   */
  const CACHE_TYPE_CUSTOM = 3;

  /**
   * The cache object associated with the 'cdek_api' bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache object associated with the 'cdek_api' bin.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {
    parent::__construct($config_factory);
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.cdek_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cdek_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cdek_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cdek_api.settings');

    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic'),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced'),
    ];

    // Account.
    $form['basic']['account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account'),
      '#description' => $this->t('Contractor identifier.'),
      '#default_value' => $config->get('account'),
      '#size' => 40,
    ];

    // Secure password.
    $form['basic']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secure password'),
      '#description' => $this->t('Security code provided by CDEK.'),
      '#default_value' => $config->get('password'),
      '#size' => 40,
    ];

    // Timeout.
    $form['advanced']['request_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout'),
      '#description' => $this->t('Float representing the maximum number of seconds the API request may take. Use 0 to wait indefinitely.'),
      '#default_value' => $config->get('request_timeout'),
      '#min' => 0,
      '#max' => 30,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $cache_lifetime = $config->get('cache_lifetime');
    if ($cache_lifetime === NULL) {
      $cache_type = self::CACHE_TYPE_NONE;
    }
    elseif ($cache_lifetime === CacheBackendInterface::CACHE_PERMANENT) {
      $cache_type = self::CACHE_TYPE_PERMANENT;
      $cache_lifetime = NULL;
    }
    else {
      $cache_type = self::CACHE_TYPE_CUSTOM;
    }

    // Cache type.
    $form['advanced']['cache_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Cache type'),
      '#options' => [
        self::CACHE_TYPE_NONE => $this->t('None'),
        self::CACHE_TYPE_PERMANENT => $this->t('Permanent'),
        self::CACHE_TYPE_CUSTOM => $this->t('Custom'),
      ],
      '#default_value' => $cache_type,
      '#required' => TRUE,
    ];

    $conditions = [
      ':input[name="cache_type"]' => [
        'value' => self::CACHE_TYPE_CUSTOM,
      ],
    ];

    // Cache lifetime.
    $form['advanced']['cache_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache lifetime'),
      '#description' => $this->t('Cache lifetime in minutes.'),
      '#default_value' => $cache_lifetime,
      '#min' => 1,
      '#step' => 1,
      '#states' => [
        'visible' => $conditions,
        'required' => $conditions,
        'enabled' => $conditions,
      ],
      '#element_validate' => [[$this, 'validateCacheLifetime']],
    ];

    // Button to clear the cache.
    $form['advanced']['clear_cache'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear cache'),
      '#submit' => [[$this, 'clearCacheSubmit']],
      '#limit_validation_errors' => [],
      '#access' => $cache_type !== self::CACHE_TYPE_NONE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate cache_lifetime element.
   *
   * @param array $element
   *   The element structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateCacheLifetime(array $element, FormStateInterface $form_state) {
    $cache_type = (int) $form_state->getValue('cache_type');
    if ($cache_type === self::CACHE_TYPE_CUSTOM && !$form_state->getValue('cache_lifetime')) {
      $form_state->setError($element, $this->t('@name field is required.', [
        '@name' => $element['#title'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cdek_api.settings');

    $cache_type = (int) $form_state->getValue('cache_type');
    if ($cache_type === self::CACHE_TYPE_PERMANENT) {
      $cache_lifetime = CacheBackendInterface::CACHE_PERMANENT;
    }
    elseif ($cache_type === self::CACHE_TYPE_CUSTOM) {
      $cache_lifetime = $form_state->getValue('cache_lifetime');
    }
    else {
      // Clear the cache if the API data is not cached.
      $this->cache->deleteAll();
      $cache_lifetime = NULL;
    }

    // Save data in configuration.
    $config->set('account', $form_state->getValue('account'));
    $config->set('password', $form_state->getValue('password'));
    $config->set('request_timeout', $form_state->getValue('request_timeout'));
    $config->set('cache_lifetime', $cache_lifetime);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Submission handler to clear the cache.
   */
  public function clearCacheSubmit() {
    $this->cache->deleteAll();
    $this->messenger()->addStatus($this->t('The cache has been cleared.'));
  }

}
