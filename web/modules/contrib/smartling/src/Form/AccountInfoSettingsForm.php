<?php

/**
 * @file
 * Contains \Drupal\smartling\Form\AccountInfoSettingsForm.
 */

namespace Drupal\smartling\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\smartling\ApiWrapper\SmartlingApiWrapper;

/**
 * Smartling account settings form.
 */
class AccountInfoSettingsForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Smartling API Wrapper.
   *
   * @var \Drupal\smartling\ApiWrapper\SmartlingApiWrapper
   */
  protected $apiWrapper;

  /**
   * Constructs a AccountInfoSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The state key value store.
   * @param \Drupal\smartling\ApiWrapper\SmartlingApiWrapper
   *   Smartling API Wrapper.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, SmartlingApiWrapper $api_wrapper) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
    $this->apiWrapper = $api_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('smartling.api_wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'smartling.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartling_account_info_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $url = Url::fromRoute('entity.configurable_language.collection');

    $config = $this->config('smartling.settings');
    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#default_value' => $config->get('account_info.api_url'),
      '#size' => 25,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => $this->t('Set api url. Default: @api_url', ['@api_url' => $config->get('account_info.api_url')]),
    ];

    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project Id'),
      '#default_value' => $config->get('account_info.project_id'),
      '#size' => 25,
      '#maxlength' => 25,
      '#required' => TRUE,
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => '',
      '#description' => $this->t('Current key: @key', ['@key' => $this->hideKey($config->get('account_info.key'))]),
      '#size' => 40,
      '#maxlength' => 40,
      '#required' => FALSE,
    ];

    $languages = $this->languageManager->getLanguages();
    // Hide default site language from mapping.
    unset($languages[$this->languageManager->getDefaultLanguage()->getId()]);
    foreach ($languages as $langcode => $language) {
      $languages[$langcode] = $language->getName();
    }
    if (count($languages) < 1) {
      // Display stubs to pass value to submit function.
      $form['enabled_languages'] = [
        '#type' => 'item',
        '#title' => $this->t('Target Locales'),
        '#value' => [],
        '#description' => [
          '#type' => 'link',
          '#title' => $this->t('At least two languages must be enabled. Please change language settings.'),
          '#url' => $url,
        ],
      ];
      $form['language_mappings'] = [
        '#type' => 'value',
        '#value' => [],
      ];
    }
    else {
      $enabled_languages = $config->get('account_info.enabled_languages');
      $form['enabled_languages'] = [
        '#type' => 'checkboxes',
        '#options' => $languages,
        '#title' => $this->t('Enabled languages'),
        '#description' => $this->t('In order to get values for these fields, please visit API section of Smartling dashboard :url', [
          ':url' => 'https://dashboard.smartling.com/settings/api.htm',
        ]),
        '#default_value' => $enabled_languages,
        // Attach library only when any language exists.
        '#attached' => [
          'library' => ['smartling/smartling.admin'],
          'drupalSettings' => ['smartling' => ['checkAllId' => ['#edit-enabled-languages']]],
        ],
      ];

      $form['language_mappings'] = [
        '#tree' => TRUE,
      ];
      foreach (array_keys($languages) as $langcode) {
        $form['language_mappings'][$langcode] = [
          '#type' => 'textfield',
          '#default_value' => $config->get('account_info.language_mappings.' . $langcode),
          '#size' => 6,
          '#maxlength' => 10,
          '#attributes' => [
            'disabled' => !isset($enabled_languages[$langcode]),
          ],
        ];
      }
    }

    $default_language = $this->languageManager->getDefaultLanguage();
    $form['default_language'] = [
      '#type' => 'item',
      '#title' => $this->t('Site default language: %lang_name', [
        '%lang_name' => $default_language->getName(),
      ]),
      '#description' => [
        '#type' => 'link',
        '#title' => $this->t('Change default language'),
        '#url' => $url,
      ],
    ];

    $form['callback_url_use'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Smartling callback: /smartling/callback/%cron_key'),
      // @todo Add description to display full URL.
      '#default_value' => $config->get('account_info.callback_url_use'),
      '#required' => FALSE,
    ];

    $form['auto_authorize_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto authorize content'),
      '#default_value' => $config->get('account_info.auto_authorize_content'),
      '#required' => FALSE,
    ];

    $form['retrieval_type'] = [
      '#type' => 'select',
      '#title' => $this->t('The desired format for download'),
      '#default_value' => $config->get('account_info.retrieval_type'),
      '#options' => [
        'pending' => $this->t('Smartling returns any translations (including non-published translations)'),
        'published' => $this->t('Smartling returns only published/pre-published translations'),
        'pseudo' => $this->t('Smartling returns a modified version of the original text'),
      ],
      '#required' => FALSE,
    ];

    $form['actions']['test_connection'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test connection'),
      '#submit' => ['::testConnection'],
    ];

    // Validate key is saved.
    $form['#validate'] = ['::validateKey'];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('smartling.settings');
    $config
      ->set('account_info.api_url', $form_state->getValue('api_url'))
      ->set('account_info.project_id', $form_state->getValue('project_id'))
      ->set('account_info.callback_url_use', $form_state->getValue('callback_url_use'))
      ->set('account_info.retrieval_type', $form_state->getValue('retrieval_type'))
      ->set('account_info.auto_authorize_content', $form_state->getValue('auto_authorize_content'));

    if ($key = trim($form_state->getValue('key'))) {
      // Do not update existing key if new key missing.
      $config->set('account_info.key', $key);
    }

    $enabled_languages = $form_state->getValue('enabled_languages');
    // Store only enabled languages.
    $config->set('account_info.enabled_languages', array_filter($enabled_languages));

    $language_mappings = $form_state->getValue('language_mappings');
    foreach ($language_mappings as $lang => $enabled) {
      $config->set('account_info.language_mappings.' . $lang, $enabled);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Validates languages before testing connection.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateKey(array &$form, FormStateInterface $form_state) {
    $enabled_languages = $form_state->getValue('enabled_languages');
    // Test only enabled languages.
    $enabled_languages = array_filter($enabled_languages);
    if (!$enabled_languages) {
      $form_state->setErrorByName('enabled_languages', $this->t('At least one language mapping should be enabled.'));
    }
    else {
      $languages = $this->languageManager->getLanguages();
      $language_mappings = $form_state->getValue('language_mappings');
      foreach ($enabled_languages as $enabled_language) {
        if (empty($language_mappings[$enabled_language])) {
          $form_state->setErrorByName('language_mappings][' . $enabled_language, $this->t('Provide a language mapping for %language',[
            '%language' => $languages[$enabled_language]->getName(),
          ]));
        }
      }
      // Save filtered languages.
      $form_state->setValue('enabled_languages', $enabled_languages);
    }
    // Validate key already saved.
    $config = $this->config('smartling.settings');
    if (!$form_state->getValue('key') && ($key = $config->get('account_info.key'))) {
      $form_state->setValue('key', $key);
    }
  }

  /**
   * Checks if site can establish connection with Smartling servers.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function testConnection(array &$form, FormStateInterface $form_state) {
    $enabled_languages = $form_state->getValue('enabled_languages');
    $language_mappings = $form_state->getValue('language_mappings');
    $locales = [];
    // Test only enabled languages.
    foreach ($enabled_languages as $langcode) {
      $locales[$langcode] = $language_mappings[$langcode];
    }
    $connection = $this->apiWrapper->testConnection($locales);
    $languages = $this->languageManager->getLanguages();
    $all_passed = TRUE;
    foreach ($connection as $locale => $langcode) {
      if ($langcode) {
        drupal_set_message($this->t('Test connection for %language was successful.', [
          '%language' => $languages[$langcode]->getName(),
        ]));
      }
      else {
        drupal_set_message($this->t('Test connection for %locale failed.', [
          '%locale' => $locale,
        ]), 'error');
        $all_passed = FALSE;
      }
    }
    if ($all_passed) {
      drupal_set_message($this->t('Settings are not saved. Save settings to apply changes.'), 'warning');
    }
    $form_state->setRebuild();
  }

  /**
   * Hide last 10 characters in string.
   *
   * @param string $key
   *   Smartling key.
   *
   * @return string
   *   Return smartling key without 10 last characters.
   */
  protected function hideKey($key = '') {
    return substr($key, 0, -10) . str_repeat("*", 10);
  }

  /**
   * Check api key.
   *
   * @param string $key
   *   Api key.
   *
   * @return string
   *   Return checked api key.
   */
  protected function apiKeyCheck($key) {
    return preg_match("/^[a-z0-9]{8}(?:-[a-z0-9]{4}){3}-[a-z0-9]{12}$/", $key);
  }

  /**
   * Check project id.
   *
   * @param string $project_id
   *   Project id.
   *
   * @return string
   *   Return checked project id.
   */
  protected function projectIdCheck($project_id) {
    return preg_match("/^[a-z0-9]{9}$/", $project_id);
  }

}
