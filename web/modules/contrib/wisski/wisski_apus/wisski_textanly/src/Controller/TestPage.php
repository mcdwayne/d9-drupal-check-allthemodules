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

}
