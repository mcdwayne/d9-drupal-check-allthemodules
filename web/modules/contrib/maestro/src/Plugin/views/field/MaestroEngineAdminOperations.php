<?php 

/**
 * @file
 * Definition of Drupal\maestro\Plugin\views\field\MaestroEngineActiveHandler
 */
 
namespace Drupal\maestro\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Field handler to create Administrative Operations for a queue entry in views
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("maestro_admin_operations")
 */
class MaestroEngineAdminOperations extends FieldPluginBase {
 
  /**
   * @{inheritdoc}
   */
  public function query() {
    // no Query to be done.
  }
 
  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
   
 
    return $options;
  }
 
  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    
 
    parent::buildOptionsForm($form, $form_state);
  }
 
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $item = $values->_entity;
    $rows = array();
    $links = array();

    /*
     * Tracing mechanism needs the process ID
     */
    $processID = 0;
    if ($item->getEntityTypeId() == 'maestro_production_assignments') {
      $queueRecord = MaestroEngine::getQueueEntryById($item->queue_id->getString());
      $processID = $queueRecord->process_id->getString();
    }
    elseif ($item->getEntityTypeId() == 'maestro_queue') {
      $processID = $item->process_id->getString();
    }
    elseif ($item->getEntityTypeId() == 'maestro_process') {
      $processID = $item->process_id->getString();
    }
    

    $links['trace'] = array(
      'title' => t('Trace'),
      'url' => Url::fromRoute('maestro.trace', array('processID' => $processID)),
    );

    /*
     * Reassignment mechanism: only works for queue and production assignment types
     */
    $assignees = [];
    if ($item->getEntityTypeId() == 'maestro_production_assignments') {
      $assignees = MaestroEngine::getAssignedNamesOfQueueItem($item->queue_id->getString(), TRUE);
    }
    elseif ($item->getEntityTypeId() == 'maestro_queue') {
      $assignees = MaestroEngine::getAssignedNamesOfQueueItem($item->id->getString(), TRUE);
    }

    /*
     * The assignees holds a keyed array telling us who is assigned and how.
     * We use this information to determine what to pass to the handlers for the operations
     * First, the reassign. for each of the assignees, provide a link to reassign
     */
    foreach ($assignees as $name => $assignment) {
      $links[$name] = array(
        'title' => t('Reassign') . ' ' . $name,
        'url' => Url::fromRoute('maestro.reassign_task', array('assignmentID' => $assignment['id'])),
      );
    }

    $rows[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );


    return $rows;

  }

}