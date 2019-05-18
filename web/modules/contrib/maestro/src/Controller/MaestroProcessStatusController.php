<?php
namespace Drupal\maestro\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\maestro\Utility\MaestroStatus;
use Drupal\Core\Url;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\views\Views;

class MaestroProcessStatusController extends ControllerBase {

  /**
   * Returns response for the process status queries.
   *
   * @param $processID
   *   The processID we wish to get details for
   *
   * @return AjaxResponse.
   */

  public function getDetails($processID) {
    
    $build = [];
    $taskDetails = '';
    //first, we determine if the template even wants process shown.
    $template = MaestroEngine::getTemplate(MaestroEngine::getTemplateIdFromProcessId($processID));
    if(isset($template->show_details) && $template->show_details) {
      $templateName = MaestroEngine::getTemplateIdFromProcessId($processID);
      $status_bar = MaestroStatus::getMaestroStatusBar($processID, 0, TRUE);  //skip the can execute check as this is not against a queue entry
      $build['status'] = [
        '#prefix' =>  '<div id="processid-' . $processID . '" class="maestro-block-process ' . $templateName . '">',
        '#suffix' => '</div>',
        '#markup' => $status_bar['status_bar']['#children'],
      ];
      
      //Lets see if there's any views attached that we should be showing.
      if(isset($template->views_attached)) {
        foreach($template->views_attached as $machine_name => $arr) {
          $view = Views::getView($machine_name);
          if($view) {
            $display = explode(';', $arr['view_display']);
            $display_to_use = isset($display[0]) ? $display[0] : 'default';
            $render_build = $view->buildRenderable($display_to_use, [$processID, 0], FALSE);
            if($render_build) {
              $thisViewOutput = \Drupal::service('renderer')->renderPlain($render_build);
              if($thisViewOutput) {
                $task_information_render_array = [
                  '#theme' => 'taskconsole_views',
                  '#task_information' => $thisViewOutput,
                  '#title' => $view->storage->label(),
                ];
                $taskDetails .= (\Drupal::service('renderer')->renderPlain($task_information_render_array));
              }
            }
          }
        }
      }
      //anyone want to override the task details display or add to it?
      \Drupal::moduleHandler()->invokeAll('maestro_process_status_alter', array(&$taskDetails, $processID, $template));
      
      $build['views_bar'] = array(
        '#children' => '<div class="maestro-process-details">' . $taskDetails . '</div>',
      );
      
      
    }
    
    //$build = MaestroStatus::getMaestroStatusBar($processID, 0, TRUE);  //skip the can execute check as this is not against a queue entry
    
    //we replace the down arrow with the toggle up arrow
    $replace['expand'] = array(
      '#attributes' => [
        'class' => array('maestro-timeline-status', 'maestro-status-toggle-up'),
        'title' => $this->t('Close Details'),
      ],
      '#type' => 'link',
      '#id' => 'maestro-id-ajax-' . $processID,
      '#url' => Url::fromRoute('maestro.process_details_ajax_close', ['processID' => $processID]),
      '#title' => $this->t('Close Details'),
      '#ajax' => array(
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ),
    );
    
    if(count($build) == 0) { //empty array
      $build['status'] = [
        '#plain_text' => $this->t('No details to show'),
      ];
    }
    
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#details_replace_column_' . $processID, $build)); //Row
    $response->addCommand(new HtmlCommand('.maestro-status-toggle-' . $processID . '', $replace['expand'])); //Wrapper attribute TD tag. Toggle up arrow.
    $response->addCommand(new CssCommand('#details_replace_row_' . $processID, ['display' => 'table-row']));
    return $response;
    
  }
  
  public function closeDetails($processID) {
    $build = [];
    //we replace the up arrow with the down arrow.
    $build['expand'] = array(
      '#attributes' => [
        'class' => array('maestro-timeline-status', 'maestro-status-toggle'),
        'title' => $this->t('Open Details'),
      ],
      '#type' => 'link',
      '#id' => 'maestro-id-ajax-' . $processID,
      '#url' => Url::fromRoute('maestro.process_details_ajax_open', ['processID' => $processID]),
      '#title' => $this->t('Open Details'),
      '#ajax' => array(
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ),
    
    );
    
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#maestro-ajax-' . $processID, ''));
    $response->addCommand(new HtmlCommand('.maestro-status-toggle-' . $processID , $build['expand']));
    $response->addCommand(new CssCommand('#details_replace_row_' . $processID, ['display' => 'none']));
    
    return $response;
  }
  
  
}