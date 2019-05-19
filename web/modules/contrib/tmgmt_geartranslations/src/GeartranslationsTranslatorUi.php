<?php
/**
 * @file
 * Contains \Drupal\tmgmt_geartranslations\GeartranslationsTranslatorUi.
 */

namespace Drupal\tmgmt_geartranslations;

use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tmgmt_geartranslations\Geartranslations\APIConnector;

/**
 * GearTranslations translator UI.
 */
class GeartranslationsTranslatorUi extends TranslatorPluginUiBase {
  use StringTranslationTrait;

  /**
   * GearTranslations default settings file identifier
   * @var string
   */
  const SETTINGS_NAME = 'tmgmt_geartranslations.settings';

  /**
   * GearTranslations API wrapper
   * @var API
   */
  private $api;

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    $translator = $form_state->getFormObject()->getEntity();

    if (!$this->prepareAPI($translator)) {
      return parent::checkoutSettingsForm($form, $form_state, $job);
    }

    $packages = $this->api->getTranslationPackages(
      $job->getRemoteSourceLanguage(),
      $job->getRemoteTargetLanguage()
    );

    $form['package'] = [
      '#type' => 'select',
      '#title' => t('Translation package'),
      '#description' => t('Select a translation level for your translation request.'),
      '#options' => $packages['packages'],
      '#default_value' => $packages['default']
    ];

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => t('Remember that you can access into our platform to manage your profile:
        <ul>
          <li><a href="@monthly_expenses" target="_blank">Monthly expenses</a></li>
          <li><a href="@translation_library" target="_blank">Translation library</a></li>
        </ul>',
        $this->api->profileLinks()
      ),
    ];

    return parent::checkoutSettingsForm($form, $form_state, $job);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $translator = $form_state->getFormObject()->getEntity();

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('GearTranslations API endpoint'),
      '#required' => TRUE,
      '#default_value' => $this->fetchSetting($translator, 'endpoint'),
      '#description' => t('Please enter the URL endpoint provided by GearTranslations')
    ];
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => t('Access token'),
      '#required' => TRUE,
      '#default_value' => $this->fetchSetting($translator, 'token'),
      '#description' => t('Please enter your GearTranslations access token')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $translator = $form_state->getFormObject()->getEntity();

    try {
      $this->prepareAPI($translator);
      if (!$this->isAPIReady()) {
        throw new \Exception('Invalid API configuration');
      }

      $this->api->ping();
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('settings][endpoint', t('Invalid API configuration.'));
      $form_state->setErrorByName('settings][token', t('Invalid API configuration.'));
    }
  }

  /**
   * Instanciate GearTranslations API if needed.
   *
   * @param many $translator Translator wrapper.
   */
  private function prepareAPI($translator) {
    if (!APIConnector::isConfigured($translator)) {
      return false;
    }

    if (!$this->isAPIReady()) {
      $this->api = APIConnector::build($translator);
    }

    return true;
  }

  /**
   * Check if API connector is ready.
   *
   * @return boolean TRUE if the API is ready; FALSE otherwise.
   */
  private function isAPIReady() {
    return isset($this->api);
  }

  /**
   * Fetch a configuration setting by searching first on the stored value, and then on the module
   * defaults settings file `config/install/tmgmt_geartranslations.settings.yml`.
   *
   * @param many $translator Translator wrapper.
   * @return many Configuration value.
   */
  private function fetchSetting($translator, $key) {
    $value = $translator->getSetting($key);
    if ($value) {
      return $value;
    }

    $defaults = \Drupal::configFactory()->get(self::SETTINGS_NAME);
    return $defaults->get($key);
  }
}
