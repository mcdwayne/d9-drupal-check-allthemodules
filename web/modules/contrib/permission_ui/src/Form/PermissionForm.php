<?php

namespace Drupal\permission_ui\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the permission entity edit forms.
 *
 * @internal
 */
class PermissionForm extends EntityForm {

  /**
   * Entity bundle information.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * Constructs an IndexAddFieldsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity bundle information.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\permission_ui\Entity\Permission $entity */
    $entity = $this->entity;
    $entity_types = $this->getEntityTypes();
    $default_entity_type = $form_state->getValue('entity_type', current(array_keys($entity_types)));
    $default_entity_type = $entity->isNew() ? $default_entity_type : $entity->getElementEntityType();
    $form['id_fields'] = [
      '#type' => 'detail',
      '#prefix' => '<div class="id-fields-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['id_fields']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity'),
      '#options' => $entity_types,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateBundleType',
        'wrapper' => 'edit-bundle-type-wrapper',
      ],
      '#default_value' => $default_entity_type,
    ];
    $form['id_fields']['bundle_type'] = [
      '#type' => 'select',
      '#prefix' => '<div id="edit-bundle-type-wrapper">',
      '#suffix' => '</div>',
      '#title' => $this->t('Bundles'),
      '#options' => $this->findBundles($default_entity_type),
      '#default_value' => $form_state->getValue('entity_type', $entity->getElementBundleType()),
    ];

    $form['id_fields']['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#options' => [
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'list' => 'List',
      ],
      '#default_value' => $entity->getOperation(),
    ];
    $form['id_fields']['scope'] = [
      '#type' => 'select',
      '#title' => $this->t('Scope'),
      '#options' => [
        'any' => 'Any',
        'own' => 'Own',
      ],
      '#default_value' => $entity->getScope(),
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->getDescription(),
    ];

    $form['is_restricted'] = [
      '#title' => 'Restricted?',
      '#type' => 'checkbox',
      '#default_value' => $entity->isRestricted(),
    ];
    $form['#attached']['library'][] = 'permission_ui/drupal.permission_ui.admin';
    return parent::form($form, $form_state, $entity);
  }

  /**
   * Handles switching the entity type selector.
   *
   * @param array $form
   *   Form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   An array of affected form element.
   */
  public function updateBundleType(array $form, FormStateInterface $form_state) {
    $bundles = $this->findBundles($form_state->getValue('entity_type'));
    if ($bundles) {
      $form['id_fields']['bundle_type']['#options'] = $bundles;
    }
    else {
      $form['id_fields']['bundle_type']['#type'] = 'value';
    }
    return $form['id_fields']['bundle_type'];
  }

  /**
   * Helper to find bundle.
   *
   * @param string $entity_type
   *   String entity type machine name.
   *
   * @return array
   *   An array of bundles in given entity type.
   */
  public function findBundles($entity_type) {
    $bundles = [];
    foreach ($this->entityBundleInfo->getBundleInfo($entity_type) as $bundle_id => $bundle_info) {
      $bundles[$bundle_id] = $bundle_info['label'];
    }
    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Permission %label has been updated.', ['%label' => $entity->label()]));
      $this->logger('user')->notice('Permission %label has been updated.', ['%label' => $entity->label(), 'link' => $edit_link]);
    }
    else {
      drupal_set_message($this->t('Permission %label has been added.', ['%label' => $entity->label()]));
      $this->logger('user')->notice('Permission %label has been added.', ['%label' => $entity->label(), 'link' => $edit_link]);
    }
    $form_state->setRedirect('entity.user_permission.collection');
  }

  /**
   * Provides all entity types available.
   *
   * @return array
   *   An array of entity types.
   */
  protected function getEntityTypes() {
    $options = [];
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $options[$entity_type->id()] = $entity_type->getLabel();
      }
    }
    return $options;
  }

}
