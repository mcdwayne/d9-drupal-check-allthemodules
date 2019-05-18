<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\flexiform\FormComponent\FormComponentTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for extra field components.
 *
 * @FormComponentType(
 *   id = "extra_field",
 *   label = @Translation("Extra Field"),
 *   component_class = "Drupal\flexiform\Plugin\FormComponentType\ExtraFieldComponent",
 * )
 */
class ExtraFieldComponentType extends FormComponentTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager')
    );
  }

  /**
   * Construct a new FieldWidgetComponentType object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponent($name, array $options = []) {
    $component = parent::getComponent($name, $options);
    $component->setExtraField($this->getExtraField($name));
    return $component;
  }

  /**
   * {@inheritdoc}
   */
  public function componentRows(EntityDisplayFormBase $form_object, array $form, FormStateInterface $form_state) {
    $rows = [];
    foreach ($this->getExtraFields() as $component_name => $extra_field) {
      $rows[$component_name] = $this->buildComponentRow($form_object, $component_name, $form, $form_state);
    }
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function submitComponentRow($component_name, $values, array $form, FormStateInterface $form_state) {
    $options = $this->getFormDisplay()->getComponent($component_name);
    $options['weight'] = $values['weight'];
    $options['region'] = $values['region'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentRow(EntityDisplayFormBase $form_object, $component_name, array $form, FormStateInterface $form_state) {
    $display_options = $this->getFormDisplay()->getComponent($component_name);
    $extra_field = $this->getExtraField($component_name);
    $regions = array_keys($form_object->getRegions());

    $extra_field_row = [
      '#attributes' => [
        'class' => [
          'draggable',
          'tabledrag-leaf',
        ],
      ],
      '#row_type' => 'extra_field',
      '#region_callback' => [
        $form_object,
        'getRowRegion',
      ],
      '#js_settings' => [
        'rowHandler' => 'field',
      ],
      'human_name' => [
        '#markup' => $extra_field['label'],
      ],
      'weight' => [
        '#type' => 'textfield',
        '#title' => t('Weight for @title', [
          '@title' => $extra_field['label'],
        ]),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : 0,
        '#size' => 3,
        '#attributes' => [
          'class' => [
            'field-weight',
          ],
        ],
      ],
      'parent_wrapper' => [
        'parent' => [
          '#type' => 'select',
          '#title' => t('Parents for @title', [
            '@title' => $extra_field['label'],
          ]),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#empty_value' => '',
          '#attributes' => [
            'class' => [
              'js-field-parent',
              'field-parent',
            ],
          ],
          '#parents' => [
            'fields',
            $component_name,
            'parent',
          ],
        ],
        'hidden_name' => [
          '#type' => 'hidden',
          '#default_value' => $component_name,
          '#attributes' => [
            'class' => [
              'field-name',
            ],
          ],
        ],
      ],
      'region' => [
        '#type' => 'select',
        '#title' => t('Region for @title', [
          '@title' => $extra_field['label'],
        ]),
        '#title_display' => 'invisible',
        '#options' => $form_object->getRegionOptions(),
        '#default_value' => $display_options ? $display_options['region'] : 'hidden',
        '#attributes' => [
          'class' => [
            'field-region',
          ],
        ],
      ],
      'plugin' => [
        'type' => [
          '#type' => 'hidden',
          '#value' => $display_options ? 'visible' : 'hidden',
          '#parents' => [
            'fields',
            $component_name,
            'type',
          ],
          '#attributes' => [
            'class' => [
              'field-plugin-type',
            ],
          ],
        ],
      ],
      'settings_summary' => [],
      'settings_edit' => [],
    ];

    return $extra_field_row;
  }

  /**
   * Get extra fields.
   */
  protected function getExtraFields() {
    if ($form_entity = $this->getFormEntityManager()->getFormEntity('')) {
      $extra_fields = $this->entityFieldManager->getExtraFields($form_entity->getEntityType(), $form_entity->getBundle());
      return isset($extra_fields['form']) ? $extra_fields['form'] : [];
    }
    return [];
  }

  /**
   * Get extra field.
   */
  protected function getExtraField($component_name) {
    $extra_fields = $this->getExtraFields();
    return isset($extra_fields[$component_name]) ? $extra_fields[$component_name] : [];
  }

}
