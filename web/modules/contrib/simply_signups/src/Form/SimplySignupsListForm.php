<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simply_signups\Utility\SimplySignupsUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a signup form.
 */
class SimplySignupsListForm extends FormBase {

  protected $time;
  protected $database;
  protected $currentPath;
  protected $dateFormatter;
  protected $entityTypeManager;

  /**
   * Implements __construct function.
   */
  public function __construct(TimeInterface $time_interface, CurrentPathStack $current_path, Connection $database_connection, DateFormatter $date_formatter, EntityTypeManagerInterface $entity_type_manager) {
    $this->time = $time_interface;
    $this->currentPath = $current_path;
    $this->database = $database_connection;
    $this->dateFormatter = $date_formatter;
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
      $container->get('date.formatter'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $nid = $arg[1];
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    $id = $node->id();
    $numberOfCheckedInAttending = SimplySignupsUtility::getNumberOfCheckedInsAttending($id);
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-list-form', 'simply-signups-form'],
    ];
    $db = $this->database;
    $query = $db->select('simply_signups_data', 'p');
    $query->fields('p');
    $query->condition('nid', $id, '=');
    $signupCount = $query->countQuery()->execute()->fetchField();
    $attendingCount = 0;
    $results = $query->execute()->fetchAll();
    $header = [
      'signup_data' => $this->t('Signup'),
      'number_attending' => $this->t('# Attending'),
      'status' => $this->t('Status'),
      'updated' => $this->t('Updated'),
      'operations' => $this->t('Operations'),
    ];
    $output = [];
    $attendingCount = 0;
    $checkedInCount = 0;
    foreach ($results as $row) {
      $links['status'] = [
        'title' => $this->t('status'),
        'url' => Url::fromRoute('simply_signups.nodes.single', ['node' => $id, 'sid' => $row->id]),
        'attributes' => [
          'id' => 'signup-list-view-modal',
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
        ],
      ];
      $links['edit'] = [
        'title' => $this->t('edit'),
        'url' => Url::fromRoute('simply_signups.nodes.single.edit', ['node' => $id, 'sid' => $row->id]),
      ];
      $links['remove'] = [
        'title' => $this->t('remove'),
        'url' => Url::fromRoute('simply_signups.nodes.single.remove', ['node' => $id, 'sid' => $row->id]),
      ];
      $updatedDate = $this->dateFormatter->format($row->updated, 'custom', 'm/d/Y - h:i a');
      $attendingCount = ($attendingCount + $row->attending);
      $checkedInCount = ($row->status == 1) ? ($checkedInCount + 1) : ($checkedInCount + 0);
      $rawSignupData = unserialize($row->fields);
      $signupData = '';
      $x = 0;
      foreach ($rawSignupData as $field) {
        if ($x < 3) {
          if ($field['type'] == "tel") {
            $formattedTelephone = SimplySignupsUtility::formatTelephone($field['value'], 3);
            $field['value'] = $formattedTelephone;
          }
          $signupData .= $field['title'] . ":" . $field['value'] . "<br />";
        }
        $x++;
      }
      $output[($row->id + 1)] = [
        'signup_data' => check_markup($signupData, 'full_html'),
        'number_attending' => $row->attending,
        'status' => ($row->status == 1) ? $this->t('<div class="btn btn-xs btn-success">Checked-In</div>') : $this->t('-'),
        'updated' => $updatedDate,
        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#links' => $links,
          ],
        ],
      ];
    }
    $form['total_attending'] = [
      '#markup' => $this->t('<div class="simply-signups-total-attending simply-signups-action-info">Attending: <strong>@attendingCount</strong></div>', ['@attendingCount' => $attendingCount]),
    ];
    $form['total_signups'] = [
      '#markup' => $this->t('<div class="simply-signups-total-signups simply-signups-action-info">Submissions: <strong>@signupCount</strong></div>', ['@signupCount' => $signupCount]),
    ];
    $form['total_checked_in_attending'] = [
      '#markup' => $this->t('<div class="simply-signups-total-attended simply-signups-action-info">Attending (Checked-In): <strong>@numberOfCheckedInAttending</strong></div>', ['@numberOfCheckedInAttending' => $numberOfCheckedInAttending]),
    ];
    $form['total_checked_in'] = [
      '#markup' => $this->t('<div class="simply-signups-total-attended simply-signups-action-info">Submissions (Checked-In): <strong>@checkedInCount</strong></div>', ['@checkedInCount' => $checkedInCount]),
    ];
    if ($signupCount > 0) {
      $options = ['absolute' => TRUE];
      $url = Url::fromRoute('simply_signups.nodes.csv', ['node' => $nid], $options);
      $link = Link::fromTextAndUrl('Download signups', $url)->toString();
      $form['download_csv'] = [
        '#markup' => $this->t('<div class="simply-signups-download-csv simply-signups-action-info">@link</div>', ['@link' => $link]),
      ];
    }
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#empty' => $this->t('No signups found.'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove selected items'),
      '#attributes' => [
        'class' => [
          'button--danger',
          'btn-link',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $rows = $values['table'];
    $selected = array_filter($rows);
    if (empty($selected)) {
      $form_state->setErrorByName('table', $this->t('Must select at least one item.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $rows = $values['table'];
    $rows = array_filter($rows);
    foreach ($rows as $row) {
      $item = ($row - 1);
      $db = $this->database;
      $query = $db->delete('simply_signups_data');
      $query->condition('id', $item, '=');
      $query->execute();
    }
    drupal_set_message($this->t('Successfully removed selected signup(s).'));
  }

}
