<?php

namespace Drupal\fillpdf;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines a custom storage handler for FillPdfFormFields.
 *
 * The default storage is overridden to avoid having to delete FillPdfFormField
 * entities separately from their parent FillPdfForms.
 */
class FillPdfFormFieldStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    // @todo: entity_type.manager replaced the entity.manager in Drupal 8.7.
    // Remove after Drupal 8.6 is no longer supported.
    $manager = isset($this->entityTypeManager) ? $this->entityTypeManager : $this->entityManager;
    // Announce having data only if there are orphan FillPdfFormFields after
    // all FillPdfForms are deleted.
    return $manager->getStorage('fillpdf_form')->hasData() ? FALSE : parent::hasData();
  }

}
