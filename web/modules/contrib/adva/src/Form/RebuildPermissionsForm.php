<?php

namespace Drupal\adva\Form;

use Drupal\adva\AccessStorage;
use Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide page and form to submit rebuild job.
 */
class RebuildPermissionsForm extends ConfirmFormBase {

  /**
   * Access Consumer being updated.
   *
   * @var \Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface
   */
  protected $consumer;

  /**
   * Access Consumer being updated.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current Access Storage instance.
   *
   * @var \Drupal\adva\AccessStorage
   */
  protected $accessStorage;

  /**
   * Create an instance of the Form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Current Entity Type manager.
   * @param \Drupal\adva\AccessStorage $access_storage
   *   Current Access Storage instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, AccessStorage $access_storage) {
    $this->entityTypeManager = $entity_manager;
    $this->accessStorage = $access_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('adva.access_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adva_access_rebuild';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to rebuild the permissions for %entityType entities?', [
      '%entityType' => $this->entityType->getLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.status');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OverridingAccessConsumerInterface $consumer = NULL) {
    $this->consumer = $consumer;
    $this->entityType = $this->entityTypeManager->getDefinition($consumer->getEntityTypeId());
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Rebuild Permissions');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action rebuilds all permissions on %entityType entites, and may be a lengthy process. Rebuilding will remove all privileges to content and replace them with permissions based on the current modules and settings. This can result in unexpected access to content and this action cannot be undone.', [
      '%entityType' => $this->entityType->getLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->consumer->rebuildCache(TRUE);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
