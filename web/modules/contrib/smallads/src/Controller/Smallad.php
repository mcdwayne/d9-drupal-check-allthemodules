<?php

namespace Drupal\smallads\Controller;

/**
 * Returns responses for Smallad routes.
 */
class Smallad {

  /**
   * {@inheritdoc}
   */
  public function canonicalLabel($smallad) {
    return t(
      '@type: %title',
        [
          '@type' => $smallad->type->entity->label(),
          '%title' => $smallad->label(),
        ]
    );
  }

}
