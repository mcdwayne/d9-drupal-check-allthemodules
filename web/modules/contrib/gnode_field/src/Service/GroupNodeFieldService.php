<?php

namespace Drupal\gnode_field\Service;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\GroupMembershipLoader;
use Drupal\node\NodeForm;

/**
 * Provides the 'gnode_field.node_group_ref' service.
 *
 * The service pulls in everything we need to deal with group reference fields
 * on nodes.
 */
class GroupNodeFieldService {

  use StringTranslationTrait;

  /**
   * The field group for group_ref_GROUPTYPE fields.
   */
  const GROUP_NODE_FIELD_GROUP = 'group_group_reference';

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Instance of the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The current user membership.
   *
   * @var \Drupal\group\GroupMembershipLoader
   */
  protected $membership;

  /**
   * All groups the current user is a member of.
   *
   * @var array
   */
  public $memberGroups = [];

  /**
   * Nodes that are content of a group.
   *
   * @var array
   */
  protected $groupContent = [];

  /**
   * The node form.
   *
   * @var array
   */
  protected $form = [];

  /**
   * The state of the Node form.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Determines if this is actually a node.
   *
   * @var bool
   */
  public $validEntity = FALSE;

  /**
   * The node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $entity;

  /**
   * Reference fields on the node.
   *
   * @var array
   */
  protected $referenceFields = [];

  /**
   * GroupNodeFieldService constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   Provides an interface for classes representing the result of routing.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Manages entity type plugin definitions.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Defines an account interface which represents the current user.
   * @param \Drupal\group\GroupMembershipLoader $membership_loader
   *   Loader for wrapped GroupContent entities using the 'group_membership'
   *   plugin.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   Interface for the translation.manager translation service.
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManager $entity_type_manager, AccountInterface $account, GroupMembershipLoader $membership_loader, TranslationInterface $string_translation) {
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
    $this->membership = $membership_loader;
    $this->stringTranslation = $string_translation;
    $this->setUserGroups();
  }

  /**
   * Defines the groups a user has access to.
   *
   * This is put in place so we can account for users that have the bypass
   * group permission.
   */
  protected function setUserGroups() {
    // If the user has permission, we give them access to all groups.
    $bypass = $this->account->hasPermission('bypass group access');
    if ($bypass) {
      $this->memberGroups = $this->entityTypeManager
        ->getStorage('group')
        ->loadMultiple();
    }
    // We allow access to only the groups the user is a member of.
    else {
      // Store the groups the user is a member of.
      foreach ($this->membership->loadByUser($this->account) as $membership) {
        $group = $membership->getGroup();
        $this->memberGroups[$group->id()] = $group;
      }
    }
  }

  /**
   * Getter for groups a user has access to.
   *
   * @return array
   *   Groups the user has access to.
   */
  public function getUserGroups() {
    return $this->memberGroups;
  }

  /**
   * The current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  public function currentUser() {
    return $this->account;
  }

  /**
   * Set the entity we will be performing actions on.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being acted on.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Adds the form and form state.
   *
   * @param array $form
   *   The current node form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the node form.
   *
   * @return \Drupal\gnode_field\Service\GroupNodeFieldService
   *   Returns this.
   */
  public function setFormData(array &$form, FormStateInterface $form_state) {
    $this->form = &$form;
    $this->formState = $form_state;
    return $this;
  }

  /**
   * Performs group content related actions on nodes during operations.
   *
   * This method is necessary when an operation is performed on a node.
   *
   * @param string $op
   *   The operation being taken on the node.
   *     - insert, update, delete.
   *
   * @see \Drupal\group\Plugin\GroupContentEnablerBase
   */
  public function nodeOperations($op) {
    // Groups referenced in all group_ref_GROUPTYPE fields.
    $groups_referenced = [];

    foreach ($this->referenceFields() as $group_ref_field) {
      if ($this->entity->hasField($group_ref_field)) {

        // Get the values for this group reference field.
        $group_values = $this->entity->{$group_ref_field}->getValue();

        // Build an array of referenced groups keyed by their ids.
        foreach ($group_values as $value) {
          $target_id = $value['target_id'];
          $group = $this->entityTypeManager
            ->getStorage('group')
            ->load($target_id);

          $groups_referenced[$target_id] = $group;
        }
      }
    }

    switch ($op) {
      case 'insert':
        $this->nodeInsert($groups_referenced);
        break;

      case 'update':
        $this->nodeUpdate($groups_referenced);
        break;
    }
  }

  /**
   * Called on hook_entity_insert().
   *
   * @param array $groups
   *   Groups referenced on the node.
   */
  protected function nodeInsert(array $groups) {
    // Deny access initially.
    $access = AccessResult::forbidden();

    // The plugin id for this node bundle.
    $plugin_id = $this->getContentPluginId();

    // When inserting a new node we know we are going to add this node
    // as group content to any groups that have been selected in the
    // group ref field.
    /** @var \Drupal\group\Entity\Group $group */
    foreach ($groups as $group) {
      // The user must have the permission to create group content
      // in this group.
      if ($group->hasPermission("create $plugin_id content", $this->account)) {
        $access = AccessResult::allowed();
      }
      // If this node does not already exist as group content then add it.
      $group_content = $group->getContentByEntityId($plugin_id, $this->entity->id());
      if (empty($group_content)) {
        if ($access->isAllowed()) {
          $group->addContent($this->entity, $plugin_id);
        }
      }
    }
  }

  /**
   * Called on hook_entity_update().
   *
   * @param array $groups
   *   Groups referenced on the node.
   */
  protected function nodeUpdate(array $groups) {
    // Deny access initially.
    $access = AccessResult::forbidden();

    // The plugin id for this node bundle.
    $plugin_id = $this->getContentPluginId();

    // Load the group contents for this node. We are comparing the GroupContent
    // entities that have already been created for this node to what is in
    // the group_ref field.
    $group_contents = GroupContent::loadByEntity($this->entity);

    // Group content will briefly be empty if all the
    // original groups are removed before a new one is added.
    if (empty($group_contents)) {
      $this->nodeInsert($groups);
    }

    /** @var \Drupal\group\Entity\GroupContent $group_content */
    foreach ($group_contents as $group_content) {

      /** @var \Drupal\group\Entity\Group $group */
      $group = $group_content->getGroup();
      $gid = $group->id();

      if (!isset($groups[$gid])) {
        // A group, saved as group content, might not exits as an option
        // on a group ref field for this user. If the option isn't there
        // and the node is saved, this group will be removed as group
        // content. We keep this from happening by checking permissions.
        // If the group didn't show up as an option for this user then
        // they shouldn't be able to delete the group.
        if ($group->hasPermission("delete any $plugin_id content", $this->account)) {
          $access = AccessResult::allowed();
        }
        elseif ($this->account->id() == $this->entity->getOwnerId() && $group->hasPermission("delete own $plugin_id content", $this->account)) {
          $access = AccessResult::allowed();
        }
        if ($access->isAllowed()) {
          $group_content->delete();
        }
      }
      else {

        // If there is a group reference on the node and this node does not
        // exist as group content then add it.
        /** @var \Drupal\group\Entity\Group $group */
        foreach ($groups as $group) {
          $access = AccessResult::forbidden();
          if ($group->hasPermission("create $plugin_id entity", $this->account)) {
            $access = AccessResult::allowed();
          }
          // If this node does not already exist as group content then add it.
          $group_content = $group->getContentByEntityId($plugin_id, $this->entity->id());
          if (empty($group_content)) {
            if ($access->isAllowed()) {
              $group->addContent($this->entity, $plugin_id);
            }
          }
        }
      }
    }
  }

  /**
   * Confirm this entity is a node.
   *
   * Also sets the entity.
   *
   * @return \Drupal\gnode_field\Service\GroupNodeFieldService
   *   Returns this.
   */
  public function validateEntity() {
    $valid = $this->validEntity = FALSE;
    $form_object = $this->formState->getFormObject();

    // This must be a node form.
    if ($form_object instanceof NodeForm) {
      /** @var \Drupal\node\Entity\Node $entity */
      $entity = $form_object->getEntity();

      // Iterate over all possible group_ref_BUNDLE fields.
      foreach ($this->referenceFields() as $group_ref_field) {
        // If the node has even a single group field then it is valid.
        if ($entity->hasField($group_ref_field)) {
          $valid = TRUE;
          break;
        }
      }

      // If we have a valid node then set the entity and validEntity properties.
      if ($valid) {
        $this->entity = $entity;
        $this->validEntity = TRUE;
        return $this;
      }
    }

    // This was not a valid entity.
    return $this;
  }

  /**
   * Validation for Group reference fields.
   *
   * Only one of the fields is required. This requires custom validation.
   */
  public function validateGroupRef() {
    $values = FALSE;

    foreach ($this->referenceFields() as $group_ref_field) {
      if ($this->entity->hasField($group_ref_field)) {
        $group_values = $this->formState->getValue($group_ref_field);
        if (!empty($group_values)) {
          $values = TRUE;
        }
      }
    }
    // If node of the group reference fields have a value then we set a form
    // error on both.
    if (!$values) {
      $message = $this->t('A Group is required in one of the Group Types to create this content.');
      foreach ($this->referenceFields() as $group_ref_field) {
        $this->formState->setErrorByName($group_ref_field, $message);
      }
    }
  }

  /**
   * Returns all group types.
   */
  public function groupTypes() {
    return $this->entityTypeManager->getStorage('group_type')->loadMultiple();
  }

  /**
   * Get all groups where this node is group content.
   *
   * @return array|\Drupal\group\Entity\GroupContentInterface[]
   *   Group content.
   */
  public function groupContent() {
    if (empty($this->groupContent)) {
      $this->groupContent = GroupContent::loadByEntity($this->entity);
    }
    return $this->groupContent;
  }

  /**
   * Get group reference fields for this node.
   *
   * @return array
   *   All possible group reference fields.
   */
  public function referenceFields() {
    if (empty($this->referenceFields)) {
      /** @var \Drupal\group\Entity\GroupType $group_type */
      foreach ($this->groupTypes() as $group_type) {
        $id = $group_type->id();
        $this->referenceFields[$id] = 'group_ref_' . $id;
      }
    }
    return $this->referenceFields;
  }

  /**
   * Builds the name for the group reference field.
   *
   * @param string $id
   *   The group type of the group.
   *
   * @return string
   *   The name of the group ref field.
   */
  public function getGroupRef($id) {
    return 'group_ref_' . $id;
  }

  /**
   * Builds the name of the plugin id for gnode.
   *
   * @return string
   *   The name of the plugin id.
   */
  protected function getContentPluginId() {
    return 'group_node:' . $this->entity->bundle();
  }

  /**
   * Query for Groups by bundle.
   *
   * @param string $bundle
   *   The Group Type.
   *
   * @return array
   *   Array of group ids.
   */
  protected function queryGroupsByBundle($bundle) {
    /** @var \Drupal\Core\Entity\EntityStorageBase $storage */
    $storage = $this->entityTypeManager->getStorage('group');
    // Query groups for this group type.
    return $storage->getQuery()
      ->condition('type', $bundle)
      ->execute();
  }

  /**
   * Adds a submit handler to a node form.
   *
   * @param string $handler
   *   The name of the submit handler.
   * @param bool $unshift
   *   Determines if the submit handler as added to the beginning of the array.
   */
  public function submitHandler($handler, $unshift = FALSE) {
    // This is how we should be checking all submit handlers.
    foreach (array_keys($this->form['actions']) as $action) {
      // Ignore previews.
      if ($action !== 'preview') {
        if (isset($this->form['actions'][$action]['#type']) && $this->form['actions'][$action]['#type'] === 'submit') {
          if ($unshift) {
            array_unshift($this->form['actions'][$action]['#submit'], $handler);
          }
          else {
            $this->form['actions'][$action]['#submit'][] = $handler;
          }
        }
      }
    }
  }

  /**
   * Checks for group relationships on this node.
   *
   * A Group can be removed from the node form by a user that didn't have
   * permissions for that Group. This doesn't mean that it was removed as
   * Group Content. This will put the Group(s) back on the node form the next
   * time the node is edited. This will ensure that the node form stays in
   * sync with Group Content.
   */
  public function replaceGroupContentGroups() {
    // This node is a group content entity so we make sure that the group
    // reference(s) on this node are actually set on the node form.
    /** @var \Drupal\group\Entity\Group $group_content */
    foreach ($this->groupContent() as $group_content) {
      $gid = $group_content->get('gid')->getValue()[0]['target_id'];
      $group = $this->entityTypeManager->getStorage('group')->load($gid);
      $group_ref_field = $this->getGroupRef($group->bundle());

      if ($this->entity->hasField($group_ref_field)) {
        // If this node does not have relationship as group content for this
        // group_ref field we must check the current status from Group. If we
        // find that there is a Group Content relationship then we set this as
        // one of the default values for the group reference field.
        if (!in_array($gid, $this->form[$group_ref_field]['widget']['#default_value'])) {
          $this->form[$group_ref_field]['widget']['#default_value'][] = $gid;
        }
      }
    }
  }

  /**
   * Defines groups that will show as options.
   *
   * Show me all groups I have access to as `#options` in the ref field.
   * Groups I am not a member of will be filtered out of the reference field
   * `#options` and displayed below the field. They will be added back to the
   * ref field in `hook_node_presave()` before the Node is fully saved.
   */
  public function getReferenceableGroups() {

    // When a node is updated and a user does not have access to a group in a
    // group reference field we unset that value from the field. This ensures
    // that the user can't add/delete this group from the group reference field.
    // This stops adding or deleting the node as group content but it does
    // remove that group from the group reference field's default values. This
    // will look for this node as group content and will place that group back
    // in the group reference field.
    if (!$this->entity->isNew()) {
      $this->replaceGroupContentGroups();
    }

    // Filter the options the current user sees based on the user's permission.
    foreach ($this->referenceFields() as $bundle => $group_ref_field) {

      if ($this->entity->hasField($group_ref_field)) {

        // Load the groups for this group type.
        $gids = $this->queryGroupsByBundle($bundle);
        $groups = $this->entityTypeManager
          ->getStorage('group')
          ->loadMultiple($gids);

        // If the entity is new we set a default group value if the user is a
        // member of a single group.
        if ($this->entity->isNew()) {

          // If the user is a member of a single group we need the group id.
          if (count($this->memberGroups) === 1 && $single_gid = array_keys($this->memberGroups)[0]) {
            /** @var \Drupal\Group\Entity\Group $member_group */
            $member_group = $this->memberGroups[$single_gid];
            if ($member_group->bundle() === $bundle) {
              // We are creating new content and this user is a member of a
              // single group. Set that group as the default value and disable
              // the field. They aren't able to add another group and this
              // content must relate to at least one group.
              $this->form[$group_ref_field]['widget']['#default_value'][] = $single_gid;
              // @todo: If we disable this field then the value isn't found
              // on the form state when the field is validated...why?
              // $this->form[$group_ref_field]['widget']['#attributes']
              // ['disabled'] = 'disabled';
            }
          }
        }

        // The options are an array of group ids. We use these ids to determine
        // the groups the user should have access to in our group_ref_* fields.
        $options = &$this->form[$group_ref_field]['widget']['#options'];

        // The plugin id used to check the permission.
        $plugin_id = $this->getContentPluginId();

        // Filter all groups based on the "create PLUGIN_ID content" permission.
        /** @var \Drupal\group\Entity\Group $group */
        foreach ($groups as $group) {
          // Deny access initially.
          $access = AccessResult::forbidden();

          // Allows you to create a new %entity_type entity and relate it to
          // the group. This will check the user's role for this permission.
          if ($group->hasPermission("create $plugin_id entity", $this->account)) {
            $access = AccessResult::allowed();
          }

          // If the user can create a node and assign it as Group Content skip
          // to the next Group.
          if ($access->isAllowed()) {
            continue;
          }

          // The user doesn't have access to add this node as group content.
          // Remove it from the group reference field options.
          unset($options[$group->id()]);
        }

        // Hide the group ref field if the only option is '_none'.
        if (count($options) <= 1) {
          $this->form[$group_ref_field]['#access'] = FALSE;
          continue;
        }
      }
    }
  }

}
