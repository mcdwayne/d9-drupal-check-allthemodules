<?php

/**
 * @file
 * Contains \Drupal\rng_date_scheduler\Form\EventTypeDateSchedulerForm.
 */

namespace Drupal\rng_date_scheduler\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\rng_date_scheduler\EventDateProviderInterface;

/**
 * Form for event type access defaults.
 */
class EventTypeDateSchedulerForm extends EntityForm {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The event date provider.
   *
   * @var \Drupal\rng_date_scheduler\EventDateProviderInterface
   */
  protected $eventDateProvider;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\rng\EventTypeInterface
   */
  protected $entity;

  /**
   * Constructs a new EventTypeDateSchedulerForm object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\rng_date_scheduler\EventDateProviderInterface $event_date_provider
   *   The event date provider.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EventDateProviderInterface $event_date_provider) {
    $this->entityFieldManager = $entity_field_manager;
    $this->eventDateProvider = $event_date_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('rng_date_scheduler.event_dates')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $event_type = &$this->entity;

    $field_definitions = $this->entityFieldManager
      ->getFieldDefinitions($event_type->getEventEntityTypeId(), $event_type->getEventBundle());

    $default_access = $this->eventDateProvider
      ->getDefaultAccess($event_type->getEventEntityTypeId(), $event_type->getEventBundle());
    $form['default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deny by default'),
      '#description' => $this->t('Deny new registrations if no dates are set on the event.'),
      '#default_value' => $default_access === FALSE,
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        [
          'data' => $this->t('Field'),
          'width' => '10%',
        ],
        [
          'data' => $this->t('Description'),
          'width' => '20%',
        ],
        [
          'data' => $this->t('Enabled'),
          'width' => '10%',
        ],
        [
          'data' => $this->t('Before'),
          'width' => '20%',
        ],
        [
          'data' => $this->t('During'),
          'width' => '20%',
        ],
        [
          'data' => $this->t('After'),
          'width' => '20%',
        ],
      ],
      '#empty' => $this->t('There are no date fields attached to this entity type.'),
    ];

    $fields = $this->eventDateProvider
      ->getFieldAccess($event_type->getEventEntityTypeId(), $event_type->getEventBundle(), NULL);

    foreach ($field_definitions as $field_definition) {
      if ($field_definition->getType() != 'datetime') {
        continue;
      }
      $field_name = $field_definition->getName();

      $access = [];
      foreach (['before', 'during', 'after'] as $time) {
        $access[$time] = isset($fields[$field_name]['access'][$time]) && $fields[$field_name]['access'][$time] === FALSE;
      }

      $row = [];

      $row['label'] = [
        '#plain_text' => $field_definition->getLabel(),
      ];

      $description = $field_definition->getDescription() ?: $this->t('None');
      $row['description'] = [
        '#plain_text' => $description,
      ];

      $row['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Status'),
        '#title_display' => 'invisible',
        '#default_value' => !empty($fields[$field_name]['status']),
      ];

      $row['before'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Deny new registrations'),
        '#description' => $this->t('Forbid creation of registrations before date in this field.'),
        '#default_value' => $access['before'],
        '#states' => [
          'disabled' => [
            ':input[name="table[' . $field_name . '][status]"]' => ['checked' => FALSE],
          ],
        ],
      ];

      if ($field_definition->getSetting('datetime_type') == 'datetime') {
        $row[] = [
          '#plain_text' => $this->t('Not applicable'),
        ];
      }
      else {
        // Does not include time.
        $row['during'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Deny new registrations'),
          '#description' => $this->t('Forbid creation of registrations within date in this field.'),
          '#default_value' => $access['during'],
          '#states' => [
            'disabled' => [
              ':input[name="table[' . $field_name . '][status]"]' => ['checked' => FALSE],
            ],
          ],
        ];
      }

      $row['after'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Deny new registrations'),
        '#description' => $this->t('Forbid creation of registrations after date in this field.'),
        '#default_value' => $access['after'],
        '#states' => [
          'disabled' => [
            ':input[name="table[' . $field_name . '][status]"]' => ['checked' => FALSE],
          ],
        ],
      ];

      $form['table'][$field_name] = $row;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $event_type = &$this->entity;
    $default = $form_state->getValue('default') ? FALSE : NULL;

    $fields = [];
    foreach ($form_state->getValue('table') as $field_name => $row) {
      $field = [];
      $field['status'] = (boolean) $row['status'];
      $field['field_name'] = $field_name;

      foreach (['before', 'during', 'after'] as $time) {
        $deny_registrations = !empty($row[$time]);
        $field['access'][$time] = $deny_registrations ? FALSE : NULL;
      }

      $fields[] = $field;
    }

    $event_type
      ->setThirdPartySetting('rng_date_scheduler', 'default_access', $default)
      ->setThirdPartySetting('rng_date_scheduler', 'fields', $fields)
      ->save();

    drupal_set_message($this->t('Date settings saved.'));
  }

  /**
   * {@inheritdoc}
   *
   * Remove delete element since it is confusing on non CRUD forms.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

}
