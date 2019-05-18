<?php

namespace Drupal\groupmenu\Plugin\GroupContentEnabler;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content enabler for nodes.
 *
 * @GroupContentEnabler(
 *   id = "group_menu",
 *   label = @Translation("Group menu"),
 *   description = @Translation("Adds menus to groups both publicly and privately."),
 *   entity_type_id = "menu",
 *   entity_access = TRUE,
 *   pretty_path_key = "menu",
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the menu to add to the group"),
 *   deriver = "Drupal\groupmenu\Plugin\GroupContentEnabler\GroupMenuDeriver"
 * )
 */
class GroupMenu extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $plugin_id = $this->getPluginId();
    $operations = [];

    if ($group->hasPermission("create $plugin_id entity", $account)) {
      $route_params = ['group' => $group->id(), 'plugin_id' => $plugin_id];
      $operations["groupmenu-create"] = [
        'title' => $this->t('Create menu'),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    // Add custom permissions for managing the menus since we don't have
    // "edit own" / "edit any".
    $plugin_id = $this->getPluginId();

    // Allow permissions here and in child classes to easily use the plugin name
    // and target entity type name in their titles and descriptions.
    $t_args = [
      '%plugin_name' => $this->getLabel(),
      '%entity_type' => $this->getEntityType()->getLowercaseLabel(),
    ];
    $defaults = ['title_args' => $t_args, 'description_args' => $t_args];

    // Use the same title prefix to keep permissions sorted properly.
    $entity_prefix = '%plugin_name - Entity:';
    $relation_prefix = '%plugin_name - Relationship:';

    $permissions["view $plugin_id entity"] = [
      'title' => "$entity_prefix View %entity_type entities",
    ] + $defaults;
    $permissions["create $plugin_id entity"] = [
      'title' => "$entity_prefix Add %entity_type entities",
      'description' => 'Allows you to create a new %entity_type entity and relate it to the group.',
    ] + $defaults;
    $permissions["update $plugin_id entity"] = [
      'title' => "$entity_prefix Edit %entity_type entities",
    ] + $defaults;
    $permissions["delete $plugin_id entity"] = [
      'title' => "$entity_prefix Delete %entity_type entities",
    ] + $defaults;
    $permissions["view $plugin_id content"] = [
      'title' => "$relation_prefix View entity relations",
    ] + $defaults;
    $permissions["create $plugin_id content"] = [
      'title' => "$relation_prefix Add entity relation",
      'description' => 'Allows you to relate an existing %entity_type entity to the group.',
    ] + $defaults;
    $permissions["update $plugin_id content"] = [
      'title' => "$relation_prefix Edit entity relations",
    ] + $defaults;
    $permissions["delete $plugin_id content"] = [
      'title' => "$relation_prefix Delete entity relations",
    ] + $defaults;

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function updateAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    $plugin_id = $this->getPluginId();
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, "update $plugin_id content");
  }

  /**
   * {@inheritdoc}
   */
  protected function deleteAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    $plugin_id = $this->getPluginId();
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, "delete $plugin_id content");
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    $config['node_form_group_menu'] = 1;
    $config['auto_create_group_menu'] = FALSE;
    $config['auto_create_home_link'] = FALSE;
    $config['auto_create_home_link_title'] = 'Home';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();
    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    $form['auto_create_group_menu'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically create a menu when a group is created.'),
      '#description' => $this->t('The menu will be added to the new group as a group menu. The menu will be deleted when group is deleted.'),
      '#default_value' => $configuration['auto_create_group_menu'],
    ];

    $form['auto_create_home_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically create a "Home" link for the menu.'),
      '#description' => $this->t('The "Home" link will link to the canonical URL of the group.'),
      '#default_value' => $configuration['auto_create_home_link'],
      '#states' => [
        'visible' => [
          ':input[name="auto_create_group_menu"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['auto_create_home_link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link title'),
      '#default_value' => $configuration['auto_create_home_link_title'],
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="auto_create_home_link"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

}
