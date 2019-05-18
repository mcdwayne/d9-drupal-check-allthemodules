<?php

/**
 * @file
 * Contains \Drupal\entity_base\Form\EntityBaseTypeForm.
 */

namespace Drupal\entity_base\Form;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Form controller for entity type forms.
 */
class EntityBaseTypeForm extends BundleEntityFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the EntityBaseTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity_type = $this->entity;
    $entity_type_info = $entity_type->getEntityType();
    $entity_type_label = $entity_type_info->getLabel();
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add ' . $entity_type_label);
    }
    else {
      $form['#title'] = $this->t('Edit %label ' . $entity_type_label, ['%label' => $entity_type->label()]);
    }

    $form['label'] = [
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity_type->label(),
      '#description' => t('The human-readable name of this entity type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $entity_type->isLocked(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#description' => t('A unique machine-readable name for this entity type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $entity_type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add new entity</em> page.'),
    );

    $form['help']  = array(
      '#type' => 'textarea',
      '#title' => t('Explanation or submission guidelines'),
      '#default_value' => $entity_type->getHelp(),
      '#description' => t('This text will be displayed at the top of the page when creating or editing entity of this type.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if (\Drupal::moduleHandler()->moduleExists('field_ui') &&
      $this->getEntity()->isNew()
    ) {
      $actions['submit']['#value'] = t('Save entity type');
      $actions['save_continue'] = $actions['submit'];
      $actions['save_continue']['#value'] = t('Save and manage fields');
      $actions['save_continue']['#submit'][] = [$this, 'redirectToFieldUI'];
      $actions['delete']['#value'] = t('Delete entity type');
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entity;
    $entity_type_info = $entity_type->getEntityType();
    $entity_type_label = $entity_type_info->getLabel();
    $entity_type->setNewRevision($form_state->getValue(array('options', 'revision')));
    $entity_type->set('id', trim($entity_type->id()));
    $entity_type->set('label', trim($entity_type->label()));
    $status = $entity_type->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t($entity_type_label . ' %label has been updated.', ['%label' => $entity_type->label()]));
    }
    else {
      drupal_set_message(t($entity_type_label . ' %label has been created.', ['%label' => $entity_type->label()]));
    }

    $this->entityManager->clearCachedFieldDefinitions();
    $form_state->setRedirect('entity.' . $entity_type_info->id() . '.collection');
  }

  /**
   * Form submission handler to redirect to Manage fields page of Field UI.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function redirectToFieldUI(array $form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#parents'][0] === 'save_continue' && $route_info = FieldUI::getOverviewRouteInfo($this->entity->getEntityType()->getBundleOf(), $this->entity->id())) {
      $form_state->setRedirectUrl($route_info);
    }
  }

  /**
   * Check whether the entity type exists.
   *
   * @param $id
   * @return bool
   */
  public function exists($id) {
    return !empty($this->entityTypeManager->getStorage($this->entity->getEntityType()->id())->load($id));
  }

}
