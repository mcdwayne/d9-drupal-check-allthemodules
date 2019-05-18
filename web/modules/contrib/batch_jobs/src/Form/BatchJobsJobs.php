<?php

namespace Drupal\batch_jobs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\batch_jobs\Job;

/**
 * Batch jobs form.
 */
class BatchJobsJobs extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_jobs_jobs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $header = [
      ['data' => t('Title'), 'field' => 'title'],
      ['data' => t('User'), 'field' => 'uid'],
      t('Total'),
      t('Started'),
      t('Completed'),
      t('Errors'),
      t('Status'),
      t('Action'),
    ];
    $sql = \Drupal::database()->select('batch_jobs', 'jobs')
      ->fields('jobs', ['bid', 'title', 'uid'])
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);
    if ($user->id() == 1) {
      $jobs = $sql->execute();
    }
    else {
      $jobs = $sql->condition('jobs.uid', [0, $user->id()], 'IN')
        ->execute();
    }

    $form['jobs'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['batch-jobs']],
      '#header' => $header,
      '#empty' => t('There are no jobs yet.'),
    ];

    foreach ($jobs as $batch_job) {
      $job = new Job($batch_job->bid);
      $token = $job->getToken($job->bid);
      $form['jobs'][$job->bid]['title'] = [
        '#markup' => $this->link($job->title, '/batch-jobs/' . $job->bid),
      ];
      $form['jobs'][$job->bid]['user'] = [
        '#markup' => $job->getUser(),
      ];
      $total = $job->total();
      $form['jobs'][$job->bid]['total'] = [
        '#prefix' => '<div class="align-right">',
        '#markup' => $total,
        '#postfix' => '</div>',
      ];
      $form['jobs'][$job->bid]['started'] = [
        '#prefix' => '<div class="align-right">',
        '#markup' => $job->started(),
        '#postfix' => '</div>',
      ];
      $completed = $job->completed();
      $form['jobs'][$job->bid]['completed'] = [
        '#prefix' => '<div class="align-right">',
        '#markup' => $completed,
        '#postfix' => '</div>',
      ];
      $form['jobs'][$job->bid]['errors'] = [
        '#prefix' => '<div class="align-right">',
        '#markup' => $job->errors(),
        '#postfix' => '</div>',
      ];
      if ($completed == $total) {
        if ($job->status) {
          $form['jobs'][$job->bid]['status'] = [
            '#prefix' => '<div class="align-center">',
            '#markup' => t('Completed'),
            '#postfix' => '</div>',
          ];
        }
        else {
          $form['jobs'][$job->bid]['status'] = [
            '#prefix' => '<div class="align-center">',
            '#type' => 'button',
            '#value' => t('Run finish tasks'),
            '#postfix' => '</div>',
            '#attributes' => ['token' => [$token]],
          ];
        }
      }
      else {
        $form['jobs'][$job->bid]['status'] = [
          '#prefix' => '<div class="align-center">',
          '#type' => 'button',
          '#value' => t('Run'),
          '#postfix' => '</div>',
          '#attributes' => ['token' => [$token]],
        ];
      }
      $form['jobs'][$job->bid]['operations'] = [
        '#prefix' => '<div class="align-center">',
        '#type' => 'button',
        '#value' => t('Delete'),
        '#postfix' => '</div>',
        '#attributes' => ['token' => [$token]],
      ];
    }

    $form['#attached']['library'][] = 'batch_jobs/batch_jobs';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Construct a link.
   *
   * @param string $text
   *   Text to be displayed for link.
   * @param string $url
   *   Url for the link.
   * @param array $classes
   *   Classes for the link.
   */
  private function link($text, $url, array $classes = []) {
    $attributes = '';
    if (count($classes) > 0) {
      $attributes .= 'class="' . implode(' ', $classes) . '" ';
    }
    $attributes .= 'href="' . $url . '"';
    return '<a ' . $attributes . '>' . $text . '</a>';
  }

}
