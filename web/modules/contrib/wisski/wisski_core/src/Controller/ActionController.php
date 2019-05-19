<?php
/**
 * contains Drupal\wisski_core\Controller\ActionController
 */

namespace Drupal\wisski_core\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\wisski_salz\AdapterHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActionController extends ControllerBase {

  public function execute($action, $objects) {
    
    if (!is_object($action)) {
      $action = entity_load('action', $action);
    }
    if (empty($action)) {
      throw new \InvalidArgumentException("You must specify a valid action");
    }
    $entity_type = $action->getType();
    $entity_ids = explode(',', $objects);
    $entities = entity_load_multiple($entity_type, $entity_ids);
#rpm([$action,$entity_ids,count($entities)],'davor');    
    $action->execute($entities);
#rpm('danach');    

    return $this->redirect('<front>');

  }

  
  /**
   * Implements callback_batch_operation() - perform processing on each batch.
   *
   * Updates the titles of entities
   *
   * @param string $bundle_id
   *   ID of the bundle of which all entities are updated
   * @param mixed $context
   *   Batch context information.
   */
  public function processBatch($bundle_id, &$context) {
    $amount = 1000; // 1000 should be sufficient
    $query = \Drupal::entityQuery('wisski_individual');
    $query->condition('bundle', $bundle_id);
    $offset = isset($context['sandbox']['progress']) ? $context['sandbox']['progress'] : 0;
    $query->range($offset, $amount);
    $eids = $query->execute();
    if (empty($eids)) {
      $context['finished'] = 1;
      $context['results'] = [
        'total' => $offset,
      ];
    }
    else {
      foreach ($eids as $eid) {
        wisski_core_generate_title($eid, NULL, FALSE, $bundle_id);
      }
      $context['sandbox']['progress'] = $offset + count($eids);
      // we do not know the total number of individuals so we just
      // asymptotically grow towards 1...
      $context['finished'] = 1 - ($amount / (1 + $context['sandbox']['progress']));
    }
  }


  /**
   * Implements callback for batch finish.
   *
   * @param bool $success
   *    Indicates whether we hit a fatal PHP error.
   * @param array $results
   *    Contains batch results.
   * @param array $operations
   *    If $success is FALSE, contains the operations that remained unprocessed.
   *
   * @return RedirectResponse
   *    Where to redirect when batching ended.
   */
  public function finishBatch($success, $results, $operations) {
    if ($success) {
      drupal_set_message(t('Updated titles of @total entities', ['@total' => $results['total']]));
    }
    else {
      drupal_set_message(t('An error occurred while updating the titles.'), 'error');
    }
  }

}
