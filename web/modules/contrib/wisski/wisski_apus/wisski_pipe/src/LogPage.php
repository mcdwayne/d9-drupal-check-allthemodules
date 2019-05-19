<?php
/**
 * @file
 * Contains \Drupal\wisski_textanly\Controller\TestPage.
 */

namespace Drupal\wisski_textanly\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\wisski_pipe\StackingLogger;

class TestPage extends ControllerBase {

  
  public function testPage() {
  
    $service = \Drupal::service('wisski_pipe.pipe');
    $pipes = $service->loadMultiple();
    $options = [];
    foreach ($pipes as $pipe) {
      $options[$pipe->id()] = $pipe->label();
    }

    $form['text'] = array(
      '#type' => 'textarea',
      '#title' => t('Text'),
      '#attributes' => array('id' => 'analyse_text'),
    );
    $form['pipe'] = array(
      '#type' => 'select',
      '#title' => t('Pipe'),
      '#options' => $options,
      '#attributes' => array('id' => 'analyse_pipe'),
    );
    $form['analyse'] = array(
      '#markup' => '<p><a id="analyse_do" href="#">Analyse</a></p>',
    );
    $form['result'] = array(
      '#type' => 'fieldset',
      '#title' => t('Result'),
      // Drupal 8 way to add css and js files, see .libraries.yml file
      '#attached' => array(
        'library' => array('wisski_textanly/test_page')
      ),
    );
    $form['result']['value'] = array(
      '#prefix' => '<div><pre id="analyse_result" class="json_dump"></pre></div>',
      '#value' => '',
    );
    $form['logs'] = array(
      '#type' => 'fieldset',
      '#title' => t('Logs'),
    );
    $form['logs']['value'] = array(
      '#prefix' => '<div><pre id="analyse_log" class="json_dump"></pre></div>',
      '#value' => '',
    );

    return $form;
  }


  /** Web service callback: Analyse a text and return a json object
  * containing proposed annotations.
  *
  * Supports parameters in $q. All are optional but 'text'.
  *
  * @author Martin Scholz
  *
  */
  public function ajax_analyse() {
    
    // we use the textedit module to read the post parameters
    $q = (object) array(
      'ticket' => NULL,
      'text' => NULL,
      'annos' => array(), 
      'pipe' => 'plain_text_default', 
      'annos_only' => TRUE, 
      'log' => TRUE
    );
    
    \Drupal::service('wisski_apus.util')->parseHttpParams($q, "text_struct");
    
    if ($q->text === NULL) {
      // found no text parameter
      throw new RuntimeException("no text to analyse");
    }
    
    $text_struct = array(
      'text' => $q->text,
      'annos' => $q->annos,
    );
    $ticket = empty($q->ticket) ? wisski_get_uuid(4) : $q->ticket;
    $logger = new StackingLogger();
    $text_struct = \Drupal::service('wisski_pipe.pipe')->run($q->pipe, $text_struct, $ticket, $logger);
    
    // we store the log messages for one day
    // should be enough for a testing page
    $log_stack = $logger->getStack();
    \Drupal::services('cache.default')->set("wisski_textanly_log_$ticket", $log_stack, time() + 86400);

    // Print result
    $json = array('ticket' => $ticket);
    if ($q->annos_only) $json["annos"] = $text_struct['annos'];
    else $json["text_struct"] = $text_struct;
    
    // the symfony resonse object set the right header
    return new JsonResponse($json);

  }


  /** Web service callback: Return the logs of an analysis as a json object
  *
  * Supported parameters in $q are 'ticket' (mandatory) and 'logs' (optional).
  * The latter is an array of log levels to return. By default only returns info level.
  *
  * @author Martin Scholz
  *
  */
  public function wisski_textanly_ajax_log() {

    drupal_set_header('Content-Type: application/json; charset=utf-8');
    
    // we use the textedit module to read the post parameters
    $q = (object) array('ticket' => NULL, 'levels' => array('info'));
    wisski_parse_http_params($q, "log");
    
    $ticket = $q->ticket;
    if ($ticket === NULL) {
      // found no text parameter
      drupal_set_header('HTTP/1.1 400 Bad Request');
      print json_encode(array("error" => "no ticket specified"));
      return;    
    }

    $json = array('ticket' => $ticket);
    if (!empty($q->levels) && is_array($q->levels)) {
      $cache = \Drupal::service('cache.default')->get("wisski_textanly_log_$ticket");
      if (isset($cache->data)) {
        foreach ($cache->data as $log) 
          if (in_array($log[1], $q->levels)) $json['logs'][] = $log;
      }
    }

    // the symfony resonse object set the right header
    return new JsonResponse($json);
    
  }




  /** Web service callback: Return the logs of an analysis as a json object
  *
  * Supported parameters in $q are 'ticket' (mandatory) and 'logs' (optional).
  * The latter is an array of log levels to return. By default only returns info level.
  *
  * @author Martin Scholz
  *
  */
  public function wisski_textanly_html_log($ticket) {

    if ($ticket === NULL) {
      // found no text parameter
      throw new RuntimeException("no ticket specified");
    }
    
    $page = array();
    $page['#attached']['library'][] = 'wisski_textanly/log_page';
    $page['#attached']['drupalSettings']['WissKI']['textanly']['log_page']['ticket'] = $ticket;
    $page['#markup'] = '<pre id="analyse_log" class="json_dump"></pre>';
    
    return $page;

  }

}
