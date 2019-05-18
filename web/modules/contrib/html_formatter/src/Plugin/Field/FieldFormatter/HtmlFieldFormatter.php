<?php

namespace Drupal\html_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\html_formatter\Plugin\HtmlFormatterTrait;

/**
 * Plugin implementation of the 'html_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "html_field_formatter",
 *   label = @Translation("Html field formatter"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string"
 *   },
 * )
 */
class HtmlFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use HtmlFormatterTrait;

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
      $container->get('path.validator')
    );
  }

  /**
   * Constructs a new LinkFormatter.
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
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return HtmlFormatterTrait::getHtmlFormatterDefaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form += $this->getHtmlFormatterSettingsForm();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary = array_merge($summary, $this->getHtmlFormatterSettingsSummary($this->settings));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $url = $this->getEntityUrl($items);

    foreach ($items as $delta => $item) {
      $value = $this->getLinkedValue($this->settings, $item->value, $url);

      $elements[$delta] = [
        '#theme' => 'html_formatter',
        '#value' => $value,
        '#tag' => $this->getSetting('tag'),
        '#attributes' => ['class' => [$this->getSetting('class')]],
      ];
    }

    return $elements;
  }

}
