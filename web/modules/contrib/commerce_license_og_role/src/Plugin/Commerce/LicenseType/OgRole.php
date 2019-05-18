<?php

namespace Drupal\commerce_license_og_role\Plugin\Commerce\LicenseType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\ExistingRights\ExistingRightsResult;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeBase;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\ExistingRightsFromConfigurationCheckingInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\GrantedEntityLockingInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @CommerceLicenseType(
 *   id = "commerce_license_og_role",
 *   label = @Translation("OG Role"),
 * )
 */
class OgRole extends LicenseTypeBase implements
  ContainerFactoryPluginInterface,
  ExistingRightsFromConfigurationCheckingInterface,
  GrantedEntityLockingInterface {

  /**
   * Indicates that the default membership type for the group type is used.
   *
   * This requires the PR at https://github.com/Gizra/og/pull/333; without it
   * the membership type named 'default' will be used.
   */
  const GROUP_TYPE_DEFAULT_MEMBERSHIP_TYPE = '';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The og access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The og group type manager service.
   *
   * @var
   */
  protected $ogGroupTypeManager;

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates a OgRole instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The og access service.
   * @param  $og_group_type_manager
   *   The og group type manager service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The og membership manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    OgAccessInterface $og_access,
    $og_group_type_manager,
    MembershipManagerInterface $og_membership_manager,
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->ogAccess = $og_access;
    $this->ogGroupTypeManager = $og_group_type_manager;
    $this->ogMembershipManager = $og_membership_manager;
    $this->currentUser = $current_user;
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
      $container->get('entity_type.bundle.info'),
      $container->get('og.access'),
      $container->get('og.group_type_manager'),
      $container->get('og.membership_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'license_og_group' => [
        'target_type' => '',
        'target_id' => '',
      ],
      'license_og_role' => '',
      'license_og_membership_type' => static::GROUP_TYPE_DEFAULT_MEMBERSHIP_TYPE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    $args = [
      '@role' => $license->license_og_role->entity->label(),
      '@group' => $license->license_og_group->entity->label(),
    ];
    return $this->t('@role role in group @group license', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
    // Get the owner of the license, and grant them the role in the group.
    $owner = $license->getOwner();
    $licensed_group = $license->license_og_group->entity;
    $licensed_role = $license->license_og_role->entity;
    $membership_type = $license->license_og_membership_type->value;

    // Check for an existing membership.
    // Load all states (though see https://github.com/Gizra/og/issues/335; this
    // is using undocumented API).
    $membership = $this->ogMembershipManager->getMembership($licensed_group, $owner, []);

    if (empty($membership)) {
      if (empty($membership_type)) {
        // Rely on the default value for the membership parameter to create
        // the type of membership set for the group type.
        // NOTE: This the PR at https://github.com/Gizra/og/pull/333; without it
        // the membership type named 'default' will be used.
        $membership = $this->ogMembershipManager->createMembership($licensed_group, $owner);
      }
      else {
        // Pass in the membership type from the license field value.
        $membership = $this->ogMembershipManager->createMembership($licensed_group, $owner, $membership_type);
      }

      // TODO: handle the case where the user is not initially a member of the
      // group, and is being granted a custom role. This initial state should
      // be stored, so revokeLicense() knows whether to delete the entire
      // membership.
    }

    // Set a pending membership to be activated. This allows for the membership
    // entity to be created in checkout.
    if ($membership->getState() == OgMembershipInterface::STATE_PENDING) {
      $membership->setState(OgMembershipInterface::STATE_ACTIVE);
    }

    if ($membership->getState() == OgMembershipInterface::STATE_BLOCKED) {
      // The Commerce availability check should cover this in
      // checkUserHasExistingRights(), but throw an exception here just in case.
      $licensed_group_label = $licensed_group->label();
      throw new \Exception("Membership of the {$licensed_group_label} group is blocked.");
    }

    $membership->addRole($licensed_role);

    $membership->save();

    // Set the membership on the license.
    $license->set('license_og_membership', $membership);
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
    // Get the owner of the license, and grant them the role in the group.
    $owner = $license->getOwner();
    $licensed_group = $license->license_og_group->entity;
    $licensed_role = $license->license_og_role->entity;

    // Only load active memberships: if something else interfered with the
    // membership and set it to blocked or pending, we might as well leave it
    // unchanged.
    $membership = $this->ogMembershipManager->getMembership($licensed_group, $owner);

    if (empty($membership)) {
      // Something's deleted the membership from beneath us: log a warning.
      \Drupal::logger('commerce_license_og_role')->error("Attempted to revoke license ID @license-id, but no membership for the @group-type group @group-id was found.", [
        '@license-id' => $license->id(),
        '@group-type' => $licensed_group->getEntityTypeId(),
        '@group-id' => $licensed_group->id(),
      ]);

      return;
    }

    if ($licensed_role->getName() == OgRoleInterface::AUTHENTICATED) {
      // If the role is plain membership, delete the membership.
      // TODO: consider moving the membership to 'blocked'.
      $membership->delete();

      // Remove the reference to the deleted membership.
      $license->set('license_og_membership', NULL);
    }
    else {
      // If the role is a custom role, revoke it.
      // TODO: handle the case where the user was not initially a member of the
      // group -- should the membership should also be deleted? Should this
      // behaviour be configurable via a field on the license?
      $membership->revokeRole($licensed_role);
      $membership->save();

      // TODO: consider whether to keep or delete the reference to the
      // membership.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkUserHasExistingRights(UserInterface $user) {
    $group_entity_type_id = $this->configuration['license_og_group']['target_type'];
    $group_id = $this->configuration['license_og_group']['target_id'];
    $licensed_group = \Drupal::service('entity_type.manager')->getStorage($group_entity_type_id)->load($group_id);

    // Check for a membership for the user that's in any state.
    // (The empty array parameter acts as a wildcard: see
    // https://github.com/Gizra/og/issues/335.)
    $membership = $this->ogMembershipManager->getMembership($licensed_group, $user, []);

    // If there is no group membership, the user definitely does not have the
    // group role!
    if (empty($membership)) {
      return ExistingRightsResult::rightsDoNotExist();
    }

    // If the membership is pending, then the user may purchase it to activate
    // it: effectively, rights do not exist.
    if ($membership->getState() == OgMembershipInterface::STATE_PENDING) {
      return ExistingRightsResult::rightsDoNotExist();
    }

    // If there is a membership, but it is blocked, do not allow the user to
    // purchase the license.
    if ($membership->getState() == OgMembershipInterface::STATE_BLOCKED) {
      // TODO: This is a bit of an abuse of the system, as technically rights
      // don't exist. Clean up: add our own Commerce availability checker to do
      // this.
      return ExistingRightsResult::rightsExist(
        $this->t("You may not purchase the @role role in the @group group.", $args),
        $this->t("The user may not purchase the @role role in the @group group.", $args)
      );
    }

    // If the membership is active or pending, check the roles it has.
    // TODO: This API in OG is likely to change!
    // See https://github.com/Gizra/og/issues/327
    $og_roles = $membership->getRoles();
    foreach ($og_roles as $role) {
      if ($role->id() == $this->configuration['license_og_role']) {
        $args = [
          '@group' => $licensed_group->label(),
          '@role' => $role->label(),
        ];

        return ExistingRightsResult::rightsExist(
          $this->t("You already have the @role role in the @group group.", $args),
          $this->t("The user already has the @role role in the @group group.", $args)
        );
      }
    }

    // No role matched: rights do not exist.
    return ExistingRightsResult::rightsDoNotExist();
  }

  /**
   * {@inheritdoc}
   */
  public function alterEntityOwnerForm(&$form, FormStateInterface $form_state, $form_id, LicenseInterface $license, EntityInterface $form_entity) {
    if ($form_entity->getEntityTypeId() != 'og_membership') {
      // Only act on a og_membership form.
      return;
    }

    if ($form_entity->getGroupEntityType() != $license->license_og_group->target_type || $form_entity->getGroupId() != $license->license_og_group->target_id) {
      // Only act if this OG membership is for the group the license is for.
      return;
    }

    if ($form_id == 'og_membership_default_delete_form') {
      // Disable the delete button on the delete confirmation form.
      // TODO: show a message to explain why deletion is not allowed.
      $form['actions']['submit']['#disabled'] = TRUE;
    }
    else {
      // Disable the role and the delete link on the edit form.
      $licensed_role = $license->license_og_role->entity;
      if ($licensed_role->getName() != OgRoleInterface::AUTHENTICATED) {
        // Disable the checkbox for the licensed role.
        $licensed_role_id = $licensed_role->id();
        $form['roles']['widget'][$licensed_role_id]['#disabled'] = TRUE;
        $form['roles']['widget'][$licensed_role_id]['#description'] = t("This role is granted by a license. It cannot be removed manually.");
      }

      // For all roles, prevent deletion.
      // TODO: show a message to explain why deletion is not allowed.
      $form['actions']['delete']['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // The group form element must only show the groups in which the current
    // user has access to grant licenses.

    $group_type_options = [];
    $group_bundles = $this->ogGroupTypeManager->getAllGroupBundles();
    $all_bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();
    foreach ($group_bundles as $entity_type_id => $bundles) {
      $entity_type_label = $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
      foreach ($bundles as $bundle) {
        $bundle_label = $all_bundle_info[$entity_type_id][$bundle]['label'];
        $group_type_options["$entity_type_id:$bundle"] = "$entity_type_label : $bundle_label";
      }
    }

    // Setting default values and options for form elements must be done in a
    // FormAPI #process callback, because prior to that we don't have the
    // #parents and #array_parents for our form elements, and so we can't get
    // hold of the form values submitted by ajax updates.
    // This is because we're embedded in an IEF in the PV form, and that has
    // a different strucure depending on whether this is a new PV or an
    // existing one.
    // @see \Drupal\Core\Plugin\PluginFormInterface::buildConfigurationForm()
    $form['#process'][] = [$this, 'processForm'];

    $form['group_type'] = [
      '#type' => 'select',
      '#title' => $this->t("Group type"),
      '#options' => $group_type_options,
      '#empty_option' => $this->t('- Select a group type and bundle -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'ajaxUpdateGroup'],
        // This gets a suffix appended in processForm() to ensure uniqueness.
        'wrapper' => 'og-group-wrapper',
      ],
      '#button_type' => 'select',
      '#limit_validation_errors' => array(),
    ];
    // For a form editing an existing PV, get the group so we can set a default
    // value for the group type element that matches it. The group type element
    // doesn't correspond to any configuration setting; it's effectively just a
    // filter.
    if (!empty($this->configuration['license_og_group']['target_type'])) {
      $group_entity = $this->entityTypeManager->getStorage($this->configuration['license_og_group']['target_type'])->load($this->configuration['license_og_group']['target_id']);
      $form['group_type']['#default_value'] = $group_entity->getEntityTypeId() . ':' . $group_entity->bundle();
    }

    // Container for the elements that are updated by the group type element.
    $form['group_wrapper'] = [
      '#type' => 'container',
      // This gets a suffix appended in processForm() to ensure uniqueness.
      '#attributes' => ['id' => 'og-group-wrapper'],
    ];

    // TODO: Use a dynamic_entity_autocomplete form element when
    // https://www.drupal.org/node/2766213 lands.
    $form['group_wrapper']['license_og_group'] = [
      '#type' => 'select',
      '#title' => $this->t("Group"),
      '#description' => $this->t("The group in which this license will grant a role in."),
      // The options depend on the group type, so are set in processForm().
      '#options' => [],
      '#required' => TRUE,
      // Workaround for core bug: https://www.drupal.org/node/2906113
      '#empty_option' => $this->t('- Select a group -'),
      '#default_value' => $this->configuration['license_og_group']['target_type'] . ':' . $this->configuration['license_og_group']['target_id'],
      /*
      '#ajax' => [
        'callback' => [$this, 'ajaxUpdateRole'],
        'wrapper' => 'og-role-wrapper',
      ],
      */
    ];

    $form['group_wrapper']['license_og_role'] = [
      '#type' => 'select',
      '#title' => $this->t("Group role"),
      '#description' => $this->t("The role this license grants. A role other than 'Member' will also grant membership."),
      // The options depend on the group type, so are set in processForm().
      '#options' => [],
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a group role -'),
      '#default_value' => $this->configuration['license_og_role'],
      /*
      '#prefix' => '<div id="og-role-wrapper">',
      '#suffix' => '</div>',
      */
    ];

    $membership_types = $this->entityTypeManager->getStorage('og_membership_type')->loadMultiple();
    $membership_type_options = [];
    $membership_type_options[static::GROUP_TYPE_DEFAULT_MEMBERSHIP_TYPE] = $this->t("**Group type default**");
    foreach ($membership_types as $id => $type) {
      $membership_type_options[$id] = $type->label();
    }
    $form['license_og_membership_type'] = [
      '#type' => 'select',
      '#title' => t('Membership type'),
      '#description' => $this->t("The type of OG membership to create when granting the license. Note that this will not apply when the license owner has an existing membership."),
      '#options' => $membership_type_options,
      '#default_value' => $this->configuration['license_og_membership_type'],
    ];

    return $form;
  }

  /**
   * Process callback: sets group and role options and ajax IDs.
   *
   * This has to be done in a #process callback, as we need the #parent property
   * to get the form values during an ajax update and to set unique wrapper IDs.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    // Get the form values for our part of the form.
    $form_value = $form_state->getValue($element['#parents']);

    // Figure out the group type to use for building the group and role options.
    if (empty($form_value)) {
      // This is the first trip through the form, before any ajax update.
      if (empty($this->configuration['license_og_group']['target_type'])){
        // Editing a new product variation, with no plugin configuration.

        // There is no group type.
        $group_type_value = NULL;
      }
      else {
        // Editing an existing product variation, with a configured plugin.

        // The group type value is the default on the form element, which has
        // been set to match the plugin's configuration.
        $group_type_value = $element['group_type']['#default_value'];
      }
    }
    else {
      // This is an ajax update.

      // The group type is in the form values.
      $group_type_value = $form_value['group_type'];
    }

    // Now that we have the group type value, add the options to the elements
    // that depend on it.
    $element['group_wrapper']['license_og_group']['#options'] = $this->getGroupOptions($group_type_value);
    $element['group_wrapper']['license_og_role']['#options'] = $this->getRoleOptions($group_type_value);

    // If after an ajax update, there are still no group options, show an
    // explanation of why.
    if (!empty($group_type_value) && empty($element['group_wrapper']['license_og_group']['#options'])) {
      if ($this->currentUser->hasPermission('grant group roles with licenses in any group')) {
        $element['group_wrapper']['license_og_group']['#description'] .= ' ' . $this->t("WARNING: There are no groups.");
      }
      else {
        $element['group_wrapper']['license_og_group']['#description'] .= ' ' . $this->t("WARNING: There are no groups for which you have access to create licenses.");
      }
    }

    // Set unique wrapper IDs for the ajax elements.
    // Because this form appears in a multi-valued inline entity form, there can
    // be multiple copies of it open at the same time. Therefore, our ajax IDs
    // need to include the delta to be unique.
    if ($element['#parents'][3] == 'entities') {
      // Editing an existing product variation. Use the delta (of the product
      // variation field) as a suffix.
      $suffix = $element['#parents'][4];
    }
    else {
      $suffix = 'new';
    }

    $element['group_type']['#ajax']['wrapper'] .= '-' . $suffix;
    $element['group_wrapper']['#attributes']['id'] .= '-' . $suffix;

    return $element;
  }

  /**
   * Gets the options for the group form element.
   *
   * Helper for processForm().
   *
   * @param string $group_type_value
   *   The form value for the group type.
   *
   * @return array
   *   An array of form options.
   */
  protected function getGroupOptions($group_type_value) {
    $options = [];

    if (empty($group_type_value)) {
      // On a new form, we don't have a group type set yet, so we can't do
      // anything.
      return $options;
    }

    list ($group_entity_type_id, $group_bundle) = explode(':', $group_type_value);

    // Load some suitable groups.
    if ($this->currentUser->hasPermission('grant group roles with licenses in any group')) {
      // The user has the master permission: load all groups of the selected
      // bundle.
      $group_entities = $this->entityTypeManager->getStorage($group_entity_type_id)->loadByProperties([
        // Quick and dirty hack!
        // TODO! Not all entity types will use 'type' as their bundle key!
        'type' => $group_bundle,
      ]);
      foreach ($group_entities as $id => $entity) {
        $options["$group_entity_type_id:$id"] = $entity->label();
      }
    }
    else {
      // Get the group memberships for the current user.
      // TODO: this needs to take into account the group type element and
      // filter by it.
      $user_memberships = $this->ogMembershipManager->getMemberships($this->currentUser);
      foreach ($user_memberships as $membership) {
        // Skip group memberships where the current user does not have the
        // permission to grant roles with licenses.
        if (!$membership->hasPermission('grant group roles with licenses')) {
          continue;
        }

        $group = $membership->getGroup();
        $options[$group->getEntityTypeId() . ':' . $group->id()] = $group->label();
      }
    }

    natcasesort($options);

    return $options;
  }

  /**
   * Gets the options for the role form element.
   *
   * Helper for processForm().
   *
   * TODO: Roles can apparently be per-group too.
   *
   * @param string $group_type_value
   *   The form value for the group type.
   *
   * @return array
   *   An array of form options.
   */
  protected function getRoleOptions($group_type_value) {
    $options = [];

    if (empty($group_type_value)) {
      // On a new form, we don't have a group type set yet, so we can't do
      // anything.
      return $options;
    }

    list ($group_entity_type_id, $group_bundle) = explode(':', $group_type_value);

    $roles = $this->entityTypeManager->getStorage('og_role')->loadByProperties([
      // The group type ajax update will have set these.
      'group_type' => $group_entity_type_id,
      'group_bundle' => $group_bundle,
    ]);
    foreach ($roles as $role) {
      // Skip the non-member role.
      if ($role->getName() == OgRoleInterface::ANONYMOUS) {
        continue;
      }
      // Skip the admin role.
      if ($role->getName() == OgRoleInterface::ADMINISTRATOR) {
        continue;
      }

      $options[$role->id()] = $role->label();
    }

    natcasesort($options);

    return $options;
  }

  /**
   * AJAX callback displaying the group and role elements.
   */
  public function ajaxUpdateGroup(array $form, FormStateInterface $form_state) {
    // Make the array parents for the group wrapper container.
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($parents);
    $parents[] = 'group_wrapper';

    $group_form_element = NestedArray::getValue($form, $parents);
    return $group_form_element;
  }

  /**
   * AJAX callback displaying the roles element.
   *
   * TODO: not in use! Role currently depends only on the group type.
   * Implement this if roles specific to individual groups are needed.
   */
  public function ajaxUpdateRole(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($parents);
    $parents[] = 'license_og_role';

    return NestedArray::getValue($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Form validation handler.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    list($group_entity_type_id, $group_id) = explode(':', $values['group_wrapper']['license_og_group']);
    $this->configuration['license_og_group'] = [
      'target_type' => $group_entity_type_id,
      'target_id' => $group_id,
    ];

    $this->configuration['license_og_role'] = $values['group_wrapper']['license_og_role'];

    $this->configuration['license_og_membership_type'] = $values['license_og_membership_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // Get the IDs of entity types that have bundles which are groups.
    $group_bundles = $this->ogGroupTypeManager->getAllGroupBundles();
    $group_entity_types = array_keys($group_bundles);

    $fields['license_og_group'] = BundleFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('OG Group'))
      ->setDescription(t('The OG Group this license grants a role in.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('entity_type_ids', $group_entity_types);
      // TODO: set settings to limit to bundles which are groups.

    $fields['license_og_role'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('OG Role'))
      ->setDescription(t('The OG role this license grants access to.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'og_role');

    $fields['license_og_membership_type'] = BundleFieldDefinition::create('string')
      // Not a reference field as we also need to store the value for using
      // the group type default.
      ->setLabel(t('OG membership type'))
      ->setDescription(t('The type of OG membership created when this license grants membership.'))
      ->setCardinality(1)
      ->setRequired(TRUE);

    $fields['license_og_membership'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('OG membership'))
      ->setDescription(t('The OG membership which is granted or upgraded by this license.'))
      ->setCardinality(1)
      ->setSetting('target_type', 'og_membership');

    return $fields;
  }

}
