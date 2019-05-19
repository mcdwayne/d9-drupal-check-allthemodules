<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsNodesSingleForm extends FormBase {

  protected $time;
  protected $database;
  protected $currentPath;
  protected $entityTypeManager;

  /**
   * Implements __construct function.
   */
  public function __construct(TimeInterface $time_interface, CurrentPathStack $current_path, Connection $database_connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->time = $time_interface;
    $this->currentPath = $current_path;
    $this->database = $database_connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('path.current'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_nodes_single_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $nid = $arg[1];
    $sid = $arg[3];
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    $isValidNode = (isset($node)) ? TRUE : FALSE;
    if (!$isValidNode) {
      throw new NotFoundHttpException();
    }
    $db = $this->database;
    $query = $db->select('simply_signups_data', 'p');
    $query->fields('p');
    $query->condition('id', $sid, '=');
    $signupCount = $query->countQuery()->execute()->fetchField();
    if ($signupCount == 0) {
      throw new NotFoundHttpException();
    }
    $results = $query->execute()->fetchAll();
    foreach ($results as $row) {
      $fields = unserialize($row->fields);
      $statusData = $row->status;
      $status = ($row->status = 1) ? '<div><strong>Status:</strong> - </div>' : '<div><strong>Status:</strong> Checked In</div>';
    }
    $data = '';
    foreach ($fields as $field) {
      $data .= '<div><strong>' . $field['title'] . ':</strong> ' . $field['value'] . '</div>';
    }
    $data .= $status;
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-single-form', 'simply-signups-form'],
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];
    $form['sid'] = [
      '#type' => 'hidden',
      '#value' => $sid,
    ];
    $form['status'] = [
      '#type' => 'hidden',
      '#value' => ($statusData == 1) ? 1 : 0,
    ];
    $form['signup_entry'] = [
      '#markup' => '<div>' . $data . '</div>',
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => ($statusData == 1) ? $this->t('Check Out') : $this->t('Check In'),
      '#attributes' => [
        'class' => ($statusData == 1) ? ['btn-primary'] : ['btn-success'],
      ],
    ];
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
    $updatedStatus = ($values['status'] == 1) ? 0 : 1;
    $row = [
      'status' => $updatedStatus,
      'updated' => $requestTime,
    ];
    $db = $this->database;
    $db->update('simply_signups_data')
      ->fields($row)
      ->condition('id', $values['sid'], '=')
      ->execute();
    $message = ($updatedStatus == 1) ? 'Successfully <strong>checked in</strong> current signup entry.' : 'Successfully <strong>checked out</strong> current signup entry.';
    $form_state->setRedirect('simply_signups.nodes', ['node' => $values['nid']]);
    drupal_set_message($this->t('@message', ['@message' => $message]));
  }

}
