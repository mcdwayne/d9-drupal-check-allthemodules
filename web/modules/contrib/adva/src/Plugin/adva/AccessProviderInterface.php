<?php

namespace Drupal\adva\Plugin\adva;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines implmentation of AccessProvider plugin.
 */
interface AccessProviderInterface {

  /**
   * Get plugin id.
   *
   * @return string
   *   Plugin id.
   */
  public function getId();

  /**
   * Get plugin label.
   *
   * @return string
   *   Plugin label.
   */
  public function getLabel();

  /**
   * Get consumer instance.
   *
   * Get instance of parent AccessConsumer.
   *
   * @return \Drupal\adva\Plugin\adva\AccessConsumer
   *   Parent AccessConsumer plugin.
   */
  public function getConsumer();

  /**
   * Get supported access operations.
   *
   * @return array
   *   Array of supported access operations that are supported by the plugin.
   */
  public function getOperations();

  /**
   * Whether the provider is applicable to an EntityType.
   *
   * If the provider is not applicable the it will be not shown on the settings
   * page under that entity type and thus cannot be enabled for it.
   *
   * An access provider may check for specific entity type or if the entity (or
   * one of its bundles) has a specific type of field in order the check to work
   * properly. If these conditions are not met, we should not be able to enable
   * the provider for that entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity for which to check access.
   *
   * @return bool
   *   TRUE if the handler should control access.
   */
  public static function appliesToType(EntityTypeInterface $entityType);

  /**
   * Gets the list of node access grants.
   *
   * This function is called to check the access grants for a node. It collects
   * all node access grants for the node from hook_node_access_records()
   * implementations, allows these grants to be altered via
   * hook_node_access_records_alter() implementations, and returns the grants to
   * the caller.
   *
   * Grant definition template:
   *
   * $grants[] = [
   *   'realm' => 'all',
   *   'gid' => 0,
   *   'grant_view' => 0,
   *   'grant_update' => 0,
   *   'grant_delete' => 0,
   *   'langcode' => 'ca',
   * ];
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The $entity to acquire grants for.
   *
   * @return array
   *   The access rules for the node.
   */
  public function getAccessRecords(EntityInterface $entity);

  /**
   * Gets a list of applicable grant relms and grant ids for a given account.
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "view label", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return array
   *   An array whose keys are "realms" of grants, and whose values are arrays
   *   of the grant IDs within this realm that this user is being granted.
   */
  public function getAccessGrants($operation, AccountInterface $account);

  /**
   * Get config data for instance.
   *
   * @return array
   *   Configuration for plugin instance.
   */
  public function getConfiguration();

  /**
   * Build provider config form.
   *
   * Construct sub form for config on the Advanced Access settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The Form structure.
   */
  public function buildConfigForm(array $form, FormStateInterface $form_state);

  /**
   * Build provider config form.
   *
   * Construct sub form for config on the Advanced Access settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|null
   *   The Form structure if updates are being made.
   */
  public function validateConfigForm(array &$form, FormStateInterface $form_state);

  /**
   * Build provider config form.
   *
   * Construct sub form for config on the Advanced Access settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state);

  /**
   * Get helper message text for the admin page.
   *
   * The Helper message should provide a site admin with important information
   * about provider setup and configuration.
   *
   * @param array $definition
   *   Plugin definition array.
   *
   * @return string
   *   Message text.
   */
  public static function getHelperMessage(array $definition);

}
