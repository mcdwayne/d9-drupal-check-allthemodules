<?php

/**
 * @file
 * Contains \Drupal\viewmode_field\Plugin\Field\FieldWidget\ViewModeWidget.
 */

namespace Drupal\viewmode_field\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'view_mode' widget.
 *
 * @FieldWidget(
 *   id = "view_mode",
 *   label = @Translation("View mode"),
 *   field_types = {
 *     "view_mode"
 *   }
 * )
 */
class ViewModeWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * ViewModeWidget constructor.
   *
   * @param array $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'view_mode' => 'full',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $options = $this->getViewModes($form_state);
    $entity = $items->getEntity();
    $default = $entity->field_viewmode[0]->getValue();

    $element['view_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $options,
      '#default_value' => $default,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    );

    return $element;
  }

  /**
   * Returns allowed view modes for this widget.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array The allowed view modes.
   * The allowed view modes.
   */
  protected function getViewModes(FormStateInterface $form_state) {
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();

    $view_modes = $this->entityTypeManager->getStorage('entity_view_mode')
      ->loadMultiple();

    $options = [];
    array_walk(
      $view_modes, function ($item, $key) use ($entity_type, &$options) {
        if ($entity_type === $item->getTargetType()) {
          $item = $item->label();
          $key = substr($key, strlen($entity_type) + 1);
          $options[$key] = $item;
        }
      }
    );

    return $options;
  }

}
