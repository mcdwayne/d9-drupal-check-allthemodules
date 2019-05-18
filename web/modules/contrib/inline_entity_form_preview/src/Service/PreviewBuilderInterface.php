<?php
/**
 * @file:
 *   Contains Drupal\inline_entity_form_preview\Service\PreviewBuilderInterface.
 */

namespace Drupal\inline_entity_form_preview\Service;

use Drupal\Core\Entity\EntityInterface;

interface PreviewBuilderInterface {

  /**
   * Builds the render array for the provided entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return array
   *   A render array for the entity.
   *
   * @throws \InvalidArgumentException
   *   Can be thrown when the set of parameters is inconsistent, like when
   *   trying to view a Comment and passing a Node which is not the one the
   *   comment belongs to, or not passing one, and having the comment node not
   *   be available for loading.
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL);
}
