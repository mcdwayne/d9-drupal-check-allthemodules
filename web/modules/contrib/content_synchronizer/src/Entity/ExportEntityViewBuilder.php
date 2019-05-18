<?php

namespace Drupal\content_synchronizer\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Form\FormState;

/**
 * View for Export entity.
 */
class ExportEntityViewBuilder extends EntityViewBuilder {

  /**
   * Render the view of the entity.
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {

    $formState = new FormState();
    $formState->addBuildInfo('export', $entity);

    return \Drupal::formBuilder()->buildForm('Drupal\content_synchronizer\Form\LaunchExportForm', $formState);
  }

}
