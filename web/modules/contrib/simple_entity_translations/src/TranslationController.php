<?php

namespace Drupal\simple_entity_translations;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_entity_translations\Form\EntityTranslateForm;
use Drupal\simple_entity_translations\Form\FilterForm;
use Drupal\simple_entity_translations\Form\ListTranslateForm;

/**
 * Class TranslationController.
 */
class TranslationController extends ControllerBase {

  /**
   * Gets translation form for a single entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param string|null $entity_type_id
   *   Entity type id.
   *
   * @return array
   *   The form.
   */
  public function getEntityForm(RouteMatchInterface $routeMatch, $entity_type_id = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $routeMatch->getParameter($entity_type_id);
    $form = $this->formBuilder()->getForm(EntityTranslateForm::class, $entity);
    return $form;
  }

  /**
   * Gets translation form for a entity list.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param string|null $entity_type_id
   *   Entity type id.
   *
   * @return array
   *   The form.
   */
  public function getListForm(RouteMatchInterface $routeMatch, $entity_type_id = NULL) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityBundleBase $entity */
    $entity = $routeMatch->getParameter($entity_type_id);
    $contentEntityType = $this->entityTypeManager()->getDefinition($entity->getEntityType()->getBundleOf());

    $filterForm = $this->formBuilder()->getForm(FilterForm::class, $contentEntityType);
    $translationForm = $this->formBuilder()->getForm(ListTranslateForm::class, $entity);
    return [
      'filter' => $filterForm,
      'translation' => $translationForm,
    ];
  }

  /**
   * Route access handler.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   * @param string|null $entity_type_id
   *   Entity type id.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(RouteMatchInterface $routeMatch, AccountInterface $account, $entity_type_id = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $routeMatch->getParameter($entity_type_id);
    if ($entity instanceof ContentEntityInterface) {
      if ($bundleEntityTypeName = $entity->getEntityType()->getBundleEntityType()) {
        /** @var \Drupal\Core\Config\Entity\ConfigEntityBundleBase $bundleEntity */
        $bundleEntity = $this->entityTypeManager()->getStorage($bundleEntityTypeName)->load($entity->bundle());
      }
    }
    elseif ($entity instanceof ConfigEntityBundleBase) {
      $bundleEntity = $entity;
    }

    if (isset($bundleEntity)) {
      return AccessResult::allowedIfHasPermission($account, 'access simple entity translations')
        ->andIf(AccessResult::allowedIf($bundleEntity->getThirdPartySetting('simple_entity_translations', 'enabled', FALSE)))
        ->addCacheableDependency($bundleEntity)
        ->cachePerPermissions();
    }

    return AccessResult::forbidden()->setCacheMaxAge(0);
  }

}
