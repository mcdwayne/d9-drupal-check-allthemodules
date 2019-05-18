<?php

/**
 * @file
 * Contains \Drupal\entity_base\Controller\EntityBaseGenericController.
 */

namespace Drupal\entity_base\Controller;

use Drupal\entity_base\Entity\EntityBaseGenericInterface;
use Drupal\entity_base\Entity\EntityBaseTypeInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Controller routines for entity routes.
 */
class EntityBaseGenericController extends EntityBaseSimpleController {

  protected $entityTypeId;
  protected $entityTypeBundleId;

  /**
   * Displays add entity links for available entity types.
   *
   * Redirects to specific add form if only one entity type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the entity types that can be added; however,
   *   if there is only one entity type defined, the function
   *   will return a RedirectResponse to the entity add page for that one entity
   *   type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'entity_base_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition($this->entityTypeBundleId)->getListCacheTags(),
      ],
    ];

    $content = array();

    // Only use entity types the user has access to.
    foreach ($this->entityTypeManager()->getStorage($this->entityTypeBundleId)->loadMultiple() as $type) {
      $access = $this->entityTypeManager()->getAccessControlHandler($this->entityTypeId)->createAccess($type->id(), NULL, ['entity_type_id' => $this->entityTypeId], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the entity/add listing if only one entity type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('entity.' . $this->entityTypeId . '.add_form', array($this->entityTypeBundleId => $type->id()));
    }

    $build['#content'] = $content;
    $build['#entity_type_id'] = $this->entityTypeBundleId;

    return $build;
  }

  /**
   * The _title_callback for the "Add Page" route.
   *
   * @param \Drupal\entity_base\Entity\EntityBaseTypeInterface $entity_type
   *   The current entity type.
   *
   * @return string
   *   The page title.
   *
   */
  public function addPageTitle(EntityBaseTypeInterface $entity_type) {
    return $this->t('Create @name', array('@name' => $entity_type->label()));
  }

  /**
   * Provides the entity submission form.
   *
   * @param \Drupal\entity_base\Entity\EntityBaseTypeInterface $entity_type
   *   The entity type for the entity.
   *
   * @return array
   */
  public function addEntity(EntityBaseTypeInterface $entity_type) {
    $entity_type_definition = $entity_type->getEntityType();
    $entity = $this->entityTypeManager()->getStorage($entity_type_definition->get('bundle_of'))->create([
      'type' => $entity_type->id(),
    ]);

    return $this->entityFormBuilder()->getForm($entity);
  }

}
