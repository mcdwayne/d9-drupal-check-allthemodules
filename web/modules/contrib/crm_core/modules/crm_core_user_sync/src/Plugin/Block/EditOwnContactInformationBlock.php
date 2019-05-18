<?php

namespace Drupal\crm_core_user_sync\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\crm_core_contact\Entity\Individual;
use Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Edit own contact information' block.
 *
 * @Block(
 *   id = "crm_core_user_sync_edit_own_contact_information",
 *   admin_label = @Translation("Edit own contact information"),
 *   category = @Translation("CRM Core")
 * )
 */
class EditOwnContactInformationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Relation service.
   *
   * @var \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface
   */
  protected $relation;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * Constructs a new EditOwnContactInformationBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface $relation
   *   Relation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CrmCoreUserSyncRelationInterface $relation, EntityTypeManagerInterface $entityTypeManager, FormBuilderInterface $formBuilder, AccountProxyInterface $accountProxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->relation = $relation;
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
    $this->accountProxy = $accountProxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('crm_core_user_sync.relation'),
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'edit own contact information');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    if ($individualId = $this->relation->getIndividualIdFromUserId($this->accountProxy->id())) {
      /* @var $individual \Drupal\crm_core_contact\Entity\Individual */
      /* @var $form \Drupal\crm_core_contact\Form\IndividualForm */
      $individual = Individual::load($individualId);

      $form = $this->entityTypeManager->getFormObject($individual->getEntityTypeId(), 'default');
      $form->setEntity($individual);
      $form_state = new FormState();
      $form_state->disableRedirect();
      $build = $this->formBuilder->buildForm($form, $form_state);

      unset($build['actions']['delete']);

      return $build;
    }
  }

}
