<?php

/**
 * @file
 * Contains \Drupal\field_formatters\Plugin\Field\FieldFormatter.
 */

namespace Drupal\field_formatters\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field_formatters\ConvertSlugInterface;

/**
 * Plugin implementation of the 'slugify' formatter.
 *
 * @FieldFormatter(
 *   id = "slugify",
 *   label = @Translation("Convert into a slug"),
 *   field_types = {
 *     "string", "list_string", "text_with_summary", "text_long",
       "string_long", "text"
 *   }
 * )
 */

class FormatterSlugify extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The Slug service
   *
   * @var \Drupal\field_formatters\ConvertSlugInterface
   */
  protected $convertSlug;

  /**
   * Construct a Slugify object
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
   * @param \Drupal\field_formatters\ConvertSlugInterface $convertSlug
   *   Allow to tonvert a text into slug.
   */

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConvertSlugInterface $convertSlug) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->convertSlug = $convertSlug;
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
      $container->get('convert.slug')
    );
  }

    /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => '_',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['separator'] = [
      '#type' => 'textfield',
      '#title' => t('Specify the separator for the text'),
      '#size' => 5,
      '#default_value' => $this->getSetting('separator'),
     ];

     return $elements;
  }

    /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $separator = $this->getSetting('separator');
    $summary[] = t('The separator is: "'.$separator.'"');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $separator = $this->getSetting('separator');

    foreach ($items as $delta => $item) {
      // Convert the text into a slug.
      $converted_text = $this->convertSlug->textIntoSlugSeparator($item->value, $separator);

      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $converted_text,
      ];
    }

    return $element;
  }

}

