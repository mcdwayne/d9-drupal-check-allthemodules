<?php

namespace Drupal\virtual_entities\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class VirtualEntityAddController.
 *
 * @package Drupal\virtual_entities\Controller
 */
class VirtualEntityAddController extends ControllerBase {

  /**
   * VirtualEntityAddController constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Entity storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $type_storage
   *   Entity type storage.
   */
  public function __construct(EntityStorageInterface $storage, EntityStorageInterface $type_storage) {
    $this->storage = $storage;
    $this->typeStorage = $type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $entity_type_manager->getStorage('virtual_entity'),
      $entity_type_manager->getStorage('virtual_entity_type')
    );
  }

  /**
   * Displays add links for available bundles/types for entity virtual_entity .
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the virtual_entity bundles/types that can
   *   be added or if there is only one type/bunlde defined for the site, the
   *   function returns the add page for that bundle/type.
   */
  public function add(Request $request) {
    $types = $this->typeStorage->loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);

      return $this->addForm($type, $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any %bundle types yet. @link to add a new type.', [
          '%bundle' => 'Virtual entity',
          '@link' => $this->l($this->t('Go to the type creation page'), Url::fromRoute('entity.virtual_entity_type.add_form')),
        ]),
      ];
    }

    return [
      '#theme' => 'virtual_entity_content_add_list',
      '#content' => $types,
    ];
  }

  /**
   * Presents the creation form for virtual_entity entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $virtual_entity_type
   *   The custom bundle to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(EntityInterface $virtual_entity_type, Request $request) {
    $entity = $this->storage->create([
      'type' => $virtual_entity_type->id(),
    ]);

    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\Core\Entity\EntityInterface $virtual_entity_type
   *   The custom bundle/type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(EntityInterface $virtual_entity_type) {
    return $this->t('Create of bundle @label',
      ['@label' => $virtual_entity_type->label()]
    );
  }

}
