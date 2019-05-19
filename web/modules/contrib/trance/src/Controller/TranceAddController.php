<?php

namespace Drupal\trance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TranceAddController.
 *
 * @package Drupal\trance\Controller
 */
class TranceAddController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(EntityStorageInterface $storage, EntityStorageInterface $type_storage) {
    $this->storage = $storage;
    $this->typeStorage = $type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var EntityManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('trance'),
      $entity_manager->getStorage('trance_type')
    );
  }

  /**
   * Displays add links for available bundles/types for entity trance .
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the trance bundles/types that can be added
   *   or if there is only one type/bunlde defined for the site, the function
   *   returns the add page for that bundle/type.
   */
  public function add(Request $request) {
    $entity_type = $this->storage->getEntityType()->id();
    $bundle_entity_type = $this->storage->getEntityType()->getBundleEntityType();
    $types = $this->typeStorage->loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any %bundle types yet. @link to add a new type.', [
          '%bundle' => $entity_type,
          '@link' => Link::fromTextAndUrl($this->t('Go to the type creation page'), Url::fromRoute('entity.' . $bundle_entity_type . '.add_form'))->toString(),
        ]),
      ];
    }
    return ['#theme' => 'trance_content_add_list', '#content' => $types];
  }

  /**
   * Presents the creation form for trance entities of given bundle/type.
   *
   * @param EntityInterface $trance_type
   *   The custom bundle to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(EntityInterface $trance_type, Request $request) {
    $entity = $this->storage->create(['type' => $trance_type->id()]);
    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param EntityInterface $trance_type
   *   The custom bundle/type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(EntityInterface $trance_type) {
    return $this->t('Create @label of @type', [
      '@label' => $trance_type->label(),
      '@type' => $this->storage->getEntityType()->id(),
    ]);
  }

}
