<?php

namespace Drupal\access_by_entity\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\access_by_entity\AccessByEntityStorageInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * AccessByEntityForm.
 */
class AccessByEntityForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Access By Entity Storage.
   *
   * @var \\Drupal\access_by_entity\AccessByEntityStorageInterface
   */
  protected $accessByEntityStorage;

  /**
   * The current route matcher service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The cache tag invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a new AccessByEntityForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\access_by_entity\AccessByEntityStorageInterface $access_by_entity_storage
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The core route match service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The core route match service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              AccessByEntityStorageInterface $access_by_entity_storage,
                              RouteMatchInterface $current_route_match,
                              CacheTagsInvalidatorInterface $cache_tags_invalidator
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->accessByEntityStorage = $access_by_entity_storage;
    $this->currentRouteMatch = $current_route_match;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('access_by_entity.access_storage'),
      $container->get('current_route_match'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_admin_access_by_entity';
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\core\Entity\EntityInterface[]
   *   An array of role objects.
   */
  protected function getRoles() {
    return $this->entityTypeManager->getStorage('user_role')->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parameters = $this->currentRouteMatch->getRouteObject()->getOption('parameters');
    $entity_type_id = array_keys($parameters)[0];
    $entity = $this->getRequest()->attributes->get($entity_type_id);

    $form['header'] = [
      '#type' => 'item',
      '#markup' => $this->t('Below is the access restriction matrix for %title entity. 
          To deny access, check off the roles that should not access this item. For example, 
          to deny any anonymous user access, check off "Anonymous User". To allow access for a role, 
          leave the box unchecked. If a user has multiple roles, they will be denied access if any 
          of the roles they have are checked.',
        ['%title' => $entity->label()]),
    ];

    $role_names = [];
    foreach ($this->getRoles() as $role_name => $role) {
      // Retrieve role names for columns.
      if (!$role->isAdmin()) {
        $role_names[$role_name] = $role->label();
      }
    }
    // Store $role_names for use when saving the data.
    $form['role_names'] = [
      '#type' => 'value',
      '#value' => $role_names,
    ];
    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    ];
    foreach ($role_names as $name) {
      $form['permissions']['#header'][] = [
        'data' => $name,
        'class' => ['checkbox'],
      ];
    }
    $permissions = [
      'view' => [
        'title' => $this->t('View content'),
        'description' => NULL,
        'provider' => 'entity',
      ],
      'edit' => [
        'title' => $this->t('Edit content'),
        'description' => NULL,
        'provider' => 'entity',
      ],
      'delete' => [
        'title' => $this->t('Delete content'),
        'description' => NULL,
        'provider' => 'entity',
      ],
    ];
    $default_values = $this->accessByEntityStorage->findBy(
      [
        ['key' => 'entity_id', 'value' => $entity->id()],
        ['key' => 'entity_type_id', 'value' => $entity_type_id],
      ]
    );
    $default_values_permission = [];
    foreach ($default_values as $item) {
      if (!isset($default_values_permission[$item->perm])) {
        $default_values_permission[$item->perm] = [$item->rid => $item->rid];
      }
      else {
        $default_values_permission[$item->perm][$item->rid] = $item->rid;
      }
    }

    foreach ($permissions as $perm => $perm_item) {
      // Fill in default values for the permission.
      $perm_item += ['restrict access' => FALSE];
      $form['permissions'][$perm]['description'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="permission"><span class="title">{{ title }}</span></div>',
        '#context' => [
          'title' => $perm_item['title'],
        ],
      ];

      foreach ($role_names as $rid => $name) {
        $form['permissions'][$perm][$rid] = [
          '#title' => $name . ': ' . $perm_item['title'],
          '#title_display' => 'invisible',
          '#wrapper_attributes' => [
            'class' => ['checkbox'],
          ],
          '#type' => 'checkbox',
          '#default_value' => isset($default_values_permission[$perm][$rid]),
          '#attributes' => [
            'class' => [
              'rid-' . $rid,
              'js-rid-' . $rid,
            ],
          ],
          '#parents' => [$rid, $perm],
        ];
      }
    }

    $form['entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $entity->id(),
    ];

    $form['entity_type_id'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_type_id,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_id = $form_state->getValue('entity_id');
    $entity_type_id = $form_state->getValue('entity_type_id');
    $this->accessByEntityStorage->clear($entity_id, $entity_type_id);
    foreach ($form_state->getValue('role_names') as $role_name => $name) {
      $this->accessByEntityStorage->save(
        $entity_id, $entity_type_id, $role_name,
        array_diff((array) $form_state->getValue($role_name), [0])
      );
    }
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $tags = $entity->getCacheTags();
    $this->cacheTagsInvalidator->invalidateTags($tags);

    drupal_set_message($this->t('The changes have been saved.'));
  }

}
