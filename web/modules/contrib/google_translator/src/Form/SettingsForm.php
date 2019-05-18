<?php

namespace Drupal\google_translator\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The Cache Render.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_render) {
    parent::__construct($config_factory);
    $this->cacheRender = $cache_render;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.render')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_translator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('google_translator.settings');
    $modes = [
      'VERTICAL' => $this->t('Vertical'),
      'HORIZONTAL' => $this->t('Horizontal'),
      'SIMPLE' => $this->t('Compact'),
    ];

    $form['google_translator_active_languages_display_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display Mode'),
      '#description' => $this->t('Control whether content defaults to test content or not'),
      '#options' => $modes,
      '#default_value' => $config->get('google_translator_active_languages_display_mode'),
    ];

    $form['gt_active_languages'] = [
      '#title' => $this->t('Languages configuration. Configure the languages available to your site.'),
      '#type' => 'details',
      '#collapsed' => TRUE,
    ];

    $form['gt_active_languages']['google_translator_active_languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this
        ->t('Available Languages'),
      '#options' => $this
        ->getAvailableLanguages(),
      '#description' => $this
        ->t('Please select specific languages'),
      '#default_value' => $config
        ->get('google_translator_active_languages') ?: [],
    ];

    $form['google_translator_disclaimer'] = [
      '#title' => $this
        ->t('Service disclaimer text'),
      '#type' => 'textarea',
      '#default_value' => $config
        ->get('google_translator_disclaimer'),
      '#description' => $this
        ->t('Optionally require users to accept a disclaimer (in a popup modal on click) before allowing translation. Allowed tags: @tags', [
          '@tags' => implode(', ', Xss::getAdminTagList()),
        ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_translator.settings');

    $config->set('google_translator_active_languages_display_mode', $form_state->getValue('google_translator_active_languages_display_mode'));
    $config->set('google_translator_active_languages', array_values(array_filter($form_state->getValue('google_translator_active_languages'))));
    $config->set('google_translator_disclaimer', $form_state->getValue('google_translator_disclaimer'));

    $config->save();

    $this->cacheRender->invalidateAll();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_translator.settings',
    ];
  }

  /**
   * Returns a list of available languages.
   *
   * @return array
   *   The list of available languages, suitable for being used as options.
   */
  protected function getAvailableLanguages() {
    return [
      'af' => 'Afrikaans',
      'sq' => 'Albanian',
      'ar' => 'Arabic',
      'be' => 'Belarusian',
      'bg' => 'Bulgarian',
      'ca' => 'Catalan',
      'zh-CN' => 'Chinese (Simplified)',
      'zh-TW' => 'Chinese (Traditional)',
      'hr' => 'Croatian',
      'cs' => 'Czech',
      'da' => 'Danish',
      'nl' => 'Dutch',
      'en' => 'English',
      'eo' => 'Esperanto',
      'et' => 'Estonian',
      'tl' => 'Filipino',
      'fi' => 'Finnish',
      'fr' => 'French',
      'gl' => 'Galician',
      'de' => 'German',
      'el' => 'Greek',
      'ht' => 'Haitian Creole',
      'iw' => 'Hebrew',
      'hi' => 'Hindi',
      'hu' => 'Hungarian',
      'is' => 'Icelandic',
      'id' => 'Indonesian',
      'ga' => 'Irish',
      'it' => 'Italian',
      'ja' => 'Japanese',
      'ko' => 'Korean',
      'lv' => 'Latvian',
      'lt' => 'Lithuanian',
      'mk' => 'Macedonian',
      'ms' => 'Malay',
      'mt' => 'Maltese',
      'no' => 'Norwegian',
      'fa' => 'Persian',
      'pl' => 'Polish',
      'pt' => 'Portuguese',
      'ro' => 'Romanian',
      'ru' => 'Russian',
      'sr' => 'Serbian',
      'sk' => 'Slovak',
      'sl' => 'Slovenian',
      'es' => 'Spanish',
      'sw' => 'Swahili',
      'sv' => 'Swedish',
      'th' => 'Thai',
      'tr' => 'Turkish',
      'uk' => 'Ukrainian',
      'vi' => 'Vietnamese',
      'cy' => 'Welsh',
      'yi' => 'Yiddish',
    ];
  }

}
