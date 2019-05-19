<?php

namespace Drupal\simple_modal_entity_form\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Modal entity form routes.
 */
class ModalEntityViewController extends ControllerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;


  /**
   * Constructs the controller object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeRepositoryInterface $entity_type_repository, EntityFormBuilderInterface $entity_form_builder, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeRepository = $entity_type_repository;
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.repository'),
      $container->get('entity.form_builder'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Builds the response.
   */
  public function viewEntity($entity_type, $entity, $view_mode) {
    $view = $this->entityTypeManager->getViewBuilder($entity_type)->view($entity, $view_mode);
    return $view;
  }

  /**
   * Gets the entity label.
   */
  public function entityLabel($entity_type, $entity, $view_mode){
    return $entity->label();
  }

  /**
   * Access callback.
   *
   * @param $entity_type
   * @param $entity_id
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function viewEntityAccess($entity_type, $entity, $view_mode, AccountInterface $account) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity);
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $displayRepository */
    $displayRepository = \Drupal::service('entity_display.repository');
    $display_modes = $displayRepository->getViewModeOptionsByBundle($entity_type, $entity->bundle());
    if (!in_array($view_mode, array_keys($display_modes))) {
     return AccessResult::forbidden(t('No valid view display mode.'));
    }
    return $entity->access('view', $account, TRUE);
  }

}
