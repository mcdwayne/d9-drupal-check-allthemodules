<?php

namespace Drupal\entity_pilot_map_config\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_pilot_map_config\FieldMappingInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity form for managing field mappings.
 */
class FieldMappingForm extends EntityForm {

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new BundleMappingForm object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity bundle info service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_field.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if ($this->getRequest()->query->get('message', FALSE)) {
      $form['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('Please configure how to handle missing fields.'),
          ],
        ],
      ];
    }

    /** @var \Drupal\entity_pilot_map_config\FieldMappingInterface $ep_field_mapping */
    $ep_field_mapping = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ep_field_mapping->label(),
      '#description' => $this->t("Label for the Field mapping."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ep_field_mapping->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_pilot_map_config\Entity\FieldMapping::load',
      ],
      '#disabled' => !$ep_field_mapping->isNew(),
    ];

    $form['mappings'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Entity type'),
        $this->t('Source field'),
        $this->t('Field type'),
        $this->t('Destination field'),
      ],
    ];

    $entity_type_options = [];

    foreach ($ep_field_mapping->getMappings() as $delta => $mapping) {
      $entity_type = $mapping['entity_type'];
      if (!isset($entity_type_options[$entity_type])) {
        $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
        // Group the fields per field-type.
        foreach ($fields as $field) {
          $entity_type_options[$entity_type][$field->getType()][$field->getName()] = $field->getLabel();
        }
        $empty = [
          FieldMappingInterface::IGNORE_FIELD => $this->t('Ignore'),
        ];
        // Add the ignore option.
        $entity_type_options[$entity_type] = array_map(function (array $item) use ($empty) {
          return $item + $empty;
        }, $entity_type_options[$entity_type]);
      }
      $form['mappings'][$delta] = [
        'entity_type_display' => [
          '#markup' => $mapping['entity_type'],
        ],
        'source_field_name_display' => [
          '#markup' => $mapping['source_field_name'],
        ],
        'field_type_display' => [
          '#markup' => $mapping['field_type'],
        ],
        'destination_field_name' => [
          '#type' => 'select',
          '#title_display' => 'invisible',
          '#options' => isset($entity_type_options[$entity_type][$mapping['field_type']]) ? $entity_type_options[$entity_type][$mapping['field_type']] : $empty,
          '#default_value' => $mapping['destination_field_name'],
        ],
      ];
      $form['values'][$delta] = [
        'entity_type' => [
          '#type' => 'value',
          '#parents' => ['mappings', $delta, 'entity_type'],
          '#value' => $mapping['entity_type'],
        ],
        'field_type' => [
          '#type' => 'value',
          '#parents' => ['mappings', $delta, 'field_type'],
          '#value' => $mapping['field_type'],
        ],
        'source_field_name' => [
          '#type' => 'value',
          '#parents' => ['mappings', $delta, 'source_field_name'],
          '#value' => $mapping['source_field_name'],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ep_field_mapping = $this->entity;
    $status = $ep_field_mapping->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Field mapping.', [
          '%label' => $ep_field_mapping->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Field mapping.', [
          '%label' => $ep_field_mapping->label(),
        ]));
    }
    $request = $this->getRequest();
    if (($destinations = $request->query->get('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
      $request->query->remove('destinations');
      $form_state->setRedirectUrl($next_destination);
    }
    else {
      $form_state->setRedirectUrl($ep_field_mapping->toUrl('collection'));
    }
  }

}
