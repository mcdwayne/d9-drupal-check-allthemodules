<?php

namespace Drupal\entity_legal_state;

use Drupal\entity_legal\EntityLegalDocumentInterface;

/**
 * EntityLegalState service interface.
 */
interface EntityLegalStateInterface {

  /**
   * Get the published version from state.
   *
   * @param \Drupal\entity_legal\EntityLegalDocumentInterface $legal_document
   *   The legal document config entity.
   *
   * @return string
   *   The ID of the published EntityLegalDocumentVersion entity.
   */
  public function getStateVersion(EntityLegalDocumentInterface $legal_document);

  /**
   * Update the published version in state.
   *
   * @param \Drupal\entity_legal\EntityLegalDocumentInterface $legal_document
   *   The legal document config entity.
   * @param string $version_value
   *   The EntityLegalDocumentVersion ID to save.
   */
  public function updateStateVersion(EntityLegalDocumentInterface $legal_document, $version_value);

  /**
   * Delete the published version from state.
   *
   * @param \Drupal\entity_legal\EntityLegalDocumentInterface $legal_document
   *   The legal document config entity.
   */
  public function deleteStateVersion(EntityLegalDocumentInterface $legal_document);

  /**
   * Update the state value if the entity contains a changed published_version.
   *
   * @param \Drupal\entity_legal\EntityLegalDocumentInterface $legal_document
   *   The legal document config entity.
   */
  public function updatePublishedVersion(EntityLegalDocumentInterface $legal_document);

}
