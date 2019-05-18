<?php

namespace Drupal\commerce_license_access_control\Plugin\Commerce\LicenseType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity\BundleFieldDefinition;
use Drupal\node\Entity\Node;
use Drupal\user\UserInterface;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeBase;
use Drupal\commerce_license\ExistingRights\ExistingRightsResult;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\ExistingRightsFromConfigurationCheckingInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\GrantedEntityLockingInterface;

/**
 * Provides a license type which grants a user access to specific entities.
 *
 * @CommerceLicenseType(
 *  id = "commerce_license_access_control",
 *  label = @Translation("Access Control"),
 *  activation_order_state = "complete",
 * )
 */
class AccessControl extends LicenseTypeBase implements ExistingRightsFromConfigurationCheckingInterface, GrantedEntityLockingInterface {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    $label = $this->getLicenseLabel($license->acl_id->value);
    if (isset($label)){
      return $label;
    } else {
      return $this->t('Access Control License');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'acl_id', '',
    ] + parent::defaultConfiguration();
  }

  /**
   * Get the nodes licensed by an ACL id.
   *
   * @param int $acl_id
   *   The id of the ACL.
   *
   * @return array
   *   An array containing the licensed nodes.
   */
  public function getLicensedNodes($acl_id) {
    $results = \Drupal::database()->query("SELECT nid FROM {acl_node} WHERE acl_id = :acl_id", [
      'acl_id' => $acl_id,
    ])->fetchAll();
    $licensed_entities = [];
    foreach ($results as $record) {
      $node = Node::load($record->nid);
      $licensed_entities[] = $node;
    }
    return $licensed_entities;
  }

  /**
   * Get the license label by an ACL id.
   *
   * @param int $acl_id
   *   The id of the ACL.
   *
   * @return string
   *   The label of the license
   */
  public function getLicenseLabel($acl_id) {
    $acl = \Drupal::database()->query("SELECT name FROM {acl} WHERE acl_id = :acl_id", [
      'acl_id' => $acl_id,
    ])->fetchObject();
    return $acl->name;
  }

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
    // Get the owner of the license and add them to the acl.
    $owner = $license->getOwner();
    acl_add_user($license->acl_id->value, $owner->id());

    $nodes = $this->getLicensedNodes($license->acl_id->value);
    foreach ($nodes as $node) {
      if (!empty($node)) {
        \Drupal::entityTypeManager()
          ->getAccessControlHandler('node')
          ->writeGrants($node);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
    // Get the owner of the license and remove them from the acl.
    $owner = $license->getOwner();
    acl_remove_user($license->acl_id->value, $owner->id());

    $nodes = $this->getLicensedNodes($license->acl_id->value);
    foreach ($nodes as $node) {
      if (!empty($node)) {
        \Drupal::entityTypeManager()
          ->getAccessControlHandler('node')
          ->writeGrants($node);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkUserHasExistingRights(UserInterface $user) {
    // Check if user is already added to the acl.
    return ExistingRightsResult::rightsExistIf(
      acl_has_user($this->configuration['acl_id'], $user->id()),
      $this->t("You already have access."),
      $this->t("User @user already has access to ACL @acl.", [
        '@user' => $user->getDisplayName(),
        '@acl' => $this->configuration['acl_id'],
      ])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterEntityOwnerForm(&$form, FormStateInterface $form_state, $form_id, LicenseInterface $license, EntityInterface $form_entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['license_label'] = [
      '#title' => $this->t('License label'),
      '#description' => $this->t('Label to display for this license'),
      '#type' => 'textfield',
      '#default_value' => $this->getLicenseLabel($this->configuration['acl_id']),
      '#required' => TRUE,
    ];

    $licensed_entities = $this->getLicensedNodes($this->configuration['acl_id']);
    $form['licensed_entity'] = [
      '#title' => $this->t('Entity'),
      '#description' => $this->t('Select the node(s) (comma separated) to grant access on'),
      '#type' => 'entity_autocomplete',
      '#default_value' => $licensed_entities,
      '#maxlength' => 4096,
      '#target_type' => 'node',
      '#tags' => TRUE,
      '#validate_reference' => TRUE,
      '#autocreate' => FALSE,
    ];

    $form['grant_view'] = [
      '#title' => $this->t('Grant view'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['grant_view'],
    ];

    $form['grant_update'] = [
      '#title' => $this->t('Grant update'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['grant_update'],
    ];

    $form['grant_delete'] = [
      '#title' => $this->t('Grant delete'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['grant_delete'],
    ];

    $priority = is_null($this->configuration['acl_priority']) ? 0 : $this->configuration['acl_priority'];
    $form['acl_priority'] = [
      '#title' => $this->t('Priority'),
      '#description' => $this->t('Set the acl priority'),
      '#type' => 'number',
      '#default_value' => $priority,
      '#required' => TRUE,
    ];

    $form['acl_id'] = [
      '#type' => 'hidden',
      '#default_value' => $this->configuration['acl_id'],
    ];

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
    $values = $form_state->getValue($form['#parents']);

    $aclId = $values['acl_id'];
    if ($aclId == NULL) {
      $aclId = acl_create_acl('commerce_license_access_control', $values['license_label']);
    } else {
      \Drupal::database()->update('acl')
        ->fields([
          'name' => $values['license_label']
        ])
        ->condition('acl_id', $aclId)
        ->execute();
    }
    $this->configuration['acl_id'] = $aclId;

    // Remove all previous grants.
    \Drupal::database()->delete('acl_node')
      ->condition('acl_id', $aclId)
      ->execute();
    foreach ($values['licensed_entity'] as $entity) {
      acl_node_add_acl($entity["target_id"], $aclId, $values['grant_view'], $values['grant_update'], $values['grant_delete'], $values['acl_priority']);
    }

    $this->configuration['grant_view'] = $values['grant_view'];
    $this->configuration['grant_update'] = $values['grant_update'];
    $this->configuration['grant_delete'] = $values['grant_delete'];
    $this->configuration['acl_priority'] = $values['acl_priority'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['acl_id'] = BundleFieldDefinition::create('string')
      ->setLabel(t('ACL ID'))
      ->setDescription(t('The id of the ACL that this license grants access to.'))
      ->setCardinality(1)
      ->setRequired(TRUE);

    return $fields;
  }

}
