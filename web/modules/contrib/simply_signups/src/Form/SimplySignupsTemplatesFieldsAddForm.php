<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsTemplatesFieldsAddForm extends FormBase {

  protected $database;
  protected $currentPath;

  /**
   * Implements __construct function.
   */
  public function __construct(CurrentPathStack $current_path, Connection $database_connection) {
    $this->currentPath = $current_path;
    $this->database = $database_connection;
  }

  /**
   * Implements create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.current'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_templates_fields_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $tid = $arg[4];
    $db = $this->database;
    $query = $db->select('simply_signups_templates', 'p');
    $query->fields('p');
    $query->condition('id', $tid, '=');
    $count = $query->countQuery()->execute()->fetchField();
    if ($count == 0) {
      throw new NotFoundHttpException();
    }
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-templates-fields-add-form', 'simply-signups-form'],
    ];
    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $tid,
    ];
    $form['field_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Field Type'),
      '#options' => [
        'checkboxes' => $this->t('Checkboxes'),
        'checkbox' => $this->t('Checkbox'),
        'email' => $this->t('Email'),
        'hidden' => $this->t('Hidden'),
        'number' => $this->t('Number'),
        'radio' => $this->t('Radio'),
        'radios' => $this->t('Radios'),
        'select' => $this->t('Select'),
        'tel' => $this->t('Telephone'),
        'textarea' => $this->t('Textarea'),
        'textfield' => $this->t('Textfield'),
      ],
      '#description' => $this->t('Select the type of field that you wish to add.'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select field type'),
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
        'title' => $this->t('Return to template fields'),
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
    $values = $form_state->getValues();
    $tid = $values['tid'];
    $form_state->setRedirect('simply_signups.templates.fields', ['tid' => $tid]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $tid = $values['tid'];
    $form_state->setRedirect('simply_signups.templates.fields.' . $values['field_type'], ['tid' => $tid]);
  }

}
