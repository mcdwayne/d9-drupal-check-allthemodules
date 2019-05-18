<?php

namespace Drupal\akamai\Form;

use Drupal\akamai\AkamaiClientManager;
use Drupal\akamai\KeyProviderInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A configuration form to interact with Akamai API settings.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * An array containing currently available client versions.
   *
   * @var \Drupal\akamai\AkamaiClientInterface[]
   */
  protected $availableVersions = [];

  /**
   * A messenger interface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new ConfigForm.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The ConfigFactory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request_stack service.
   * @param \Drupal\akamai\AkamaiClientManager $manager
   *   The Akamai Client plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal messenger service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\akamai\KeyProviderInterface $key_provider
   *   The key provider service.
   */
  public function __construct(ConfigFactory $configFactory, RequestStack $request_stack, AkamaiClientManager $manager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, KeyProviderInterface $key_provider) {
    $this->requestStack = $request_stack;
    $this->keyProvider = $key_provider;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    foreach ($manager->getAvailableVersions() as $id => $definition) {
      $this->availableVersions[$id] = $manager->createInstance($id);
    }
    parent::__construct($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('akamai.client.manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('akamai.key_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'akamai.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'akamai_config_form';
  }

  /**
   * Shows a warning via $this->messenger->addWarning().
   */
  public function httpsWarning() {
    $this->messenger->addWarning($this->t('If you submit this form via HTTP, your API credentials will be sent in clear text and may be intercepted. For information on setting up HTTPs, see <a href="https://www.drupal.org/https-information">Enabling HTTPs</a>.'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->isHttps() === FALSE) {
      $this->httpsWarning();
    }

    $config = $this->config('akamai.settings');

    // Link to instructions on how to get Akamai credentials from Luna.
    $luna_url = 'https://developer.akamai.com/introduction/Prov_Creds.html';
    $luna_uri = Url::fromUri($luna_url);

    $form['akamai_credentials_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Akamai CCU Credentials'),
      '#description' => $this->t('API Credentials for Akamai. Someone with Luna access will need to set this up. See @link for more.', ['@link' => $this->l($luna_url, $luna_uri)]),
    ];

    $options = [
      'file' => $this->t('.edgerc file'),
    ];
    if ($this->moduleHandler->moduleExists('key')) {
      $options['key'] = $this->t('Key module');
    }

    $form['akamai_credentials_fieldset']['storage_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Credential storage method'),
      '#default_value' => $config->get('storage_method') ?: 'file',
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('Credentials may be stored in an .edgerc file or using the Key module (if installed). See the README file for more information.'),
    ];

    $key_field_states = [
      'required' => [
        ':input[name="storage_method"]' => ['value' => 'key'],
      ],
      'visible' => [
        ':input[name="storage_method"]' => ['value' => 'key'],
      ],
      'optional' => [
        ':input[name="storage_method"]' => ['value' => 'file'],
      ],
      'invisible' => [
        ':input[name="storage_method"]' => ['value' => 'file'],
      ],
    ];
    $file_field_states = [
      'required' => [
        ':input[name="storage_method"]' => ['value' => 'file'],
      ],
      'visible' => [
        ':input[name="storage_method"]' => ['value' => 'file'],
      ],
      'optional' => [
        ':input[name="storage_method"]' => ['value' => 'key'],
      ],
      'invisible' => [
        ':input[name="storage_method"]' => ['value' => 'key'],
      ],
    ];

    $form['akamai_credentials_fieldset']['rest_api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('REST API URL'),
      '#description'   => $this->t('The URL of the Akamai CCU API host. It should be in the format *.purge.akamaiapis.net/'),
      '#default_value' => $config->get('rest_api_url'),
      '#states' => $key_field_states,
    ];

    $keys = [];
    if ($this->keyProvider->hasKeyRepository()) {
      foreach ($this->keyProvider->getKeys() as $key) {
        $keys[$key->id()] = $key->label();
      }
    }
    asort($keys);

    $form['akamai_credentials_fieldset']['access_token'] = [
      '#type' => 'select',
      '#title' => $this->t('Access Token'),
      '#description'   => $this->t('Access token.'),
      '#options' => $keys,
      '#default_value' => $config->get('access_token'),
      '#states' => $key_field_states,
    ];

    $form['akamai_credentials_fieldset']['client_token'] = [
      '#type' => 'select',
      '#title' => $this->t('Client Token'),
      '#description'   => $this->t('Client token.'),
      '#options' => $keys,
      '#default_value' => $config->get('client_token'),
      '#states' => $key_field_states,
    ];

    $form['akamai_credentials_fieldset']['client_secret'] = [
      '#type' => 'select',
      '#title' => $this->t('Client Secret'),
      '#description'   => $this->t('Client secret.'),
      '#options' => $keys,
      '#default_value' => $config->get('client_secret'),
      '#states' => $key_field_states,
    ];

    $form['akamai_credentials_fieldset']['edgerc_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to .edgerc file'),
      '#default_value' => $config->get('edgerc_path') ?: '',
      '#states' => $file_field_states,
    ];

    $form['akamai_credentials_fieldset']['edgerc_section'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section of .edgerc file to use for the CCU API'),
      '#default_value' => $config->get('edgerc_section') ?: 'default',
      '#states' => $file_field_states,
    ];

    $form['ccu_version'] = [
      '#type' => 'radios',
      '#title' => $this->t('CCU Version'),
      '#default_value' => $config->get('version') ?: 'v2',
      '#options' => array_map(function ($version) {
        return $version->getPluginDefinition()['title'];
      }, $this->availableVersions),
      '#required' => TRUE,
      '#description' => $this->t('Select which Akamai client version to use.'),
      // '#access' => FALSE, Uncomment in order to alter this value.
    ];

    foreach ($this->availableVersions as $id => $version) {
      $definition = $version->getPluginDefinition();
      $form['akamai_version_settings']['#options'][$id] = $definition['title'];
      $form['akamai_version_settings'][$id] = [
        '#type' => 'details',
        '#title' => $this->t('@version settings', ['@version' => $definition['title']]),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#states' => [
          'visible' => [
            ':radio[name="ccu_version"]' => ['value' => $id],
          ],
        ],
      ];
      $form['akamai_version_settings'][$id] += $version->buildConfigurationForm([], $form_state);
    }

    global $base_url;
    $basepath = $config->get('basepath') ?: $base_url;

    $form['basepath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base Path'),
      '#default_value' => $basepath,
      '#description' => $this->t('The URL of the base path (fully qualified domain name) of the site.  This will be used as a prefix for all cache clears (Akamai indexes on the full URI). e.g. "http://www.example.com"'),
      '#required' => TRUE,
    ];

    $form['timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout Length'),
      '#description' => $this->t("The timeout in seconds used when sending the cache clear request to Akamai's servers. Most users will not need to change this value."),
      '#size' => 5,
      '#maxlength' => 3,
      '#default_value' => $config->get('timeout'),
      '#required' => TRUE,
    ];

    $form['domain'] = [
      '#type' => 'select',
      '#title' => $this->t('Domain'),
      '#default_value' => $this->getMappingKey($config->get('domain')),
      '#options' => [
        'production' => $this->t('Production'),
        'staging' => $this->t('Staging'),
      ],
      '#description' => $this->t('The Akamai domain to use for cache clearing.'),
      '#required' => TRUE,
    ];

    $form['status_expire'] = [
      '#type' => 'textfield',
      '#title' => t('Purge Status expiry'),
      '#default_value' => $config->get('status_expire'),
      '#description' => $this->t('This module keeps a log of purge statuses. They are automatically deleted after this amount of time (in seconds).'),
      '#size' => 12,
    ];

    $form['edge_cache_tag_header_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Edge-Cache-Tag Header Settings'),
    ];

    $form['edge_cache_tag_header_fieldset']['edge_cache_tag_header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Edge-Cache-Tag Header'),
      '#default_value' => $config->get('edge_cache_tag_header'),
      '#description' => $this->t('Sends Edge-Cache-Tag header in responses for Akamai'),
    ];

    $form['edge_cache_tag_header_fieldset']['edge_cache_tag_header_blacklist'] = [
      '#type' => 'textarea',
      '#title' => t('Cache Tag Blacklist'),
      '#default_value' => $config->get('edge_cache_tag_header_blacklist'),
      '#description' => $this->t('List of tag prefixes to blacklist from the Edge-Cache-Tag header. One per line.'),
      '#pre_render' => [[$this, 'implodeElement']],
    ];

    $form['devel_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Development Options'),
    ];

    $form['devel_fieldset']['disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable all calls to Akamai'),
      '#default_value' => $config->get('disabled'),
      '#description' => $this->t('Killswitch - disable Akamai cache clearing entirely.'),
    ];

    $form['devel_fieldset']['log_requests'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log requests'),
      '#default_value' => $config->get('log_requests'),
      '#description' => $this->t('Log all requests and responses.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $int_fields = ['timeout', 'status_expire'];
    foreach ($int_fields as $field) {
      if (!ctype_digit($form_state->getValue($field))) {
        $form_state->setErrorByName($field, $this->t('Please enter only integer values in this field.'));
      }
    }

    // Call the form validation handler for each of the versions.
    foreach ($this->availableVersions as $version) {
      $version->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitform(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $blacklist = trim($values['edge_cache_tag_header_blacklist']);
    $blacklist = !empty($blacklist) ? array_map('trim', explode(PHP_EOL, $blacklist)) : [];

    $this->config('akamai.settings')
      ->set('version', $values['ccu_version'])
      ->set('rest_api_url', $values['rest_api_url'])
      ->set('storage_method', $values['storage_method'])
      ->set('client_token', $values['client_token'])
      ->set('client_secret', $values['client_secret'])
      ->set('access_token', $values['access_token'])
      ->set('edgerc_path', $values['edgerc_path'])
      ->set('edgerc_section', $values['edgerc_section'])
      ->set('basepath', $values['basepath'])
      ->set('timeout', $values['timeout'])
      ->set('status_expire', $values['status_expire'])
      ->set('domain', $this->saveDomain($values['domain']))
      ->set('log_requests', $values['log_requests'])
      ->set('edge_cache_tag_header', $values['edge_cache_tag_header'])
      ->set('edge_cache_tag_header_blacklist', $blacklist)
      ->set('disabled', $values['disabled'])
      ->save();

    // Call the form submit handler for each of the versions.
    foreach ($this->availableVersions as $version) {
      $version->submitConfigurationForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);

    if ($values['rest_api_url'] !== 'https://xxxx-xxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxx.luna.akamaiapis.net/') {
      $this->checkCredentials();
    }
    else {
      \Drupal::service('messenger')->addWarning($this->t('You need to provide non-default credentials for this module to work.'));
    }

    $this->messenger->addMessage($this->t('Settings saved.'));
  }

  /**
   * Ensures credentials supplied actually work.
   */
  protected function checkCredentials() {
    $client = \Drupal::service('akamai.client.factory')->get();
    if ($client->isAuthorized()) {
      $this->messenger->addMessage('Authenticated to Akamai.');
    }
    else {
      $this->messenger->addError('Akamai authentication failed.');
    }
  }

  /**
   * Return the key of the active selection in a domain mapping.
   *
   * @param array $array
   *   A settings array corresponding to a mapping with booleans against keys.
   *
   * @return mixed
   *   The key of the first value with boolean TRUE.
   */
  protected function getMappingKey(array $array) {
    return key(array_filter($array));
  }

  /**
   * Converts a form value for 'domain' back to a saveable array.
   *
   * @param string $value
   *   The value submitted via the form.
   *
   * @return array
   *   An array suitable for saving back to config.
   */
  protected function saveDomain($value) {
    $domain = [
      'production' => FALSE,
      'staging' => FALSE,
    ];

    $domain[$value] = TRUE;
    return $domain;
  }

  /**
   * Checks that the form is being accessed over HTTPs.
   *
   * @return bool
   *   TRUE if page was requested via HTTPs, FALSE if not.
   */
  protected function isHttps() {
    $request = $this->requestStack->getCurrentRequest();
    return $request->getScheme() === 'https';
  }

  /**
   * Implodes an array using PHP_EOL.
   */
  public function implodeElement(array $element) {
    $element['#value'] = !empty($element['#value']) ? implode(PHP_EOL, $element['#value']) : '';
    return $element;
  }

}
