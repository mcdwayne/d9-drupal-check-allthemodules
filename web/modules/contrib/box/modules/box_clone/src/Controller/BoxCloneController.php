<?php

namespace Drupal\box_clone\Controller;

use Drupal\box_clone\Entity\BoxCloneEntityFormBuilder;
use Drupal\box\Entity\Box;
use Drupal\box\Controller\BoxController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for box clone routes.
 */
class BoxCloneController extends BoxController {

  /**
   * The entity form builder.
   *
   * @var \Drupal\box_clone\Entity\BoxCloneEntityFormBuilder
   */
  protected $bcEntityFormBuilder;

  /**
   * Constructs a BoxController object.
   *
   * @param \Drupal\box_clone\Entity\BoxCloneEntityFormBuilder $entity_form_builder
   *   The entity form builder service.
   */
  public function __construct(BoxCloneEntityFormBuilder $entity_form_builder) {
    $this->bcEntityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('box_clone.entity.form_builder')
    );
  }

  /**
   * Retrieves the entity form builder.
   *
   * @return \Drupal\box_clone\Entity\BoxCloneEntityFormBuilder
   *   The entity form builder.
   */
  protected function entityFormBuilder() {
    return $this->bcEntityFormBuilder;
  }

  /**
   * Provides the box submission form.
   *
   * @param \Drupal\box\Entity\Box $box
   *   The box entity to clone.
   *
   * @return array
   *   A box submission form.
   */
  public function cloneBox(Box $box) {
    if (!empty($box)) {
      $form = $this->entityFormBuilder()->getForm($box, 'box_clone');
      return $form;
    }
    else {
      throw new NotFoundHttpException();
    }

  }

  /**
   * The _title_callback for the box.add route.
   *
   * @param \Drupal\box\Entity\Box $box
   *   The box entity.
   *
   * @return string
   *   The page title.
   */
  public function clonePageTitle(Box $box) {
    return box_clone_get_default_title($box);
  }

}
