<?php

namespace Drupal\customers_canvas\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity reference ID' formatter.
 *
 * @FieldFormatter(
 *   id = "customers_canvas_builder_link",
 *   label = @Translation("Customers Canvas: Show link to the builder"),
 *   description = @Translation("Display a formatted link to the builder that
 *   uses the JSON in the field."),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class BuilderLinkFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Proxy for the current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a BuilderLinkFormatter instance.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Adds the current user service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountProxyInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

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
      $container->get('current_user')
    );
  }

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem $item */
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $item->getEntity();
      $elements[$delta] = [
        '#title' => $this->getSetting('customers_canvas_link_label'),
        '#type' => 'link',
        '#url' => Url::fromRoute('customers_canvas.builder', [
          'cc_entity' => $entity->id(),
          'user' => $this->currentUser->id(),
        ]),
        '#options' => [
          'attributes' => [
            'class' => [$this->getSetting('customers_canvas_link_class')],
          ],
        ],
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'customers_canvas_link_label' => 'Customize',
      'customers_canvas_link_class' => 'button',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['customers_canvas_link_label'] = [
      '#title' => t('Link Label'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('customers_canvas_link_label'),
    ];
    $elements['customers_canvas_link_class'] = [
      '#title' => t('Link Class'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('customers_canvas_link_class'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Label: @label<br>Class: @class', [
      '@label' => $this->getSetting('customers_canvas_link_label'),
      '@class' => $this->getSetting('customers_canvas_link_class'),
    ]);
    return $summary;
  }

}
