<?php

namespace Drupal\entity_resource_layer\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity IDs to full objects.
 *
 * In addition make sure that the entity is of the given bundle.
 *
 * @code
 * example.route:
 *   path: foo/{entity}
 *   options:
 *     parameters:
 *       example:
 *         type: entity_bundle:node:article
 * @endcode
 */
class EntityBundleConverter implements ParamConverterInterface {

  /**
   * Entity manager which performs the upcasting in the end.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    list(, $typeString, $bundleString) = explode(':', $definition['type']);
    $entityTypeId = $this->getEntityTypeFromDefaults($typeString, $name, $defaults);

    if ($storage = $this->entityTypeManager->getStorage($entityTypeId)) {
      if (!($entity = $storage->load($value))) {
        throw new NotFoundHttpException(sprintf('%s with id %s not found.', $this->entityTypeManager->getDefinition($entityTypeId)->getLabel(), $value));
      }

      $bundles = $this->getBundles($bundleString);

      // Makes sure that give entity is of requested bundle.
      if ($bundles && in_array($entity->bundle(), $bundles)) {
        throw new NotFoundHttpException(sprintf('%s not found with id %s', $entity->getEntityType()->getLabel(), $entity->id()));
      }

      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
        /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entityRepo */
        $entityRepo = \Drupal::service('entity.repository');
        $entity = $entityRepo->getTranslationFromContext($entity, NULL, ['operation' => 'entity_upcast']);
      }

      return $entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && strpos($definition['type'], 'entity_bundle:') === 0) {
      $entityTypeId = explode(':', $definition['type'])[1];

      if (strpos($definition['type'], '{') !== FALSE) {
        $entityTypeSlug = substr($entityTypeId, 1, -1);
        return $name != $entityTypeSlug && in_array($entityTypeSlug, $route->compile()->getVariables(), TRUE);
      }

      return $this->entityTypeManager->hasDefinition($entityTypeId);
    }

    return FALSE;
  }

  /**
   * Gets the allowed bundles from string.
   *
   * @param string $bundleString
   *   The bundle string.
   *
   * @return array|null
   *   The bundle array if any.
   */
  protected function getBundles($bundleString) {
    if ($bundleString) {
      return explode(',', $bundleString);
    }

    return NULL;
  }

  /**
   * Determines the entity type ID given a route definition and route defaults.
   *
   * @param string $typeString
   *   The entity type string.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string
   *   The entity type ID.
   *
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   *   Thrown when the dynamic entity type is not found in the route defaults.
   */
  protected function getEntityTypeFromDefaults($typeString, $name, array $defaults) {
    // If the entity type is dynamic, it will be pulled from the route defaults.
    if (strpos($typeString, '{') === 0) {
      $entityTypeSlug = substr($typeString, 1, -1);
      if (!isset($defaults[$entityTypeSlug])) {
        throw new ParamNotConvertedException(sprintf('The "%s" parameter was not converted because the "%s" parameter is missing', $name, $entity_type_slug));
      }
      $typeString = $defaults[$entityTypeSlug];
    }
    return $typeString;
  }

}
