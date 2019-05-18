<?php
/**
 * @file
 * Contains Drupal\maestro\Controller\MaestroOrchestrator.
 */

namespace Drupal\maestro\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\maestro\Engine\MaestroEngine;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class MaestroOrchestrator extends ControllerBase {
  
  /**
   * Orchestrator method
   * This method is called by the menu router for /orchestrator
   * This runs the Maestro Engine.
   */
  public function orchestrate($token = '', $skip_response = FALSE) {
    if($token == '') {
      //bad!  must have a value
      return new Response('Missing Orchestrator Token', 500);
    }
    
    $config = $this->config('maestro.settings');
    if($config->get('maestro_orchestrator_token') != $token) {
      return new Response('Wrong Orchestrator Token', 500);
    }
    
    $engine = new MaestroEngine();
    
    $lock = \Drupal::lock();
    if ($lock->acquire('maestro_orchestrator')) {
      //TODO: Handle exceptions being thrown.
      //leaving it like this will simply stall the orchestrator execution for the time being
      //How to gracefully continue?  One process failing will stall the entire engine.
      $engine->cleanQueue();
      $lock->release('maestro_orchestrator');
    }
    
    if($engine->getDebug()) {
      return array ('#markup' => 'debug orchestrator done');
    }
    else {
      //See CronController::run as we do the same thing to return a 204 with no content to satisfy the return response
      if(!$skip_response) return new Response('', 204);
    }
  }
  
  public function startProcess($templateMachineName = '', $redirect = 'taskconsole') {
    $template = MaestroEngine::getTemplate($templateMachineName);
    if($template) {
      $engine = new MaestroEngine();
      $pid = $engine->newProcess($templateMachineName);
      if($pid) {
        drupal_set_message(t('Process Started'));
        $config = $this->config('maestro.settings');
        $this->orchestrate($config->get('maestro_orchestrator_token'), TRUE);  //run the orchestrator for us once on process kickoff
      }
      else {
        drupal_set_message(t('Error!  Process unable to start!'), 'error');
      }
    }
    else {
      drupal_set_message(t('Error!  No template by that name exits!'), 'error');
    }
    
    if($redirect == 'taskconsole') {
      return new RedirectResponse(\Drupal\Core\Url::fromRoute('maestro_taskconsole.taskconsole')->toString());
    }
    elseif($redirect == 'templates') {
      return new RedirectResponse(\Drupal\Core\Url::fromRoute('entity.maestro_template.list')->toString());
    }
    else {
      return new RedirectResponse(\Drupal\Core\Url::fromUserInput('/' . $redirect)->toString());
    }
    
    
    
  }
  
}