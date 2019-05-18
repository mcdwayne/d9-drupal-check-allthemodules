<?php

/**
 * @file
 * Contains \Drupal\mirador\Plugin\Field\FieldFormatter\MiradorTextFormatter.
 */

namespace Drupal\mirador\Plugin\Field\FieldFormatter;

use Drupal\mirador\ElementAttachmentInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'mirador' formatter.
 *
 * @FieldFormatter(
 *   id = "mirador_text",
 *   module = "mirador",
 *   label = @Translation("Mirador"),
 *   field_types = {
 *     "string",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class MiradorTextFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs an TextFormatter object.
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
   * @param \Drupal\mirador\ElementAttachmentInterface $attachment
   *   Allow the library to be attached to the page.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ElementAttachmentInterface $attachment) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->attachment = $attachment;
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
      $container->get('mirador.attachment')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'mirador_settings' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['mirador_settings'] = array(
      '#title' => t('Mirador Settings'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('mirador_settings'),
      '#description' => t('Please enter the mirador settings in YAML Format, ie key: field_machine_name.
        allowed key values are: label, description, width, height, attribution, author, rights.
        <br>
        Eg: <pre>
          label: title
          description: body
          attribution: title
         </pre>'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    if ($this->getSetting('mirador_settings')) {
      $summary[] = t('Mirador Settings: @style', array('@style' => $this->getSetting('mirador_settings')));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $settings = $this->getSettings();
    $settings['resolver_type'] = 'simple-resolver-identifier';
    foreach ($items as $delta => $item) {
      $item_attributes = $item->_attributes;
      unset($item->_attributes);
      $elements[$delta] = array(
        '#theme' => 'mirador_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#entity' => $items->getEntity(),
        '#settings' => $settings,
      );
    }
    // Attach the Mirado JS and CSS.
    if ($this->attachment->isApplicable()) {
      $this->attachment->attach($elements);
    }
    return $elements;
  }

}
