<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsTemplatesEditForm extends FormBase {

  protected $time;
  protected $database;
  protected $currentPath;

  /**
   * Implements __construct function.
   */
  public function __construct(TimeInterface $time_interface, Connection $database_connection, CurrentPathStack $current_path) {
    $this->time = $time_interface;
    $this->database = $database_connection;
    $this->currentPath = $current_path;
  }

  /**
   * Implements create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('database'),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_templates_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $tid = $arg[4];
    $title = FALSE;
    $db = $this->database;
    $query = $db->select('simply_signups_templates', 'p');
    $query->fields('p');
    $query->condition('id', $tid, '=');
    $count = $query->countQuery()->execute()->fetchField();
    if ($count == 0) {
      throw new NotFoundHttpException();
    }
    $results = $query->execute()->fetchAll();
    foreach ($results as $row) {
      $title = $row->title;
      $status = $row->status;
    }
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-template-edit-form', 'simply-signups-form'],
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 64,
      '#required' => TRUE,
      '#default_value' => ($title) ? $title : '',
    ];
    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Make this your default template?'),
      '#default_value' => ($status == 1) ? 1 : 0,
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
    ];
    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $tid,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => ($count == 0) ? $this->t('Go back') : $this->t('Save template'),
      '#attributes' => [
        'class' => [
          'button--primary',
          'btn-primary',
        ],
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value'  => 'Cancel',
      '#attributes' => [
        'title' => $this->t('Return to templates'),
        'class' => [
          'button--danger',
          'btn-link',
        ],
      ],
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [['tid']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('simply_signups.templates');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $requestTime = $this->time->getCurrentTime();
    $db = $this->database;
    if ($values['status'] == 1) {
      $row = [
        'status' => 0,
      ];
      $db->update('simply_signups_templates')
        ->fields($row)
        ->execute();
    }
    $row = [
      'title' => $this->t('@title', ['@title' => $values['title']]),
      'status' => $values['status'],
      'updated' => $requestTime,
    ];
    $db = $this->database;
    $db->update('simply_signups_templates')
      ->fields($row)
      ->condition('id', $values['tid'], '=')
      ->execute();
    $form_state->setRedirect('simply_signups.templates');
    drupal_set_message($this->t('Template <em>@title</em> updated successfully', ['@title' => $values['title']]));
  }

}
