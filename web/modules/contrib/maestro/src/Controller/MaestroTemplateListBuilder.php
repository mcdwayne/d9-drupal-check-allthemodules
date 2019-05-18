<?php
/**
 * @file
 * Contains Drupal\maestro\Controller\MaestroTemplateListController.
 */

namespace Drupal\maestro\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core;
use Drupal\Core\Url;

/**
 * Provides a listing of Maestro Template entities.
 *
 * @package Drupal\maestro\Controller
 *
 * @ingroup maestro
 */
class MaestroTemplateListBuilder extends ConfigEntityListBuilder  {

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $header['label'] = $this->t('Template');
    $header['machine_name'] = $this->t('Machine Name');
    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $valid = FALSE;
    $validity_message = '<span class="maestro-template-validation-failed">' . $this->t(' (*Needs Validation)') . '</span>';
    if(isset($entity->validated) && $entity->validated == TRUE) {
      $validity_message = '';
    }
    $row['label'] = array('data' => array('#markup' => $this->getLabel($entity) . $validity_message));
    $row['machine_name'] = $entity->id();
    $row = $row + parent::buildRow($entity);
    return $row;
  }

  /**
   * Adds some descriptive text to our entity list.
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>This is the full listing of Maestro Templates in your system.</p>"),
      '#attached' => array(
          'library' => 'maestro/maestro-engine-css',
      ),
    );
    $build[] = parent::render();

    return $build;
  }


  /**
   * {@inheritdoc}
   *
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $user = \Drupal::currentUser();

    /*
     * Check and see if the maestro task console is enabled
     */
    if (\Drupal::moduleHandler()->moduleExists('maestro_template_builder')) {
      $operations['tasks'] = array(
        'title' => t('Task Editor'),
        'url' => Url::fromUserInput('/template-builder/' . $entity->id),
        'weight' => 1
      );
    }
    
    if($user->hasPermission('start template ' . $entity->id)) {
      $operations['start_process'] = array(
        'title' => t('Start Process'),
        'url' => Url::fromRoute('maestro.start_process', ['templateMachineName' => $entity->id]),
        'weight' => 10
      );
    }
    
    /*
     * Check to see if the current user has permission to start this process
     */
    if (\Drupal::currentUser()->hasPermission('administer maestro templates')) {
      $operations['edit']['title'] = t('Edit Template');
      $operations['edit']['weight'] = 5;
      $operations['edit']['url'] = Url::fromRoute('entity.maestro_template.edit_form', ['maestro_template' => $entity->id]);

      // Weight sorting seemingly wasn't happening.  Just making sure I can sort by weight for our purposes
      uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    }
    else {
      // Make sure the edit is unset for those without this permission
      unset($operations['edit']);
    }
    
    return $operations;
  }


}
