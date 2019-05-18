<?php

namespace Drupal\migrate_override\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate_override\OverrideManagerServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'override_widget_default' widget.
 *
 * @FieldWidget(
 *   id = "override_widget_default",
 *   label = @Translation("Migrate override widget"),
 *   field_types = {
 *     "migrate_override_field_item"
 *   }
 * )
 */
class MigrateOverrideWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The override manager service.
   *
   * @var \Drupal\migrate_override\OverrideManagerServiceInterface
   */
  protected $overrideManager;

  /**
   * Constructs a MigrateOverrideWidget object.
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
   * @param \Drupal\migrate_override\OverrideManagerServiceInterface $override_manager
   *   The override manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, OverrideManagerServiceInterface $override_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->overrideManager = $override_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('migrate_override.override_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_id = $entity->id() ? ('.' . $entity->id()) : '';
    $value = $items[$delta]->value;
    if (!is_array($value)) {
      $value = unserialize($value);
    }
    $options = $this->overrideManager->getOverridableEntityFields($entity);
    $default_value = [];
    foreach (array_keys($options) as $field_name) {
      if (isset($value[$field_name]) && $value[$field_name] === OverrideManagerServiceInterface::ENTITY_FIELD_OVERRIDDEN) {
        $default_value[] = $field_name;
      }
    }
    $element['value'] = $element + [
      '#type' => 'checkboxes',
      '#options' => $this->overrideManager->getOverridableEntityFields($entity),
      '#attributes' => [
        'migrate_override_identifier' => "{$type}.{$bundle}{$entity_id}",
      ],
      '#default_value' => $default_value,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    $return = [];
    foreach ($values as $delta => $value) {
      $data = [];
      foreach ($value['value'] as $field_name => $field_value) {
        $data[$field_name] = (empty($field_value) ? OverrideManagerServiceInterface::ENTITY_FIELD_LOCKED : OverrideManagerServiceInterface::ENTITY_FIELD_OVERRIDDEN);
      }
      $return[$delta]['value'] = serialize($data);
    }
    return $return;
  }

}
