<?php

namespace Drupal\content_synchronizer\Plugin\Action;

use Drupal\content_synchronizer\Form\ExportConfirmForm;
use Drupal\content_synchronizer\Service\ExportManager;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Export entity.
 *
 * @Action(
 *   id = "export_entity_action",
 *   label = @Translation("Export"),
 *   confirm_form_route_name = "content_synchronizer.export_confirm"
 * )
 */
class ExportAction extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore object.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The export manager.
   *
   * @var \Drupal\content_synchronizer\Service\ExportManager
   */
  protected $exportManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $temp_store_factory = NULL, AccountInterface $current_user = NULL) {
    $this->currentUser = $current_user;
    $this->tempStore = $temp_store_factory->get(ExportConfirmForm::FORM_ID);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->exportManager = \Drupal::service(ExportManager::SERVICE_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $info = [
      'url'      => \Drupal::request()->getRequestUri(),
      'entities' => []
    ];
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      $info['entities'][$entity->getEntityTypeId()][] = $entity->id();
    }
    $this->tempStore->set($this->currentUser->id(), $info);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.private_tempstore'),
      $container->get('current_user')
    );
  }

}
