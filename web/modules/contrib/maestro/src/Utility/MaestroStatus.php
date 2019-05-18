<?php

namespace Drupal\maestro\Utility;

use Drupal\maestro\Engine\MaestroEngine;

class MaestroStatus {
  
  /**
   * Generates the staus bar showing process completion as status indicators
   *
   * @param int $processID
   * @param int $queueID
   * @param boolean $skipExecuteCheck
   */
  public static function getMaestroStatusBar($processID, $queueID, $skipExecuteCheck = FALSE) {
    $build = [];
    $status_bar = '';
    $canExecute = MaestroEngine::canUserExecuteTask($queueID, \Drupal::currentUser()->id());
    if($canExecute || $skipExecuteCheck) {
      $templateMachineName = MaestroEngine::getTemplateIdFromProcessId($processID);
      $template = MaestroEngine::getTemplate($templateMachineName);
      $var_workflow_stage_count = intval(MaestroEngine::getProcessVariable('workflow_timeline_stage_count', $processID));
    
      //Shall we show the status bar?
      if(isset($template->default_workflow_timeline_stage_count)
          && intval($template->default_workflow_timeline_stage_count) > 0
          && $var_workflow_stage_count > 0) {
            //fetch the currently set status number and message
            $current_status_number = intval(MaestroEngine::getProcessVariable('workflow_current_stage', $processID));
            $current_status_message = MaestroEngine::getProcessVariable('workflow_current_stage_message', $processID);
            $status_bar = '';
            $bar_render_array = [
              '#theme' => 'maestro_status_bar',
              '#stage_count' => $var_workflow_stage_count,
              '#stage_messages' => MaestroEngine::getAllStatusEntriesForProcess($processID),
              '#current_stage' => $current_status_number,
              '#current_stage_message' => $current_status_message,
            ];
            $status_bar = \Drupal::service('renderer')->renderPlain($bar_render_array);
            //anyone want to override it?
            \Drupal::moduleHandler()->invokeAll('maestro_task_console_status_bar_alter', array(&$status_bar, $processID));
          }
          $build['status_bar'] = array(
            '#children' => '<div class="maestro-timeline">' . $status_bar . '</div>',
          );
    }
    
    return $build;
  }
  
}