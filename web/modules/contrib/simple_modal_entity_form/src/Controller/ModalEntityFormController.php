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
class ModalEntityFormController extends ControllerBase {

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
  public function editEntity($entity_type, $entity, $form_mode) {
    $response = new AjaxResponse();
    $form = $this->entityFormBuilder->getForm($entity, 'modal', ['modal_form_display' => EntityFormDisplay::collectRenderDisplay($entity, $form_mode)]);
    $form['#attached']['library'][] = 'simple_modal_entity_form/simple_modal_entity_form.ajax';
    $response->addCommand(new OpenDialogCommand('#modal-entity-form', t('Edit @label', ['@label' => $entity->label()]), $form, ['max-width:900px', 'width' => '90%', 'modal' => TRUE]));
    return $response;
  }


  /**
   * Builds the response.
   */
  public function deleteEntity($entity_type, $entity) {
    $response = new AjaxResponse();
    $form = $this->entityFormBuilder->getForm($entity, 'delete');
    $form['#attached']['library'][] = 'simple_modal_entity_form/simple_modal_entity_form.ajax';
    $response->addCommand(new OpenModalDialogCommand(t('Delete @label', ['@label' => $entity->label()]), $form, ['width' => '800px']));
    return $response;
  }

  /**
   * Controller for the add entity form.
   *
   * @param string $entity_type
   *
   * @param string $bundle
   * @param string $form_mode
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *
   * @todo Upcast the entity type and bundle.
   */
  public function addEntity($entity_type, $bundle, $form_mode) {

    $response = new AjaxResponse();
    $entity_info = $this->entityTypeManager->getDefinition($entity_type);
    $form['#attached']['library'][] = 'simple_modal_entity_form/simple_modal_entity_form.ajax';
    $values = [];
    if ($bundle_key = $entity_info->getKey('bundle')) {
      if ($entity_info->getBundleEntityType()) {
        $values[$bundle_key] = $bundle;
      }
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      $label = $bundle_info[$bundle]['label'];
    }
    else {
      $label = $entity_info->getLabel();
    }
    $entity = $this->entityTypeManager->getStorage($entity_type)->create($values);
    $form = $this->entityFormBuilder->getForm($entity, 'modal', ['modal_form_display' => EntityFormDisplay::collectRenderDisplay($entity, $form_mode)]);
    $response->addCommand(new OpenModalDialogCommand(t('Create @type', ['@type' => $label]), $form, ['width' => '1200px']));
    return $response;
  }

  /**
   * Access callback.
   *
   * @param $entity_type
   * @param null $bundle
   * @param string $form_mode
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultForbidden
   */
  public function createEntityAccess($entity_type, $bundle, $form_mode, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $displayRepository */
    $displayRepository = \Drupal::service('entity_display.repository');
    $display_modes = $displayRepository->getFormModeOptions($entity_type);
    if (!in_array($form_mode, array_keys($display_modes))) {
      return AccessResult::forbidden(t('No valid form display mode.'));
    }
    return $this->entityTypeManager->getAccessControlHandler($entity_type)->createAccess($bundle, $account, [], TRUE);
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
   */
  public function editEntityAccess($entity_type, $entity, $form_mode, AccountInterface $account) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity);
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $displayRepository */
    $displayRepository = \Drupal::service('entity_display.repository');
    $display_modes = $displayRepository->getFormModeOptionsByBundle($entity_type, $entity->bundle());
    if (!in_array($form_mode, array_keys($display_modes))) {
     return AccessResult::forbidden(t('No valid form display mode.'));
    }
    return $entity->access('update', $account, TRUE);
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
   */
  public function deleteEntityAccess($entity_type, $entity, AccountInterface $account) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity);
    return $entity->access('delete', $account, TRUE);
  }

}
