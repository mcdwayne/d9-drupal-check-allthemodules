<?php
/**
 * @file
 * Contains \Drupal\wunderground_weather\Form\wunderground_weatherSettingsForm.
 */

namespace Drupal\wunderground_weather\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to configure module settings.
 */
class WundergroundWeatherSettingsForm extends ConfigFormBase {
  /**
   * Defines the interface for a configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Decorator for the URL generator, which bubbles bubbleable URL metadata.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $linkGenerator;
  /**
   * Class responsible for providing language support on language-unaware sites.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  private $languageManager;

  /**
   * WundergroundWeatherSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Defines the interface for a configuration object factory.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   Class for providing language support on language-unaware sites.
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   *   Decorator for the URL generator, which bubbles bubbleable URL metadata.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManager $language_manager, LinkGenerator $link_generator) {
    parent::__construct($config_factory);

    $this->setConfigFactory($config_factory);
    $this->linkGenerator = $link_generator;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    /** @var \Drupal\Core\Language\LanguageManager $language_manager */
    $language_manager = $container->get('language_manager');
    /** @var \Drupal\Core\Utility\LinkGenerator $link_generator */
    $link_generator = $container->get('link_generator');

    return new static($config_factory, $language_manager, $link_generator);
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wunderground_weather_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wunderground_weather.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all settings.
    $config = $this->configFactory->get('wunderground_weather.settings');

    $form['settings'] = [
      '#tree' => TRUE,
    ];

    // Link to get the api key.
    $api_url = 'http://www.wunderground.com/weather/api';
    $url = Url::fromUri($api_url);
    $wg_link = $this->linkGenerator->generate($api_url, $url);

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('Wunderground API key'),
      '#description' => t('Get your API key at @url', ['@url' => $wg_link]),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    $form['language'] = [
      '#type' => 'select',
      '#title' => t('Language'),
      '#options' => $this->getLanguages(),
      '#default_value' => $config->get('language'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('wunderground_weather.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('language', $form_state->getValue('language'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get the languages used on the current site.
   *
   * @return array
   *   An array of languages to be used in a select element.
   */
  protected function getLanguages() {
    $languages = $this->languageManager->getLanguages();
    $options_array = [];
    /** @var \Drupal\Core\Language\Language $language */
    foreach ($languages as $language) {
      $options_array[$language->getId()] = $language->getName();
    }

    return $options_array;
  }

}
