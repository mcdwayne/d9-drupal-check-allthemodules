<?php

namespace Drupal\maestro\Plugin\RulesAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\maestro\Engine\MaestroEngine;
/**
 * Provides a 'Delete entity' action.
 *
 * @RulesAction(
 *   id = "maestro_rules_spawn_workflow",
 *   label = @Translation("Spawn Maestro Workflow"),
 *   category = @Translation("Maestro"),
 *   context = {
 *     "template" = @ContextDefinition("string",
 *       label = @Translation("Maestro Template Machine Name"),
 *       description = @Translation("Specifies the Maestro Template's machine name you wish to spawn. You can find the machine name on the template listing. This is just a string value."),
 *       default_value = NULL,
 *       required = true
 *     ),
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity which should be attached to the workflow. Please use the data selector and choose the entity.  If this is a node, choose the 'node' data selector.")
 *     ),
 *   }
 * )
 */
class MaestroRulesActionSpawnWorkflow extends RulesActionBase {

  /**
   * Spawns a workflow.
   *
   * @param string $template 
   *    The workflow template machine name you wish to spawn.
   * @param  \Drupal\Core\Entity\EntityInterface $entity
   *    The entity that is being saved.
   */
  protected function doExecute($template = NULL, EntityInterface $entity) {
    if($template !== NULL) {
      $engine = new MaestroEngine();
      $process_id = $engine->newProcess($template);
      if($process_id) {
        $entity_id = current($entity->nid->getValue())['value'];
        $entity_bundle = current($entity->type->getValue())['target_id'];
        MaestroEngine::createEntityIdentifier($process_id, 'node', $entity_bundle, 'rules_added_entity', $entity_id);
      }
      else {
        //error condition.  The process was unable to be kicked off.
        drupal_set_message(t('Unable to begin workflow.  Please check with administrator for more information.'));
      }
      
    }
  }

}