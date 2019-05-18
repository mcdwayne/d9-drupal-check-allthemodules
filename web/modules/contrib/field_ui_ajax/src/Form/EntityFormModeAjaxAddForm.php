<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\EntityFormModeAjaxAddForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 */
class EntityFormModeAjaxAddForm extends EntityDisplayModeAjaxAddForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $definition = $this->entityManager->getDefinition($this->targetEntityTypeId);
    if (!$definition->get('field_ui_base_route') || !$definition->hasFormClasses()) {
      throw new NotFoundHttpException();
    }

    $this->entity->setTargetType($this->targetEntityTypeId);
  }

}
