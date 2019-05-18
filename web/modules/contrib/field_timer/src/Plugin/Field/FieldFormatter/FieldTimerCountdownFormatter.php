<?php

namespace Drupal\field_timer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'field_timer_countdown' formatter.
 *
 * @FieldFormatter(
 *   id = "field_timer_countdown",
 *   label = @Translation("jQuery Countdown"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class FieldTimerCountdownFormatter extends FieldTimerCountdownFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Provides language.default service.
   *
   * {@inheritdoc}
   */
  const JS_KEY = 'jquery.countdown';

  /**
   * Provides language.default service.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $languageDefault;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LanguageDefault $languageDefault) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->languageDefault = $languageDefault;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('language.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'use_system_language' => FALSE,
      'regional' => 'en',
      'format' => 'dHMS',
      'layout' => '',
      'compact' => 0,
      'significant' => 0,
      'timeSeparator' => ':',
      'padZeroes' => 0,
    ] + parent::defaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $language = $this->getLanguage($langcode);
    if ($language != 'en') {
      $elements['#attached']['library'][] = 'field_timer/' . static::LIBRARY_NAME . '.' . $language;
    }

    $keys = $this->getItemKeys($items);

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => '<span class="field-timer-jquery-countdown" data-field-timer-key="'
        . $keys[$delta] . '" data-timestamp="' . $this->getTimestamp($item) . '"></span>',
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $useSystemLanguageDescription = $this->t("If this option is checked, it will try to '
      . 'use appropriate translation from internal files and fallback to the '
      . 'site's default language or English if nothing is found. Otherwise it '
      . 'provides option 'Region' to configure which translation to use for '
      . 'each language on the site.");

    $form['use_system_language'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use system language'),
      '#default_value' => $this->getSetting('use_system_language'),
      '#description' => $useSystemLanguageDescription,
      '#attributes' => ['class' => ['field-timer-use-system-language']],
    ];

    $form['regional'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#default_value' => $this->getSetting('regional'),
      '#options' => $this->languageOptions(),
      '#states' => [
        'invisible' => [
          'input.field-timer-use-system-language' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format'),
      '#default_value' => $this->getSetting('format'),
      '#description' => $this->t('See <a href=":url" target="_blank">documentation</a> for this parameter.', [
        ':url' => $this->getDocumentationLink(['fragment' => 'format']),
      ]),
    ];

    $form['layout'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#cols' => 60,
      '#title' => $this->t('Layout'),
      '#default_value' => $this->getSetting('layout'),
      '#description' => $this->t('See <a href=":url" target="_blank">documentation</a> for this parameter.', [
        ':url' => $this->getDocumentationLink(['fragment' => 'layout']),
      ]),
    ];

    $form['compact'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display in compact format'),
      '#default_value' => $this->getSetting('compact'),
    ];

    $form['significant'] = [
      '#type' => 'select',
      '#title' => $this->t('Granularity'),
      '#options' => range(0, 7),
      '#default_value' => $this->getSetting('significant'),
    ];

    $form['timeSeparator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time separator'),
      '#default_value' => $this->getSetting('timeSeparator'),
    ];

    $form['padZeroes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pad with zeroes'),
      '#default_value' => $this->getSetting('padZeroes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $useSystemLanguage = $this->getSetting('use_system_language');
    $summary[] = $this->t('Use system language: %use_system_language', ['%use_system_language' => $useSystemLanguage ? $this->t('Yes') : $this->t('No')]);
    if (!$useSystemLanguage) {
      $language = $this->getSetting('regional');
      $summary[] = $this->t('Language: %language', ['%language' => $this->languageOptions()[$language]]);
    }
    $summary[] = $this->t('Format: %format', ['%format' => $this->getSetting('format')]);
    $summary[] = $this->t('Layout: %layout', ['%layout' => $this->getSetting('layout')]);
    $summary[] = $this->t('Compact: %compact', ['%compact' => $this->getSetting('compact') ? $this->t('Yes') : $this->t('No')]);
    $summary[] = $this->t('Granularity: %significant', ['%significant' => $this->getSetting('significant')]);
    $summary[] = $this->t('Time separator: %timeSeparator', ['%timeSeparator' => $this->getSetting('timeSeparator')]);
    $summary[] = $this->t('Pad with zeroes: %padZeroes', ['%padZeroes' => $this->getSetting('padZeroes') ? $this->t('Yes') : $this->t('No')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function preparePluginSettings(FieldItemInterface $item, $langcode) {
    $settings = parent::preparePluginSettings($item, $langcode);

    unset($settings['use_system_language']);
    $settings['regional'] = $this->getLanguage($langcode);

    return $settings;
  }

  /**
   * Gets language to use for jquery.countdown.
   *
   * @param string $langcode
   *   Language code.
   *
   * @return string
   *   Language code.
   */
  protected function getLanguage($langcode) {
    // Fallback to English.
    $language = 'en';
    if ($this->getSetting('use_system_language')) {
      $languages = $this->languageOptions();
      // Try content language.
      if (isset($languages[$langcode])) {
        $language = $langcode;
      }
      else {
        $defaultLangcode = $this->languageDefault->get()
          ->getId();
        // Try default language.
        if (isset($languages[$defaultLangcode])) {
          $language = $defaultLangcode;
        }
      }
    }
    else {
      $language = $this->getSetting('regional');
    }

    return $language;
  }

  /**
   * Gets language options.
   *
   * @return array
   *   Array of language options.
   */
  protected function languageOptions() {
    return [
      'sq' => t('Albanian'),
      'ar' => t('Arabic'),
      'hy' => t('Armenian'),
      'bn' => t('Bengali/Bangla'),
      'bs' => t('Bosnian'),
      'bg' => t('Bulgarian'),
      'my' => t('Burmese'),
      'ca' => t('Catalan'),
      'zh-CN' => t('Chinese/Simplified'),
      'zh-TW' => t('Chinese/Traditional'),
      'hr' => t('Croatian'),
      'cs' => t('Czech'),
      'da' => t('Danish'),
      'nl' => t('Dutch'),
      'et' => t('Estonian'),
      'en' => t('English'),
      'fa' => t('Farsi/Persian'),
      'fi' => t('Finnish'),
      'fo' => t('Faroese'),
      'fr' => t('French'),
      'gl' => t('Galician'),
      'de' => t('German'),
      'el' => t('Greek'),
      'gu' => t('Gujarati'),
      'he' => t('Hebrew'),
      'hu' => t('Hungarian'),
      'id' => t('Indonesian'),
      'is' => t('Icelandic'),
      'it' => t('Italian'),
      'ja' => t('Japanese'),
      'kn' => t('Kannada'),
      'ko' => t('Korean'),
      'lv' => t('Latvian'),
      'lt' => t('Lithuanian'),
      'mk' => t('Macedonian'),
      'ml' => t('Malayalam'),
      'ms' => t('Malaysian'),
      'nb' => t('Norvegian'),
      'pl' => t('Polish'),
      'pt-BR' => t('Portuguese/Brazilian'),
      'ro' => t('Romanian'),
      'ru' => t('Russian'),
      'sr' => t('Serbian'),
      'sk' => t('Slovak'),
      'sl' => t('Slovenian'),
      'es' => t('Spanish'),
      'sv' => t('Swedish'),
      'th' => t('Thai'),
      'tr' => t('Turkish'),
      'uk' => t('Ukrainian'),
      'ur' => t('Urdu'),
      'uz' => t('Uzbek'),
      'vi' => t('Vietnamese'),
      'cy' => t('Welsh'),
    ];
  }

}
