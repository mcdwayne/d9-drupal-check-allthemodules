<?php

namespace Drupal\batch_jobs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\batch_jobs\Job;

/**
 * Batch jobs form.
 */
class BatchJobsTasks extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_jobs_tasks_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bid = NULL) {
    $user = \Drupal::currentUser();
    $token_generator = \Drupal::csrfToken();
    $form = [];
    $job = new Job($bid);
    if (!$job->access()) {
      return $form;
    }

    $header = [
      ['data' => t('Task ID'), 'field' => 'tid'],
      ['data' => t('Title'), 'field' => 'title'],
      ['data' => t('Started'), 'field' => 'start'],
      ['data' => t('Completed'), 'field' => 'end'],
      ['data' => t('Status'), 'field' => 'status'],
      t('Message'),
      t('Action'),
    ];

    $form['tasks'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['batch-tasks']],
      '#header' => $header,
      '#empty' => t('There are no tasks.'),
    ];

    $number = 25;
    $page = \Drupal::request()->query->get('page');
    $offset = (is_null($page)) ? 0 : $page * $number;

    $columns = ['tid', 'title', 'start', 'end', 'status', 'message'];
    $sql = \Drupal::database()->select('batch_task', 'task');
    $sorted = $sql->extend('Drupal\Core\Database\Query\TableSortExtender');
    $sorted->orderByHeader($header);
    $tasks = $sorted->condition('task.bid', $job->bid)
      ->fields('task', $columns)
      ->range($offset, $offset + $number)
      ->execute();
    foreach ($tasks as $task) {
      $token = $token_generator->get($task->tid);
      $form['tasks'][$task->tid]['id'] = [
        '#markup' => $task->tid,
      ];
      $form['tasks'][$task->tid]['title'] = [
        '#plain_text' => $task->title,
      ];
      $date_formatter = \Drupal::service('date.formatter');
      $start = ($task->start == 0) ? '' : $date_formatter->format($task->start);
      $form['tasks'][$task->tid]['start'] = [
        '#markup' => $start,
      ];
      $end = ($task->end == 0) ? '' : $date_formatter->format($task->end);
      $form['tasks'][$task->tid]['end'] = [
        '#markup' => $end,
      ];
      if ($task->status) {
        $status = '<div class="successful">' . t('Successful') . '</div>';
      }
      else {
        $status = ($task->end == 0) ? '' : '<div class="error">' . t('Error') .
          '</div>';
      }
      $form['tasks'][$task->tid]['status'] = [
        '#markup' => $status,
      ];
      $form['tasks'][$task->tid]['message'] = [
        '#markup' => print_r(unserialize($task->message), TRUE),
      ];
      if ($task->status) {
        $form['tasks'][$task->tid]['action'] = [
          '#markup' => '',
        ];
      }
      else {
        $form['tasks'][$task->tid]['action'] = [
          '#prefix' => '<div class="align-center">',
          '#type' => 'button',
          '#value' => t('Run'),
          '#postfix' => '</div>',
          '#attributes' => ['token' => [$token]],
        ];
      }
    }

    $count = \Drupal::database()->select('batch_task', 'task')
      ->condition('task.bid', $job->bid)
      ->countQuery()
      ->execute()
      ->fetchField();
    $page = pager_default_initialize($count, $number);
    $pager = [
      '#type' => 'pager',
    ];
    $form['pager'] = [
      '#markup' => \Drupal::service('renderer')->render($pager),
    ];

    $form['#attached']['library'][] = 'batch_jobs/batch_jobs';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
