<?php

namespace Drupal\fillpdf;

/**
 * Load entities from a FillPDF $context.
 *
 * Provides a common interface for loading and serialization of the $context
 * array returned by FillPdfLinkManipulator::parseRequest().
 *
 * @package Drupal\fillpdf\FillPdfContextManagerInterface
 */
interface FillPdfContextManagerInterface {

  /**
   * Loads the entities specified in $context['entity_ids'].
   *
   * @param array $context
   *   The FillPDF request context as returned by
   *   FillPdfLinkManipulatorInterface::parseLink().
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects indexed by their IDs. Returns an empty array
   *   if no matching entities are found.
   *
   * @see \Drupal\fillpdf\FillPdfLinkManipulatorInterface::parseLink()
   */
  public function loadEntities(array $context);

}
