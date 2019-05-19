<?php

namespace Drupal\trance_example\Controller;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\trance\Controller\TranceAddController;

/**
 * Class TranceExampleAddController.
 *
 * @package Drupal\trance_example\Controller
 */
class TranceExampleAddController extends TranceAddController {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var EntityManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('trance_example'),
      $entity_manager->getStorage('trance_example_type')
    );
  }

  /**
   * Presents the creation form for trance_example entities of given bundle.
   *
   * @param EntityInterface $trance_example_type
   *   The custom bundle to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(EntityInterface $trance_example_type, Request $request) {
    return parent::addForm($trance_example_type, $request);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param EntityInterface $trance_example_type
   *   The custom bundle/type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(EntityInterface $trance_example_type) {
    return parent::getAddFormTitle($trance_example_type);
  }

}
