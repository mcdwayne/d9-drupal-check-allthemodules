<?php

/**
 * @file
 * Contains \Drupal\redhen_asset\Form\AssetDeleteForm.
 */

namespace Drupal\redhen_asset\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\redhen_asset\Entity\AssetType;

/**
 * Provides a form for deleting Asset entities.
 *
 * @ingroup redhen_asset
 */
class AssetDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @asset-type %name?', [
      '@asset-type' => AssetType::load($this->entity->bundle())->label(),
      '%name' => $this->entity->label()
    ]);
  }

}
