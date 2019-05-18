<?php

namespace Drupal\multiplechoice\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for autologout module routes.
 */
class MultiplechoiceAjax extends ControllerBase {

  /**
   * AJAX callback that saves the settings for a multiplechoice
   */
  public function saveSettings() {
    $values = $_GET;
    \Drupal::logger('dermpedia')->notice('save settings <pre>' . print_r($_GET,1));

//    $query = \Drupal::database()->select('multiplechoice_quiz_node_properties', 'qnr');
//    $query->condition('qnr.nid', $account->id());
//    $query->condition('qnr.nid', $entity->id());
//    return $query->countQuery()->execute()->fetchField();
    $query = \Drupal::database()->upsert('multiplechoice_quiz_node_properties');
    $query->fields([
      'pass_rate',
      'backwards_navigation',
      'quiz_open',
      'quiz_close',
      'takes',
      'nid',
      'vid',
      'aid',
      'summary_pass_format',
      'summary_default_format',
      'randomization',
      'keep_results',
      'repeat_until_correct',
      'feedback_time',
      'display_feedback',
      'show_attempt_stats',
      'time_limit',
      'quiz_always',
      'tid',
      'has_userpoints',
      'time_left',
      'max_score',
      'allow_skipping',
      'allow_resume',
      'allow_jumping',
      'show_passed',
      'mark_doubtful'

    ]);
    $query->values([
      $values['pass_rate'],
      $values['backwards_navigation'],
      strtotime($values['quiz_open']),
      strtotime($values['quiz_close']),
      $values['takes'],
      $values['nid'],
      $values['vid'],
      0,
      'full_html',
      'full_html',
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0,
      0
    ]);
    $query->key('nid');
    $query->execute();
    $response = new AjaxResponse();

    return $response;
  }


}
