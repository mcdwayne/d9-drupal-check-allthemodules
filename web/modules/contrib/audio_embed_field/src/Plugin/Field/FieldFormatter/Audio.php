<?php

namespace Drupal\audio_embed_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\audio_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the audio field formatter.
 *
 * @FieldFormatter(
 *   id = "audio_embed_field_audio",
 *   label = @Translation("Audio"),
 *   field_types = {
 *     "audio_embed_field"
 *   }
 * )
 */
class Audio extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\audio_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new instance of the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\audio_embed_field\ProviderManagerInterface $provider_manager
   *   The audio embed provider manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The logged in user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ProviderManagerInterface $provider_manager, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->providerManager = $provider_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $provider = $this->providerManager->loadProviderFromInput($item->value);

      $autoplay = $this->currentUser->hasPermission('never autoplay audio') ? FALSE : $this->getSetting('autoplay');

      $element[$delta] = [];
      if (is_object($provider)) {
        $element[$delta] = $provider->renderEmbedCode($this->getSetting('width'), $this->getSetting('height'), $autoplay);
        $element[$delta]['#cache']['contexts'][] = 'user.permissions';

        // For responsive audio, wrap each field item in it's own container.
        if ($this->getSetting('responsive')) {
          $element[$delta] = [
            '#type' => 'container',
            '#attached' => ['library' => ['audio_embed_field/responsive-audio']],
            '#attributes' => ['class' => ['audio-embed-field-responsive-audio']],
            'children' => $element[$delta],
          ];
        }
      }
      else {
        $element[$delta] = [
          '#type' => 'markup',
          '#markup' => $this->t('Please specify possible providers for the audio embed field.'),
        ];
      }

    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive' => TRUE,
      'width' => '854',
      'height' => '250',
      'autoplay' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['autoplay'] = [
      '#title' => t('Autoplay'),
      '#type' => 'checkbox',
      '#description' => $this->t('Autoplay the audio for users without the "never autoplay audio" permission. Roles with this permission will bypass this setting.'),
      '#default_value' => $this->getSetting('autoplay'),
    ];
    $elements['responsive'] = [
      '#title' => t('Responsive Audio'),
      '#type' => 'checkbox',
      '#description' => $this->t("Make the audio fill the width of it's container, adjusting to the size of the user's screen."),
      '#default_value' => $this->getSetting('responsive'),
    ];
    // Loosely match the name attribute so forms which don't have a field
    // formatter structure (such as the WYSIWYG settings form) are also matched.
    $responsive_checked_state = [
      'visible' => [
        [
          ':input[name*="responsive"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $elements['width'] = [
      '#title' => t('Width'),
      '#type' => 'number',
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
      '#size' => 20,
      '#states' => $responsive_checked_state,
    ];
    $elements['height'] = [
      '#title' => t('Height'),
      '#type' => 'number',
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
      '#size' => 20,
      '#states' => $responsive_checked_state,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $dimensions = $this->getSetting('responsive') ? $this->t('Responsive') : $this->t('@widthx@height', ['@width' => $this->getSetting('width'), '@height' => $this->getSetting('height')]);
    $summary[] = t('Embedded Audio (@dimensions@autoplay).', [
      '@dimensions' => $dimensions,
      '@autoplay' => $this->getSetting('autoplay') ? t(', autoplaying') : '',
    ]);
    return $summary;
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
      $container->get('audio_embed_field.provider_manager'),
      $container->get('current_user')
    );
  }

  /**
   * Get an instance of the Audio field formatter plugin.
   *
   * This is useful because there is a lot of overlap to the configuration and
   * display of audio in a WYSIWYG and configuring a field formatter. We
   * get an instance of the plugin with our own WYSIWYG settings shimmed in,
   * as well as a fake field_definition because one in this context doesn't
   * exist. This allows us to reuse aspects such as the form and settings
   * summary for the WYSIWYG integration.
   *
   * @param array $settings
   *   The settings to pass to the plugin.
   *
   * @return static
   *   The formatter plugin.
   */
  public static function mockInstance(array $settings) {
    return \Drupal::service('plugin.manager.field.formatter')->createInstance('audio_embed_field_audio', [
      'settings' => !empty($settings) ? $settings : [],
      'third_party_settings' => [],
      'field_definition' => new FieldConfig([
        'field_name' => 'mock',
        'entity_type' => 'mock',
        'bundle' => 'mock',
      ]),
      'label' => '',
      'view_mode' => '',
    ]);
  }

}
