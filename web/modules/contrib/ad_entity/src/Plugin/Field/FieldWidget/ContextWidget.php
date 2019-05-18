<?php

namespace Drupal\ad_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ad_entity\Form\AdContextElementBuilder;

/**
 * Plugin implementation of the 'ad_entity_context' field widget.
 *
 * @FieldWidget(
 *   id = "ad_entity_context",
 *   label = @Translation("Advertising context"),
 *   field_types = {
 *     "ad_entity_context"
 *   }
 * )
 */
class ContextWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The context form element builder.
   *
   * @var \Drupal\ad_entity\Form\AdContextElementBuilder
   */
  protected $elementBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $context_element_builder = AdContextElementBuilder::create($container);
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $context_element_builder
    );
  }

  /**
   * Constructs an AdContextWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\ad_entity\Form\AdContextElementBuilder $context_element_builder
   *   The context element builder.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AdContextElementBuilder $context_element_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->elementBuilder = $context_element_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'context_plugin_id' => NULL,
      'apply_on' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $context_item = $items->get($delta)->get('context');
    $value_plugin_id = $context_item->get('context_plugin_id')->getValue();
    $value_settings = $context_item->get('context_settings')->getValue();
    $value_apply_on = $context_item->get('apply_on')->getValue();

    $this->elementBuilder->clearValues()
      ->setContextPluginValue($value_plugin_id)
      ->setContextApplyOnValue($value_apply_on);
    if (!empty($value_settings)) {
      foreach ($value_settings as $plugin_id => $settings) {
        $this->elementBuilder->setContextSettingsValue($plugin_id, $settings);
      }
    }

    return $this->elementBuilder->buildElement($element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $index => &$value) {
      if (empty($value['context']['context_plugin_id'])) {
        // Remove the whole field value in case no context was chosen.
        unset($values[$index]);
      }
      else {
        // Let the element builder massage the form values.
        $value = $this->elementBuilder->massageFormValues($value);
      }
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

}
