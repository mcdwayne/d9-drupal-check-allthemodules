<?php

namespace Drupal\core_extend\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Controller\EntityController as CoreEntityController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class EntityController.
 *
 *  Returns responses for extended-Entity routes.
 *
 * @package Drupal\core_extend\Extend\Controller
 */
class EntityController extends CoreEntityController implements ContainerInjectionInterface {

  /**
   * The entity-type's bundle entity-type id to be used across methods.
   *
   * @var string|null
   */
  protected $bundleEntityTypeId = '';

  /**
   * The currently-set entity-type id to be used across methods.
   *
   * @var string|null
   */
  protected $entityTypeId = '';

  /**
   * Sets the current entity type id.
   *
   * @param string $entity_type_id
   *   Current entity type id.
   */
  protected function setEntityTypeId($entity_type_id) {
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * Gets the current entity type id.
   *
   * @return string|null
   *   The current set entity type id.
   */
  protected function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * The entity definition of the current entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The current entity type definition.
   */
  protected function getEntityType() {
    if ($entity_type_id = $this->getEntityTypeId()) {
      return $this->entityTypeManager->getDefinition($entity_type_id);
    }
    return NULL;
  }

  /**
   * Get the current entity-types bundle entity-type id.
   *
   * @return null|string
   *   The current entity-types bundle entity-type id.
   */
  protected function getBundleEntityTypeId() {
    // If empty string (hasn't been loaded yet), return.
    if (is_string($this->bundleEntityTypeId) && empty($this->bundleEntityTypeId)) {
      $this->bundleEntityTypeId = $this->getEntityType()->getBundleEntityType();
    }
    return $this->bundleEntityTypeId;
  }

  /**
   * The entity definition of the current entity-type's bundle entity-type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The definition of the current entity-type's bundle entity-type.
   */
  protected function getBundleEntityType() {
    if ($bundle_entity_type_id = $this->getBundleEntityTypeId()) {
      return $this->entityTypeManager->getDefinition($bundle_entity_type_id);
    }
    return NULL;
  }

  /**
   * Get bundle route argument id.
   *
   * @return string
   *   The entity-type bundle ID.
   */
  protected function getBundleArgument() {
    return $this->getBundleEntityTypeId()?:$this->getEntityType()->getKey('bundle');
  }

  /**
   * Get bundles based on the current entity-type.
   */
  protected function getBundles() {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->getEntityTypeID());
    // Add descriptions from the bundle entities.
    if ($this->getBundleEntityTypeId()) {
      $bundles = $this->loadBundleDescriptions($bundles, $this->getBundleEntityType());
    }
    return $bundles;
  }

  /**
   * Filter out bundles the user doesn't have access to or aren't applicable.
   *
   * @param array $bundles
   *   The current entity bundles to filter.
   * @param array $build
   *   The render array of elements.
   */
  protected function filterBundles(array &$bundles, array &$build) {
    // Filter out bundles the user doesn't have access to or aren't applicable.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($this->entityTypeId);
    foreach ($bundles as $bundle_name => $bundle_info) {
      // Filter out non-accessible bundles.
      $access = $access_control_handler->createAccess($bundle_name, NULL, [], TRUE);
      if (!$access->isAllowed()) {
        unset($bundles[$bundle_name]);
      }

      $this->renderer->addCacheableDependency($build, $access);
    }
  }

  /**
   * Get the add-form route name for the add-page bundles.
   *
   * @return string
   *   The add-form route name for the add-page bundles.
   */
  protected function getAddFormRouteName() {
    return 'entity.' . $this->getEntityTypeId() . '.add_form';
  }

  /**
   * Get the add-form route parameters for the add-page bundle links.
   *
   * @param array|null $parameters
   *   The default route parameters to use.
   *
   * @return array
   *   The modified route parameters.
   */
  protected function getAddFormRouteParameters($parameters = NULL) {
    return (is_array($parameters)) ? $parameters : [];
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($entity_type_id) {
    // Initiate return array.
    $build = [
      '#theme' => 'entity_add_list',
      '#bundles' => [],
    ];

    // Set base entity information.
    $this->setEntityTypeId($entity_type_id);
    $entity_type = $this->getEntityType();
    $bundle_entity_type_id = $this->getBundleEntityTypeId();
    $form_route_name = $this->getAddFormRouteName();

    $bundle_argument = $this->getBundleArgument();

    // Get bundles to build list with.
    $bundles = $this->getBundles();
    // Filter out the bundles the user doesn't have access to.
    $this->filterBundles($bundles, $build);

    // Redirect early if only one bundle.
    if (count($bundles) == 1) {
      $bundle_names = array_keys($bundles);
      $bundle_name = reset($bundle_names);
      $form_route_parameters = $this->getAddFormRouteParameters([$bundle_argument => $bundle_name]);
      return $this->redirect($form_route_name, $form_route_parameters);
    }

    // Setup message if bundle-able entity.
    if ($bundle_entity_type_id) {
      $bundle_entity_type = $this->getBundleEntityType();
      $bundle_entity_type_label = $bundle_entity_type->getLowercaseLabel();
      $build['#cache']['tags'] = $bundle_entity_type->getListCacheTags();

      // Build the message shown when there are no bundles.
      $link_text = $this->t('Add a new @entity_type.', ['@entity_type' => $bundle_entity_type_label]);
      $link_route_name = 'entity.' . $bundle_entity_type->id() . '.add_form';
      $build['#add_bundle_message'] = $this->t('There is no @entity_type yet. @add_link', [
        '@entity_type' => $bundle_entity_type_label,
        '@add_link' => Link::createFromRoute($link_text, $link_route_name)->toString(),
      ]);
    }

    // Prepare the #bundles array for the template.
    foreach ($bundles as $bundle_name => $bundle_info) {
      $form_route_parameters = $this->getAddFormRouteParameters([$bundle_argument => $bundle_name]);
      $build['#bundles'][$bundle_name] = [
        'label' => $bundle_info['label'],
        'description' => isset($bundle_info['description']) ? $bundle_info['description'] : '',
        'add_link' => Link::createFromRoute($bundle_info['label'], $form_route_name, $form_route_parameters),
      ];
    }

    return $build;
  }

  /**
   * Provides a generic status title callback.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string|null
   *   The title for the entity status page, if an entity was found.
   */
  public function statusTitle(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($entity = $this->doGetEntity($route_match, $_entity)) {
      if ($status_key = $entity->getEntityType()->getKey('stauts')) {
        if ($entity->get($status_key)->get(0)->value == 0) {
          return $this->t('Activate %label', ['%label' => $entity->label()]);
        }
        return $this->t('Deactivate %label', ['%label' => $entity->label()]);
      }
      return $this->t('Modify %label status', ['%label' => $entity->label()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetEntity(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($_entity) {
      $entity = $_entity;
    }
    else {
      // Let's look up the deepest name of upcasted values in the route object.
      foreach (array_reverse($route_match->getParameters()->all(), TRUE) as $parameter) {
        if ($parameter instanceof EntityInterface) {
          $entity = $parameter;
          break;
        }
      }
    }
    if (isset($entity)) {
      return $this->entityRepository->getTranslationFromContext($entity);
    }
  }

}
