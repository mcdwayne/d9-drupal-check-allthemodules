<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsNodesSingleEditForm extends FormBase {

  protected $time;
  protected $database;
  protected $currentPath;

  /**
   * Implements __construct().
   */
  public function __construct(TimeInterface $time_interface, CurrentPathStack $current_path, Connection $database_connection) {
    $this->time = $time_interface;
    $this->currentPath = $current_path;
    $this->database = $database_connection;
  }

  /**
   * Implements create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('path.current'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_nodes_single_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $nid = $arg[1];
    $sid = $arg[4];
    $db = $this->database;
    $query = $db->select('simply_signups_fields', 'p');
    $query->fields('p');
    $query->condition('nid', $nid, '=');
    $results = $query->execute()->fetchAll();
    $fieldCount = $query->countQuery()->execute()->fetchField();
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-nodes-single-edit-form', 'simply-signupa-form'],
    ];
    if ($fieldCount > 0) {
      $form['signup_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Edit current signup'),
      ];
      $results = $query->execute()->fetchAll();
      $data = $db->select('simply_signups_data', 'p');
      $data->fields('p');
      $data->condition('id', $sid, '=');
      $rowCount = $data->countQuery()->execute()->fetchField();
      $row = $data->execute()->fetchAll();
      $rowData = unserialize($row[0]->fields);
      if ($rowCount == 0) {
        throw new NotFoundHttpException();
      }
      foreach ($results as $row) {
        $field = unserialize($row->field);
        if (($field['#type'] == 'select') or ($field['#type'] == 'radios') or ($field['#type'] == 'checkboxes')) {
          $form['signup_fieldset'][$field['#title']] = [
            '#type' => $field['#type'],
            '#title' => $row->name,
            '#options' => $field['#options'],
            '#description' => (isset($field['#description'])) ? $field['#description'] : '',
            '#default_value' => $rowData[$field['#title']]['value'],
            '#required' => $field['#required'],
            '#disabled' => (isset($field['#disabled'])) ? $field['#disabled'] : 0,
          ];
        }
        else {
          $form['signup_fieldset'][$field['#title']] = [
            '#type' => $field['#type'],
            '#title' => $row->name,
            '#description' => (isset($field['#description'])) ? $field['#description'] : '',
            '#default_value' => $rowData[$field['#title']]['value'],
            '#required' => $field['#required'],
            '#disabled' => (isset($field['#disabled'])) ? $field['#disabled'] : 0,
          ];
        }
      }
      $form['signup_fieldset']['nid'] = [
        '#type' => 'hidden',
        '#value' => $nid,
      ];
      $form['signup_fieldset']['sid'] = [
        '#type' => 'hidden',
        '#value' => $sid,
      ];
      $form['signup_fieldset']['actions'] = [
        '#type' => 'actions',
      ];
      $form['signup_fieldset']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Edit signup'),
        '#attributes' => [
          'class' => [
            'btn-primary',
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $requestTime = $this->time->getCurrentTime();
    $sid = $values['sid'];
    $nid = $values['nid'];
    foreach ($values as $key => $value) {
      if ($key != 'submit' and $key != 'form_build_id' and $key != 'form_token' and $key != 'form_id' and $key != 'op' and isset($form['signup_fieldset'][$key]['#title'])) {
        $fields['fields'][$key]['value'] = $value;
        $fields['fields'][$key]['title'] = $form['signup_fieldset'][$key]['#title'];
      }
    }
    unset($fields['fields']['submit']);
    unset($fields['fields']['form_build_id']);
    unset($fields['fields']['form_token']);
    unset($fields['fields']['form_id']);
    unset($fields['fields']['op']);
    unset($fields['fields']['sid']);
    unset($fields['fields']['nid']);
    $numberAttendingFlag = (isset($fields['fields']['number_attending'])) ? 1 : 0;
    $fields['fields']['number_attending']['value'] = (isset($fields['fields']['number_attending']['value'])) ? $fields['fields']['number_attending']['value'] : 1;
    $fields['fields']['number_attending']['title'] = (isset($fields['fields']['number_attending']['title'])) ? $fields['fields']['number_attending']['title'] : '# Attending';
    $numberAttending = $fields['fields']['number_attending']['value'];
    if ($numberAttendingFlag == 0) {
      unset($fields['fields']['number_attending']);
    }
    $row = [
      'fields' => serialize($fields['fields']),
      'attending' => $numberAttending,
      'status' => 0,
      'updated' => $requestTime,
    ];

    $db = $this->database;
    $query = $db->update('simply_signups_data');
    $query->fields($row);
    $query->condition('id', $sid, '=');
    $query->condition('nid', $nid, '=');
    $query->execute();
    $form_state->setRedirect('simply_signups.nodes', ['node' => $nid]);
    drupal_set_message($this->t('Youe signup has been edited successsfully.'));
  }

}
