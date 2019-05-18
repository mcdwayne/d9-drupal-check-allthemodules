<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CloudwordsProjectClosedOverviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_project_closed_overview_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['rescan'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
    ];

    $form['rescan']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Scan for cancelled projects in Cloudwords'),
      '#prefix' => '<div>' . $this->t('This will check for cancelled projects and will release (make translatable) the content from those projects') . '</div>',
      '#submit' => [
        'cloudwords_project_overview_scan_cancelled'
        ],
    ];

    // Select table.
    $projects = \Drupal::database()->select('cloudwords_project', 'cp')
      ->fields('cp', ['pid', 'name', 'status'])
      ->condition('status', cloudwords_project_closed_statuses())
      // ->extend('TableSort')
    ->extend('PagerDefault')
      ->limit(50)
      ->execute()
      ->fetchAll();

    $rows = [];

    foreach ($projects as $project) {
      $row = [];
      // @FIXME
      // l() expects a Url object, created from a route name or external URI.
      // $row[] = l($project->name, 'admin/structure/cloudwords/project/' . $project->pid);
      switch ($project->status) {
        case 'cancelled':
          $row[] = $this->t('Cancelled');
          break;

        case 'project_closed':
          $row[] = $this->t('Closed');
          break;

        case 'drupal_cancelled':
          $row[] = $this->t('Cancelled in Drupal');
          break;

        default:
          $row[] = $this->t('Inactive');
          break;
      }
      $rows[] = $row;
    }

    $header = [
      $this->t('Name'),
      $this->t('Status'),
    ];

    $form['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'id' => 'cloudwords-project-table'
        ],
      '#empty' => $this->t('No closed projects.'),
    ];

    $form['pager'] = ['#theme' => 'pager'];

    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle submitted form data.
  }

}
