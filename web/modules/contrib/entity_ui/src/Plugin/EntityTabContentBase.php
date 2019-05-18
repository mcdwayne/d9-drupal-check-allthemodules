<?php

namespace Drupal\entity_ui\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\entity_ui\Entity\EntityTabInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Entity tab content plugins.
 */
abstract class EntityTabContentBase extends PluginBase implements EntityTabContentInterface, ContainerFactoryPluginInterface {

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface;
   */
  protected $bundleInfoService;

  /**
   * The target entity type ID for this plugin instance.
   */
  protected $targetEntityTypeId;

  /**
   * The entity tab using this plugin.
   */
  protected $entityTab;

  /**
   * Creates an EntityForm instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The bundle info service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundle_info_service
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfoService = $bundle_info_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTab(EntityTabInterface $entity_tab) {
    $this->entityTab = $entity_tab;
    $this->targetEntityTypeId = $entity_tab->getTargetEntityTypeID();
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions = [];

    $template = $this->getPermissionTemplate();

    $entity_type_id = $this->entityTab->getTargetEntityTypeID();

    $target_entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $entity_type_label = $target_entity_type->getLabel();

    // Expand permissions for all/own modifier.
    $permissions_data_modifiers = [];

    if ($target_entity_type->entityClassImplements(EntityOwnerInterface::class)) {
      foreach (['any', 'own'] as $modifier) {
        $template_with_modifier = [];

        // Replace the %modifier token.
        $template_with_modifier['name'] = strtr($template['name'], ['%modifier' => $modifier]);
        $template_with_modifier['title'] = $template['title'][$modifier];

        $permissions_data_modifiers[] = $template_with_modifier;
      }
    }
    else {
      $template_with_modifier = [];

      // Strip the %modifier token and its space, and add the permission to the
      // array for the next step.
      $template_with_modifier['name'] = strtr($template['name'], ['%modifier ' => '']);
      $template_with_modifier['title'] = $template['title']['general'];

      $permissions_data_modifiers[] = $template_with_modifier;
    }

    // Expand permissions for bundles.
    $permissions_expanded_for_bundles = [];

    // We don't need to check that the entity type actually uses bundles, as if
    // it doesn't, then its granularity must be 'entity_type'.
    if ($target_entity_type->getPermissionGranularity() == 'bundle') {
      $bundles = $this->bundleInfoService->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle_name => $bundle_info) {
        foreach ($permissions_data_modifiers as $permissions_data) {
          $template_with_bundle = [];

          // Replace the %bundle token.
          $template_with_bundle['name'] = strtr($permissions_data['name'], ['%bundle-id' => $bundle_name]);
          $template_with_bundle['title'] = strtr($permissions_data['title'], ['%bundle-label' => $bundle_info['label']]);

          $permissions_expanded_for_bundles[] = $template_with_bundle;
        }
      }
    }
    else {
      // Strip the %bundle tokens and their spaces.
      foreach ($permissions_data_modifiers as $permissions_data) {
        $template_with_bundle = [];

        $template_with_bundle['name'] = strtr($permissions_data['name'], ['%bundle-id ' => '']);
        $template_with_bundle['title'] = strtr($permissions_data['title'], [' %bundle-label ' => '']);

        $permissions_expanded_for_bundles[] = $template_with_bundle;
      }
    }

    // Finally, replace the entity type tokens, and assemble the permissions
    // into the correct structure. We do this last as it's easier not to have to
    // work with array keys when expanding the permission array.
    $permissions = [];

    foreach ($permissions_expanded_for_bundles as $permission_item) {
      $permission_name = strtr($permission_item['name'], ['%type-id' => $entity_type_id]);

      $permissions[$permission_name] = [
        'title' => strtr($permission_item['title'], ['%type-label' => $entity_type_label]),
      ];
    }

    return $permissions;
  }

  /**
   * Get the template for permissions the entity tab provides.
   *
   * Plugins can override this to make permission machine name and title more
   * specific to the plugin or its configuration.
   *
   * @return array
   *  An array for the permission definition, contaning:
   *    - 'name': The permission machine name.
   *    - 'title': The permission title.
   *    - 'description': The permission machine description, which may be an
   *      empty string.
   */
  protected function getPermissionTemplate() {
    $tab_path  = $this->entityTab->getPathComponent();
    $tab_label = $this->entityTab->label();
    return [
      'name' => "access $tab_path tab on %modifier %type-id %bundle-id entities",
      'title' => [
        // Have to specify each format of the title for modifiers, as otherwise
        // this breaks translation.
        // Type label goes before bundle label to ensure they are grouped
        // logically in the admin UI, as permissions get shown in alphabetical
        // order.
        'general' => t("%type-label: Access @tab_label tab on %bundle-label entities", [
          '@tab_label' => $tab_label,
        ]),
        'any' => t("%type-label: Access @tab_label tab on all %bundle-label entities", [
          '@tab_label' => $tab_label,
        ]),
        'own' => t("%type-label: Access @tab_label tab on own %bundle-label entities", [
          '@tab_label' => $tab_label,
        ]),
      ],
      'description' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function suggestedEntityTabValues($definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public static function appliesToEntityType(EntityTypeInterface $entity_type, $definition) {
    if (empty($definition['entity_types'])) {
      // If the entity_types annotation property is not set (and is thus the
      // default empty array), this applies to all entity types.
      return TRUE;
    }
    else {
      // If the entity_types annotation property is set, check that.
      if (in_array($entity_type->id(), $definition['entity_types'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $target_entity, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }

    $result = $this->hasLogicAccess($target_entity);
    $result = $result->andIf($this->hasPermissionAccess($account, $target_entity));

    return $result;
  }

  /**
   * Checks the tab's access on purely logical grounds.
   *
   * For example, a tab that allows a user to publish an entity would deny
   * access here when the entity is already published.
   *
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity that the entity tab is on.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function hasLogicAccess(EntityInterface $target_entity) {
    return AccessResult::allowed()->addCacheableDependency($target_entity);
  }

  /**
   * Checks the tab's access based on user permissions.
   *
   * This should check the access based on the permissions defined in
   * $this->getPermissions().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity that the entity tab is on.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function hasPermissionAccess(AccountInterface $account, EntityInterface $target_entity) {
    $permission = $this->getPermissionTemplate()['name'];

    //'name' => "access $tab_path tab on %modifier %type-id %bundle-id entities",

    $target_entity_type_id = $this->entityTab->getTargetEntityTypeID();
    $target_entity_type = $target_entity->getEntityType();

    // Replace the entity ID.
    $permission = strtr($permission, [
      '%type-id' => $target_entity_type_id
    ]);

    // Strip or replace the bundle.
    if ($target_entity_type->getPermissionGranularity() == 'bundle') {
      $permission = strtr($permission, [
        '%bundle-id' => $target_entity->bundle(),
      ]);
    }
    else {
      $permission = strtr($permission, [
        // Remove the trailing space as well.
        '%bundle-id ' => '',
      ]);
    }

    // Handle modifier and check permissions.
    if ($target_entity_type->entityClassImplements(EntityOwnerInterface::class)) {
      $permission_any = str_replace('%modifier', 'any', $permission);

      if ($account->hasPermission($permission_any, $account)) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      else {
        $permission_own = str_replace('%modifier', 'own', $permission);

        return AccessResult::allowedIf(
          $account->hasPermission($permission_own, $account) &&
          ($account->id() == $target_entity->getOwnerId())
        )
        ->cachePerPermissions()
        ->cachePerUser()
        ->addCacheableDependency($target_entity);
      }
    }
    else {
      // Strip the modifier and its trailing space.
      $permission = strtr($permission, [
        // Remove the trailing space as well.
        '%modifier ' => '',
      ]);

      $result = AccessResult::allowedIfHasPermission($permission)->cachePerPermissions();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
