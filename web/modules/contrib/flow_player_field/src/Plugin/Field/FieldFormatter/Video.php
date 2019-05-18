<?php

namespace Drupal\flow_player_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\flow_player_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the video field formatter.
 *
 * @FieldFormatter(
 *   id = "flow_player_field_video",
 *   label = @Translation("Video"),
 *   field_types = {
 *     "flow_player_field"
 *   }
 * )
 */
class Video extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\flow_player_field\ProviderManagerInterface
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
   * @param \Drupal\flow_player_field\ProviderManagerInterface $provider_manager
   *   The Flow Player provider manager.
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('flow_player_field.provider_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $provider = $this->providerManager->loadProviderFromInput($item->value);

      if (!$provider) {
        $element[$delta] = ['#theme' => 'flow_player_field_missing_provider'];
      }
      else {
        $autoplay = FALSE;
        $element[$delta] = $provider->renderEmbedCode($this->getSetting('width'), $this->getSetting('height'), $autoplay);
        $element[$delta]['#cache']['contexts'][] = 'user.permissions';

        $element[$delta] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'flow-player-container',
              Html::cleanCssIdentifier(sprintf('flow-player-field-provider-%s', '')),
            ],
          ],
          'children' => $element[$delta],
        ];
      }

    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return $elements;
  }

  /**
   * Get an instance of the Video field formatter plugin.
   *
   * This is useful because there is a lot of overlap to the configuration and
   * display of a video in a WYSIWYG and configuring a field formatter. We
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
    return \Drupal::service('plugin.manager.field.formatter')
      ->createInstance('flow_player_field_video', [
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
