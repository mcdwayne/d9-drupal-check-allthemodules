<?php

namespace Drupal\drd\Entity\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for DRD Entities.
 *
 * @ingroup drd
 */
abstract class Base extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\drd\Entity\BaseInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('actions')) {
        $build[$id]['actions'] = \Drupal::formBuilder()->getForm('Drupal\drd\Form\EntityActions');
      }
    }
  }

}
