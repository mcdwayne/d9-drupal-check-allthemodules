<?php

namespace Drupal\carerix_form\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\carerix_form\Form\CRForm;

/**
 * Plugin implementation of the 'carerix_form_default' formatter.
 *
 * @FieldFormatter(
 *   id = "carerix_form_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "carerix_form"
 *   }
 * )
 */
class CarerixFormDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

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
      $container->get('form_builder')
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
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The path validator service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FormBuilderInterface $formBuilder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      // Check Carerix form config.
      $carerixFormId = $item->carerix_form_id ?: NULL;
      $pubId = $item->pub_id ?: NULL;
      // Render carerix form.
      $elements[$delta] = $this->formBuilder->getForm(CRForm::class, $carerixFormId, $pubId);
    }
    return $elements;
  }

}
