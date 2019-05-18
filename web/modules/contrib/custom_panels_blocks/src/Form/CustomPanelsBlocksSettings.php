<?php

namespace Drupal\custom_panels_blocks\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure CustomPanelsBlocksSettings.
 */
class CustomPanelsBlocksSettings extends ConfigFormBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a CustomPanelsBlocksSettings object.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   */
  public function __construct(BlockManagerInterface $block_manager, ContextRepositoryInterface $context_repository, RoleStorageInterface $role_storage) {
    $this->blockManager = $block_manager;
    $this->contextRepository = $context_repository;
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository'),
      $container->get('entity.manager')->getStorage('user_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocksAvailable() {
    $available_plugins = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    return $available_plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_panels_blocks_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_panels_blocks.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = _custom_panels_blocks_get_config();
    $roles = $this->roleStorage->loadMultiple();
    $role_names = [];
    $role_panels_filter = [];
    $admin_roles = [];
    foreach ($roles as $role_name => $role) {
      // Retrieve role names for columns.
      $role_names[$role_name] = $role->label();
      // Fetch permissions for the roles.
      $role_panels_filter[$role_name] = $config->get($role_name) ? $config->get($role_name) : [];
      $admin_roles[$role_name] = $role->isAdmin();
    }
    // Store $role_names for use when saving the data.
    $form['role_names'] = [
      '#type' => 'value',
      '#value' => $role_names,
    ];
    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Block')],
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
    $permissions_by_provider = [];
    $available_plugins = $this->getBlocksAvailable();
    foreach ($available_plugins as $plugin_id => $plugin_definition) {
      $category = _custom_panels_blocks_category_blocks($plugin_definition['category']);
      $permissions_by_provider[$category][$plugin_id] = $plugin_definition['admin_label'];
    }
    foreach ($permissions_by_provider as $provider => $permissions) {
      $form['permissions']['category:' . $provider] = [
        [
          '#wrapper_attributes' => [
            'colspan' => count($role_names) + 1,
            'class' => ['module'],
            'id' => 'module-' . $provider,
          ],
          '#markup' => ucfirst($provider),
        ],
      ];
      foreach ($permissions as $perm => $perm_item) {
        // Fill in default values for the permission.
        $form['permissions'][$perm]['description'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">' . $perm . '</span></div>',
          '#context' => [
            'title' => $perm,
          ],
        ];
        foreach ($role_names as $rid => $name) {
          $form['permissions'][$perm][$rid] = [
            '#title' => $perm,
            '#title_display' => 'visible',
            '#wrapper_attributes' => [
              'class' => ['checkbox'],
            ],
            '#type' => 'checkbox',
            '#default_value' => array_key_exists($perm, $role_panels_filter[$rid]) ? TRUE : FALSE,
            '#attributes' => ['class' => ['rid-' . $rid, 'js-rid-' . $rid]],
            '#parents' => [$rid, $perm],
          ];
          // Show a column of disabled but checked checkboxes.
          if ($admin_roles[$rid]) {
            $form['permissions'][$perm][$rid]['#disabled'] = TRUE;
            $form['permissions'][$perm][$rid]['#default_value'] = TRUE;
          }
        }
      }
    }
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];
    $form['#attached']['library'][] = 'user/drupal.user.permissions';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('custom_panels_blocks.settings');
    foreach ($form_state->getValue('role_names') as $role_name => $name) {
      // Remove empty permissions.
      $permissions = $form_state->getValue($role_name);
      foreach ($permissions as $name => $permission) {
        if ($permission < 1 || $permission == FALSE) {
          unset($permissions[$name]);
        }
      }
      $config->set($role_name, $permissions);
    }
    $config->save();
    $this->messenger()->addStatus($this->t('The changes have been saved.'));
  }

}
