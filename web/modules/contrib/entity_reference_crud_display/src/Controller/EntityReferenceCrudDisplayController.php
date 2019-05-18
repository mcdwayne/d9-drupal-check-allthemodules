<?php

namespace Drupal\entity_reference_crud_display\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Form\FormAjaxException;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Class EntityReferenceCrudDisplayController.
 */
class EntityReferenceCrudDisplayController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The PrivateTempStore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new crud_formController.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The PrivateTempStore factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Render Interface.
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, RendererInterface $renderer, FormBuilder $formBuilder) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->renderer = $renderer;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('renderer'),
      $container->get('form_builder')
    );
  }

  /**
   * Entity view.
   *
   * @param string $method
   *   Call method: ajax or nojs.
   * @param string $entity_target_type
   *   The type of the entity target, used to create a new entity of this type.
   * @param ContentEntityInterface $entity_target
   *   The entity target that relation with entity through $field_name.
   * @param string $entity_parent_type
   *   The parent entity type.
   * @param ContentEntityInterface $entity_parent
   *   The parent entity, used to make the relationship.
   * @param string $view_mode
   *   The view mode.
   * @param int $delta
   *   The order of the entity in the list.
   * @param string $field_name
   *   The name of the field that relation $entity and $entity_target.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Close confirm modal.
   */
  public function getEntityView($method, $entity_target_type, ContentEntityInterface $entity_target, $entity_target_bundle, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $delta, $field_name) {
    // Call function to build Entity view.
    $build = $this->buildEntityView($method, $entity_target_type, $entity_target, $entity_target_bundle, $entity_parent_type, $entity_parent, $view_mode, $delta, $field_name);

    // Now we return an AjaxResponse with the ReplaceCommand to place our
    // entity on the page.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand("#entity-reference-crud-$entity_target_type-$delta", $build));

    return $response;
  }

  /**
   * Entity edit form.
   *
   * @param string $method
   *   Call method: ajax or nojs.
   * @param string $entity_target_type
   *   The type of the entity target, used to create a new entity of this type.
   * @param ContentEntityInterface $entity_target
   *   The entity target that relation with entity through $field_name.
   * @param string $entity_target_bundle
   *   The entity target bundle.
   * @param string $entity_parent_type
   *   The parent entity type.
   * @param ContentEntityInterface $entity_parent
   *   The parent entity, used to make the relationship.
   * @param string $view_mode
   *   The view mode.
   * @param int $delta
   *   The order of the entity in the list.
   * @param string $field_name
   *   The name of the field that relation $entity and $entity_target.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Close confirm modal.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function getEntityEdit($method, $entity_target_type, ContentEntityInterface $entity_target, $entity_target_bundle, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $delta, $field_name) {
    $response = new AjaxResponse();

    // Replace entity with PrivateTempStore copy if available and not resetting,
    // init PrivateTempStore copy otherwise.
    $tempstore_entity = $this->tempStoreFactory->get('crud_form')
      ->get($entity_target->uuid());
    if ($tempstore_entity) {
      $entity_target = $tempstore_entity;
    }
    else {
      $this->tempStoreFactory->get('crud_form')->set($entity_target->uuid(), $entity_target);
    }

    $form_state = (new FormState())
      ->disableRedirect()
      ->addBuildInfo('args', [$entity_target]);
    try {
      $form = $this->formBuilder()
        ->buildForm('Drupal\entity_reference_crud_display\Form\EntityReferenceCrudDisplayForm', $form_state);
    }
    catch (EnforcedResponseException $e) {
    }
    catch (FormAjaxException $e) {
    }

    if ($form_state->isExecuted()) {
      // The form submission saved the entity in PrivateTempStore. Return the
      // updated view of the entity from the PrivateTempStore copy.
      $entity_target = $this->tempStoreFactory->get('crud_form')->get($entity_target->uuid());

      // Save update entity e delete tempstore.
      $tempstore = $this->tempStoreFactory->get('crud_form');
      $tempstore->get($entity_target->uuid())->save();
      $tempstore->delete($entity_target->uuid());

      // Respond to client that the entity was saved properly.
      $response = $this->getEntityView($method, $entity_target_type, $entity_target, $entity_target_bundle, $entity_parent_type, $entity_parent, $view_mode, $delta, $field_name);
    }
    else {
      // Set up the options for our route, we default the method to 'nojs'
      // since the drupal ajax library will replace that for us.
      $options = [
        'method' => $method,
        'entity_target_type' => $entity_target_type,
        'entity_target' => $entity_target->id(),
        'entity_target_bundle' => $entity_target_bundle,
        'entity_parent_type' => $entity_parent_type,
        'entity_parent' => $entity_parent->id(),
        'view_mode' => $view_mode,
        'delta' => $delta,
        'field_name' => $field_name,
      ];

      // Now we create the path from our route, passing the options it needs.
      $uri_cancel = Url::fromRoute('entity_reference_crud_display.view_entity', $options);

      // Creating a cancel link to return entity view.
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $uri_cancel,
        '#weight' => '6',
        '#options' => $uri_cancel->getOptions() + [
          'attributes' => [
            'class' => [
              'use-ajax',
              'button',
            ],
          ],
        ],
      ];

      // To workaround the issue where the ReplaceCommand actually REMOVES the
      // HTML element selected by the selector given to the ReplaceCommand,
      // we need to wrap our content in a div that same ID, otherwise only the
      // first click will work. (Since the ID will no longer exist on the page).
      $build[$delta] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => "entity-reference-crud-$entity_target_type-$delta",
        ],
        'entity' => $form,
      ];

      $build[$delta]['status_message'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => "entity-reference-crud-$entity_target_type-$delta-status-message",
        ],
        '#weight' => -5,
      ];

      $response->addCommand(new ReplaceCommand("#entity-reference-crud-$entity_target_type-$delta", $this->renderer->renderRoot($build)));

      $errors = $form_state->getErrors();
      if (count($errors)) {
        $status_messages = [
          '#type' => 'status_messages',
        ];

        $response->addCommand(new AppendCommand("#entity-reference-crud-$entity_target_type-$delta-status-message", $this->renderer->renderRoot($status_messages)));
      }
    }

    return $response;
  }

  /**
   * Show the create entity form in ajax container.
   *
   * @param string $method
   *   Call method: ajax or nojs.
   * @param string $entity_target_type
   *   The type of the entity target, used to create a new entity of this type.
   * @param string $entity_target_bundle
   *   The bundle of the entity.
   * @param string $entity_parent_type
   *   The parent entity type.
   * @param ContentEntityInterface $entity_parent
   *   The parent entity, used to make the relationship.
   * @param string $view_mode
   *   The view mode.
   * @param string $field_name
   *   The name of the field that relation $entity and $entity_target.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Only remove html inside #ajax-create-entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function getEntityCreate($method, $entity_target_type, $entity_target_bundle, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $field_name) {
    $response = new AjaxResponse();

    if ($entity_target_bundle == '0') {
      $entity_target = \Drupal::entityTypeManager()
        ->getStorage($entity_target_type)
        ->create([]);
    }
    else {
      $entity_target = \Drupal::entityTypeManager()
        ->getStorage($entity_target_type)
        ->create(['type' => $entity_target_bundle]);
    }

    // Replace entity_target with PrivateTempStore copy if available.
    $tempstore_entity = $this->tempStoreFactory->get('crud_form')
      ->get($entity_target->uuid());
    if ($tempstore_entity) {
      $entity_target = $tempstore_entity;
    }
    // Init PrivateTempStore copy otherwise.
    else {
      $this->tempStoreFactory->get('crud_form')->set($entity_target->uuid(), $entity_target);
    }

    $form_state = (new FormState())
      ->disableRedirect()
      ->addBuildInfo('args', [$entity_target]);

    try {
      $form = $this->formBuilder()
        ->buildForm('Drupal\entity_reference_crud_display\Form\EntityReferenceCrudDisplayForm', $form_state);
    }
    catch (EnforcedResponseException $e) {

    }
    catch (FormAjaxException $e) {

    }

    // Return a empty div if form_state is executed.
    if ($form_state->isExecuted()) {
      // The form submission saved the entity in PrivateTempStore. Return the
      // updated view of the entity from the PrivateTempStore copy.
      $entity_target = $this->tempStoreFactory->get('crud_form')->get($entity_target->uuid());
      $entity_target->save();
      $target_id = $entity_target->id();

      // Insert the reference and get the created item to get your delta (name)
      // in the future.
      $item = $entity_parent->get($field_name)->appendItem($target_id);

      // Delete tempstore.
      $this->tempStoreFactory->get('crud_form')->delete($entity_target->uuid());

      // Save de target_id on entity.
      $entity_parent->save();

      $class_field_name = str_replace('_', '-', $field_name);

      // Print the new entity.
      $build = $this->buildEntityView($method, $entity_target_type, $entity_target, $entity_target_bundle, $entity_parent_type, $entity_parent, $view_mode, $item->getName(), $field_name);
      $response->addCommand(new AppendCommand(".field--name-$class_field_name", $build));

      $link_create = $this->linkCreate($entity_target_type, $entity_target_bundle, $entity_target, $entity_parent_type, $entity_parent, $view_mode, $field_name, $item->getName());
      $response->addCommand(new ReplaceCommand('#' . $field_name . '-' . $entity_target_type . '-link-new', $link_create));

      // Empty create container.
      $create_container[-1]['field_create_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => "ajax-create-entity-$entity_target_type",
        ],
      ];
      $response->addCommand(new ReplaceCommand("#ajax-create-entity-$entity_target_type", $create_container));
    }
    // Return entity create form if not.
    else {
      // Set up the options for our route, we default the method to 'nojs'
      // since the drupal ajax library will replace that for us.
      $options = [
        'method' => 'ajax',
        'entity_target_type' => $entity_target_type,
      ];

      // Now we create the path from our route, passing the options it needs.
      $uri_cancel = Url::fromRoute('entity_reference_crud_display.cancel_entity', $options);

      // Creating a cancel link to return entity view.
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $uri_cancel,
        '#weight' => '6',
        '#options' => $uri_cancel->getOptions() + [
          'attributes' => [
            'class' => [
              'use-ajax',
              'button',
            ],
          ],
        ],
      ];

      // To workaround the issue where the ReplaceCommand actually REMOVES the
      // HTML element selected by the selector given to the ReplaceCommand,
      // we need to wrap our content in a div that same ID, otherwise only the
      // first click will work. (Since the ID will no longer exist on the page).
      $build[-1]['field_create_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => "ajax-create-entity-$entity_target_type",
        ],
        'entity' => $form,
      ];

      $build[-1]['field_create_container']['status_message'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => "ajax-create-entity-$entity_target_type-status-message",
        ],
        '#weight' => -5,
      ];

      $response->addCommand(new ReplaceCommand("#ajax-create-entity-$entity_target_type", $build));

      $errors = $form_state->getErrors();
      if (count($errors)) {
        $status_messages = [
          '#type' => 'status_messages',
        ];

        $response->addCommand(new AppendCommand("#ajax-create-entity-$entity_target_type-status-message", $this->renderer->renderRoot($status_messages)));
      }
    }

    return $response;
  }

  /**
   * Route callback to show modal to confirm entity delete.
   *
   * @param string $method
   *   Call method: ajax or nojs.
   * @param string $entity_target_type
   *   The type of the entity target, used to create a new entity of this type.
   * @param ContentEntityInterface $entity_target
   *   The entity target that relation with entity through $field_name.
   * @param string $entity_target_bundle
   *   The entity target bundle.
   * @param string $entity_parent_type
   *   The parent entity type.
   * @param ContentEntityInterface $entity_parent
   *   The parent entity, used to make the relationship.
   * @param string $view_mode
   *   The view mode.
   * @param int $delta
   *   The order of the entity in the list.
   * @param string $field_name
   *   The name of the field that relation $entity and $entity_target.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Close confirm modal.
   */
  public function deleteEntityConfirm($method, $entity_target_type, ContentEntityInterface $entity_target, $entity_target_bundle, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $delta, $field_name) {
    $response = new AjaxResponse();

    $form = \Drupal::service('entity.form_builder')->getForm($entity_target, 'delete');
    $form['actions']['submit']['#attributes'] = ['class' => ['use-ajax-submit']];

    // Set up the options for our route, we default the method to 'nojs'
    // since the drupal ajax library will replace that for us.
    $options_delete = [
      'method' => $method,
      'entity_target_type' => $entity_target_type,
      'entity_target' => $entity_target->id(),
      'entity_target_bundle' => $entity_target_bundle,
      'entity_parent_type' => $entity_parent_type,
      'entity_parent' => $entity_parent->id(),
      'view_mode' => $view_mode,
      'delta' => $delta,
      'field_name' => $field_name,
    ];

    $options_cancel = [
      'method' => 'ajax',
      'entity_target_type' => $entity_target_type,
    ];

    // Now we create the path from our route, passing the options it needs.
    $uri_delete = Url::fromRoute('entity_reference_crud_display.delete_entity', $options_delete);
    $uri_cancel = Url::fromRoute('entity_reference_crud_display.cancel_entity_delete', $options_cancel);

    // Creating a cancel link to return entity view.
    $form['actions']['submit'] = [
      '#type' => 'link',
      '#title' => t('Delete'),
      '#url' => $uri_delete,
      '#weight' => '5',
      '#options' => $uri_delete->getOptions() + [
        'attributes' => [
          'class' => [
            'use-ajax',
            'button',
          ],
        ],
      ],
    ];

    // Creating a cancel link to return entity view.
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => $uri_cancel,
      '#weight' => '6',
      '#options' => $uri_cancel->getOptions() + [
        'attributes' => [
          'class' => [
            'use-ajax',
            'button',
          ],
        ],
      ],
    ];

    $title = t('Do you want to delete %id?', ['%id' => $entity_target->label()]);

    $response->addCommand(new OpenModalDialogCommand($title, $form));
    return $response;
  }

  /**
   * Only remove create entity form.
   *
   * @param string $method
   *   Call method.
   * @param string $entity_target_type
   *   The entity type.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Only remove html inside #ajax-create-entity
   */
  public function getCancelEntityCreate($method, $entity_target_type) {
    $build[-1]['field_create_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => "ajax-create-entity-$entity_target_type",
      ],
    ];

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand("#ajax-create-entity-$entity_target_type", $build));
    return $response;
  }

  /**
   * Route callback.
   *
   * @param string $method
   *   The method of the call.
   * @param string $entity_target_type
   *   The type of the entity target.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Close confirm modal.
   */
  public function getCancelEntityDelete($method, $entity_target_type) {
    $command = new CloseModalDialogCommand("ajax-create-entity-$entity_target_type");
    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
  }

  /**
   * Route callback to Delete entity.
   *
   * @param string $method
   *   Call method: ajax or nojs.
   * @param string $entity_target_type
   *   The type of the entity target, used to create a new entity of this type.
   * @param ContentEntityInterface $entity_target
   *   The entity target that relation with entity through $field_name.
   * @param string $entity_target_bundle
   *   The entity target bundle.
   * @param string $entity_parent_type
   *   The parent entity type.
   * @param ContentEntityInterface $entity_parent
   *   The parent entity, used to make the relationship.
   * @param string $view_mode
   *   The view mode.
   * @param int $delta
   *   The order of the entity in the list.
   * @param string $field_name
   *   The name of the field that relation $entity and $entity_target.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Close confirm modal.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteEntity($method, $entity_target_type, ContentEntityInterface $entity_target, $entity_target_bundle, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $delta, $field_name) {
    $response = new AjaxResponse();

    // Select the delta field of de reference.
    $table = $entity_parent->getEntityTypeId() . '__' . $field_name;
    $col = $field_name . '_target_id';
    $query = \Drupal::database()->select($table);
    $query->fields($table, ['delta']);
    $query->condition($col, $entity_target->id());
    $result = $query->execute()->fetchAllAssoc('delta');

    // Remove item of reference table.
    $entity_parent->get($field_name)->removeItem(reset($result)->delta);
    $entity_parent->save();

    // Delete de entity target.
    $entity_target->delete();

    // Count the total records form entity_parent to generate link create if
    // necessary
    $query = \Drupal::database()->select($table);
    $query->fields($table, ['entity_id']);
    $query->condition('entity_id', $entity_parent->id());
    $num_rows = $query->countQuery()->execute()->fetchField();

    // The $delta param is decremented of 1, because the delta starts by 0
    $link_create = $this->linkCreate($entity_target_type, $entity_target_bundle, $entity_target, $entity_parent_type, $entity_parent, $view_mode, $field_name, $num_rows - 1);
    $response->addCommand(new ReplaceCommand('#' . $field_name . '-' . $entity_target_type . '-link-new', $link_create));

    // Hide the container of the deleted entity.
    $response->addCommand(new ReplaceCommand("#entity-reference-crud-$entity_target_type-$delta", ''));

    // Close confirmation delete modal.
    $command = new CloseModalDialogCommand("ajax-create-entity-$entity_target_type");
    $response->addCommand($command);

    return $response;
  }

  /**
   * Build the entity view.
   *
   * @param string $method
   *   Call method: ajax or nojs.
   * @param string $entity_target_type
   *   The type of the entity target, used to create a new entity of this type.
   * @param ContentEntityInterface $entity_target
   *   The entity target that relation with entity through $field_name.
   * @param string $entity_target_bundle
   *   The entity target bundle.
   * @param string $entity_parent_type
   *   The parent entity type.
   * @param ContentEntityInterface $entity_parent
   *   The parent entity, used to make the relationship.
   * @param string $view_mode
   *   The view mode.
   * @param int $delta
   *   The order of the entity in the list.
   * @param string $field_name
   *   The name of the field that relation $entity and $entity_target.
   *
   * @return mixed
   *   Entity ready to render.
   */
  public function buildEntityView($method, $entity_target_type, ContentEntityInterface $entity_target, $entity_target_bundle, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $delta, $field_name) {
    // We have javascript so let's grab the entityViewBuilder service.
    $view_builder = $this->entityTypeManager()->getViewBuilder($entity_target->getEntityTypeId());


    $cardinality = $entity_parent->getFieldDefinition($field_name)->getCardinality();
    if ($cardinality == -1 || $delta < $cardinality) {

    }
    // Now we add the container to render after the links. This is where the
    // AJAX loaded content will be injected in to.
    $build[$delta]['field_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => "entity-reference-crud-$entity_target_type-$delta",
        'class' => 'entity-reference-crud-item',
      ],
    ];

    // Get the render array of this entity in the specified view mode.
    $build[$delta]['field_container']['entity'] = $view_builder->view($entity_target, $view_mode, $entity_target->language()->getId());

    // Set up the options for our route, we default the method to 'nojs'
    // since the drupal ajax library will replace that for us.
    $options = [
      'method' => $method,
      'entity_target_type' => $entity_target_type,
      'entity_target' => $entity_target->id(),
      'entity_target_bundle' => $entity_target_bundle,
      'entity_parent_type' => $entity_parent_type,
      'entity_parent' => $entity_parent->id(),
      'view_mode' => $view_mode,
      'delta' => $delta,
      'field_name' => $field_name,
    ];

    // Now we create the path from our route, passing the options it needs.
    $uri_edit = Url::fromRoute('entity_reference_crud_display.edit_entity_form', $options);
    $uri_delete = Url::fromRoute('entity_reference_crud_display.delete_confirm', $options);

    // And create a link element. We need to add the 'use-ajax' class so
    // that.
    // Drupal's core AJAX library will detect this link and ajaxify it.
    // Only show the 'edit' link for users that have access to update parent
    // entity and update target entity.
    if ($entity_parent->access('update') && $entity_target->access('update')) {
      $build[$delta]['field_container']['edit'] = [
        '#type' => 'link',
        '#title' => t('Edit'),
        '#url' => $uri_edit,
        '#options' => $uri_edit->getOptions() + [
          'attributes' => [
            'class' => [
              'use-ajax',
              'edit-button',
            ],
          ],
        ],
      ];
    }

    // Only show the 'delete' link for users that have access to update parent
    // entity and delete target entity.
    if ($entity_parent->access('update') && $entity_target->access('delete')) {
      $build[$delta]['field_container'][] = [
        '#type' => 'link',
        '#title' => t('Delete'),
        '#url' => $uri_delete,
        '#options' => $uri_delete->getOptions() + [
          'attributes' => [
            'class' => [
              'use-ajax',
              'delete-button',
            ],
          ],
        ],
      ];
    }

    return $build;
  }

  /**
   * Checks access for delete a entity from entity reference crud display.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function deleteConfirmAccess(AccountInterface $account, $method, $entity_target_type, ContentEntityInterface $entity_target, $entity_target_bundle, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $delta, $field_name) {
    return AccessResult::allowedIf($entity_target->access('delete') && $entity_parent->access('update'));
  }

  /**
   * Checks access for delete a entity from entity reference crud display.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function createAccess(AccountInterface $account, $method, $entity_target_type, $entity_target_bundle, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $field_name) {
    if ($entity_target_bundle == '0') {
      $entity_target = \Drupal::entityTypeManager()
        ->getStorage($entity_target_type)
        ->create([]);
    }
    else {
      $entity_target = \Drupal::entityTypeManager()
        ->getStorage($entity_target_type)
        ->create(['type' => $entity_target_bundle]);
    }

    return AccessResult::allowedIf($entity_target->access('create') && $entity_parent->access('update'));
  }

  /**
   * Checks access for delete a entity from entity reference crud display.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function editAccess(AccountInterface $account, $method, $entity_target_type, ContentEntityInterface $entity_target, $entity_target_bundle, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $delta, $field_name) {
    return AccessResult::allowedIf($entity_target->access('update') && $entity_parent->access('update'));
  }

  /**
   * Generate the link create new if is possible add another one.
   *
   * @param string $entity_target_type
   * @param string $entity_target_bundle
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_target
   * @param string $entity_parent_type
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_parent
   * @param string $view_mode
   * @param string $field_name
   * @param string $delta
   *
   * @return void
   */
  public function linkCreate($entity_target_type, $entity_target_bundle, ContentEntityInterface $entity_target, $entity_parent_type, ContentEntityInterface $entity_parent, $view_mode, $field_name, $delta) {
        // Link 'New' to ajax create form.
    // Only show the 'create' link for users that have access to update parent
    // entity.
    if ($entity_parent->access('update') && $entity_target && $entity_target->access('create')) {
      // Creating a link 'New', for add new entity.
      $options_create = [
        'method' => 'ajax',
        'entity_target_type' => $entity_target_type,
        'entity_target_bundle' => $entity_target_bundle,
        'entity_parent_type' => $entity_parent_type,
        'entity_parent' => $entity_parent->id(),
        'view_mode' => $view_mode,
        'field_name' => $field_name,
      ];

      // Create ajax url from from create.
      $uri = Url::fromRoute('entity_reference_crud_display.new_entity_form', $options_create);

      $elements[-3] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => [
            $field_name . '-' . $entity_target_type . '-link-new',
          ],
        ],
      ];

      $cardinality = $entity_parent->getFieldDefinition($field_name)->getCardinality();

      // Show the link create new.
      if ($cardinality == -1 || ($delta + 1) < $cardinality) {
        $elements[-3] = [
          '#type' => 'link',
          '#title' => t('New'),
          '#url' => $uri,
          '#options' => $uri->getOptions() + [
            'attributes' => [
              'id' => [
                $field_name . '-' . $entity_target_type . '-link-new',
              ],
              'class' => [
                'field--type-entity-reference__new',
                'use-ajax',
              ],
            ],
          ],
        ];
      }

      return $elements;
    }
  }

}
