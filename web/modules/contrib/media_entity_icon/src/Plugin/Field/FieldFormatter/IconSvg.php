<?php

namespace Drupal\media_entity_icon\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\media_entity_icon\SvgManagerInterface;

/**
 * Plugin implementation of the 'SvgIcon SVG' formatter.
 *
 * @FieldFormatter(
 *   id = "icon_svg",
 *   label = @Translation("Icon SVG"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class IconSvg extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * SVG manager service.
   *
   * @var \Drupal\media_entity_icon\SvgManagerInterface
   */
  protected $svgManager;

  /**
   * Constructs a new IconSvg.
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
   * @param \Drupal\media_entity_icon\SvgManagerInterface $svg_manager
   *   The SVG manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, SvgManagerInterface $svg_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->svgManager = $svg_manager;
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
      $container->get('media_entity_icon.manager.svg')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings['wrap'] = FALSE;
    $settings['wrap_classes'] = '';
    $settings['icon_classes'] = '';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['wrap'] = [
      '#title' => $this->t('Add wrapper to icon'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('wrap'),
    ];

    $form['wrap_classes'] = [
      '#title' => $this->t('Wrapper classes'),
      '#type' => 'textfield',
      '#description' => $this->t('List of classes for the wrapper'),
      '#default_value' => $this->getSetting('icon_wrap_classes'),
    ];

    $form['icon_classes'] = [
      '#title' => $this->t('Icon classes'),
      '#type' => 'textfield',
      '#description' => $this->t('List of classes'),
      '#default_value' => $this->getSetting('icon_classes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $wrap = $this->getSetting('wrap');
    $icon_classes = $this->getSetting('icon_classes');
    $icon_wrap_classes = $this->getSetting('icon_wrap_classes');

    if ($wrap) {
      $summary[] = $this->t('Icon Wrapped');
    }

    if ($icon_wrap_classes) {
      $summary[] = $this->t('Wrap classes: @tags', [
        '@tags' => $icon_wrap_classes,
      ]);
    }

    if ($icon_classes) {
      $summary[] = $this->t('Icon classes: @tags', [
        '@tags' => $icon_classes,
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $wrap = $this->getSetting('wrap');
    $wrap_classes = $this->getSetting('wrap_classes');
    $icon_classes = $this->getSetting('icon_classes');

    $wrap_attributes = [
      'class' => explode(' ', $wrap_classes),
    ];
    $wrap_attributes = new Attribute($wrap_attributes);

    /** @var \Drupal\media_entity\MediaInterface $media */
    $media = $items->getEntity();
    /** @var \Drupal\media_entity\MediaTypeInterface $media_type */
    $media_type = $media->getType();
    $source_path = $media_type->getField($media, 'source');
    $source_realpath = $media_type->getField($media, 'source_realpath');

    foreach ($items as $delta => $icon) {
      $icon_size = $this->svgManager->getIconSize($source_realpath, $icon->value);
      $elements[$delta] = [
        '#theme' => 'media_icon_svg_formatter',
        '#icons_path' => $source_path,
        '#wrap' => $wrap,
        '#content' => '',
        '#icon_class' => $icon->value,
        '#wrap_attributes' => $wrap_attributes,
        '#attributes' => [
          'class' => explode(' ', $icon_classes),
        ] + $icon_size,
      ];
      $elements[$delta]['#attributes']['class'][] = $icon->value;
      $elements[$delta]['#wrap_attributes']['class'][] = 'icon';
    }

    return $elements;
  }

}
