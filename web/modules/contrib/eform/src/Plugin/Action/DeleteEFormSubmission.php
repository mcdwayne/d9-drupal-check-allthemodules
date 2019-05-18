<?php

namespace Drupal\eform\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Redirects to a node deletion form.
 *
 * @Action(
 *   id = "eform_submission_delete_action",
 *   label = @Translation("Delete EForm Submission"),
 *   type = "eform_submission"
 * )
 */
class DeleteEFormSubmission extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface $entityStorage
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('delete', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->entityStorage->delete([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('eform_submission')
    );
  }

  /**
   * Constructs a new DeleteNode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityStorageInterface $entity_storage) {
    $this->currentUser = $current_user;
    $this->entityStorage = $entity_storage;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
