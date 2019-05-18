<?php

namespace Drupal\micon;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base class for entity view builders.
 *
 * @ingroup entity_api
 */
class MiconViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // Use micon_package as the #theme for clarity.
    $build['#theme'] .= '_package';
    $build['#attached']['library'][] = 'micon/micon.' . $entity->id();
    return $build;
  }

}
