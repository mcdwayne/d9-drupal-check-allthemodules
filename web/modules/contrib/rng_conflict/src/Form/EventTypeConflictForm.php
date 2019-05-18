<?php

/**
 * @file
 * Contains \Drupal\rng_conflict\Form\EventTypeConflictForm.
 */

namespace Drupal\rng_conflict\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\rng_conflict\RngConflictProviderInterface;

/**
 * Form for event type access defaults.
 */
class EventTypeConflictForm extends EntityForm {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The RNG conflict provider.
   *
   * @var \Drupal\rng_conflict\RngConflictProviderInterface
   */
  protected $rngConflictProvider;

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
   * @param \Drupal\rng_conflict\RngConflictProviderInterface $conflict_provider
   *   The RNG conflict provider.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, RngConflictProviderInterface $conflict_provider) {
    $this->entityFieldManager = $entity_field_manager;
    $this->rngConflictProvider = $conflict_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('rng_conflict.conflict_provider')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $event_type = &$this->entity;

    $sets = $this->rngConflictProvider->getSets(
      $event_type->getEventEntityTypeId(),
      $event_type->getEventBundle()
    );

    $default_value = [];
    foreach ($sets as $set) {
      // @todo Allow configuration UI for multiple sets.
      foreach ($set as $field) {
        $default_value[] = $field['field_name'];
      }
    }

    $form['field_set'] = [
      '#type' => 'checkboxes',
      '#options' => [],
      '#default_value' => $default_value,
    ];

    $field_definitions = $this->entityFieldManager
      ->getFieldDefinitions($event_type->getEventEntityTypeId(), $event_type->getEventBundle());
    foreach ($field_definitions as $field_definition) {
      $form['field_set']['#options'][$field_definition->getName()] = $field_definition->getLabel();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $event_type = &$this->entity;
    $sets = [];

    $set = [];
    foreach ($form_state->getValue('field_set') as $field_name => $checked) {
      if ($checked) {
        $set[] = ['field_name' => $field_name];
      }
    }

    $sets[] = $set;
    $event_type->setThirdPartySetting('rng_conflict', 'conflicts', $sets);
    $event_type->save();

    drupal_set_message($this->t('Conflict settings saved.'));
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
