<?php

namespace Drupal\bibcite_entity\Controller;

use Drupal\bibcite_entity\Entity\ReferenceTypeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Returns responses for Bibcite routes.
 */
class BibciteEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Provides the reference submission form.
   *
   * @param ReferenceTypeInterface $bibcite_reference_type
   *   The reference type entity for the reference.
   *
   * @return array
   *   A reference submission form.
   */
  public function add(ReferenceTypeInterface $bibcite_reference_type) {
    $entity = $this->entityTypeManager()->getStorage('bibcite_reference')->create([
      'type' => $bibcite_reference_type->id(),
    ]);

    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function entityFormBuilder() {
    if (!$this->entityFormBuilder) {
      /* We use our form builder for saving entity in cache
       * on first form build. This is temporary patch for
       * entity form cache problem.
       *
       * @see https://www.drupal.org/project/drupal/issues/2824293
       *   Core issue.
       *
       * @see https://www.drupal.org/project/bibcite/issues/2930990
       *   Issue for this patch.
       *
       * @todo Remove after core fix.
       */
      $this->entityFormBuilder = $this->container()->get('reference_entity.form_builder');
    }
    return $this->entityFormBuilder;
  }

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it. Instead,
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
   * for injecting services.
   *
   * @see ControllerBase
   *   Copy of method from original parent class.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

}
