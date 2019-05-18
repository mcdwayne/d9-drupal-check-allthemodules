<?php

namespace Drupal\helpfulness\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

/**
 * Defines a form showing the submitted feedbacks.
 */
class HelpfulnessReportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helpfulness_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Include the css.
    $form['#attached']['library'] = ['helpfulness/helpfulness-block-form'];

    // Build the array for the header of the table.
    $header = [];
    if (\Drupal::config('helpfulness.settings')->get('helpfulness_report_option_display_username')) {
      $header['uid'] = [
        'data' => t('User'),
        'field' => 'uid',
      ];
    }
    if (\Drupal::config('helpfulness.settings')->get('helpfulness_report_option_display_helpfulness')) {
      $header['helpfulness'] = [
        'data' => t('Helpful'),
        'field' => 'helpfulness',
      ];
    }
    if (\Drupal::config('helpfulness.settings')->get('helpfulness_report_option_display_message')) {
      $header['message'] = [
        'data' => t('Message'),
        'field' => 'message',
      ];
    }
    if (\Drupal::config('helpfulness.settings')->get('helpfulness_report_option_display_base_url')) {
      $header['base_url'] = [
        'data' => t('Base URL'),
        'field' => 'base_url',
      ];
    }
    if (\Drupal::config('helpfulness.settings')->get('helpfulness_report_option_display_system_path')) {
      $header['system_path'] = [
        'data' => t('System Path'),
        'field' => 'system_path',
      ];
    }
    if (\Drupal::config('helpfulness.settings')->get('helpfulness_report_option_display_alias')) {
      $header['path_alias'] = [
        'data' => t('Alias'),
        'field' => 'path_alias',
      ];
    }
    if (\Drupal::config('helpfulness.settings')->get('helpfulness_report_option_display_date')) {
      $header['date'] = [
        'data' => t('Date'),
        'field' => 'timestamp',
      ];
    }
    if (\Drupal::config('helpfulness.settings')->get('helpfulness_report_option_display_time')) {
      $header['time'] = t('Time');
    }
    if (\Drupal::config('helpfulness.settings')->get('helpfulness_report_option_display_useragent')) {
      $header['useragent'] = [
        'data' => t('Browser'),
        'field' => 'useragent',
      ];
    }

    // Setting the sort conditions.
    if (isset($_GET['sort']) && isset($_GET['order'])) {
      // Sort it Ascending or Descending?
      if ($_GET['sort'] == 'asc') {
        $sort = 'ASC';
      }
      else {
        $sort = 'DESC';
      }

      // Which column will be sorted.
      switch ($_GET['order']) {
        case 'User ID':
          $order = 'uid';
          break;

        case 'Helpful':
          $order = 'helpfulness';
          break;

        case 'Message':
          $order = 'message';
          break;

        case 'Base URL':
          $order = 'base_url';
          break;

        case 'System Path':
          $order = 'system_path';
          break;

        case 'Alias':
          $order = 'path_alias';
          break;

        case 'Date':
          $order = 'timestamp';
          break;

        case 'Browser':
          $order = 'useragent';
          break;

        default:
          $order = 'timestamp';
      }
    }
    else {
      $sort = 'ASC';
      $order = 'timestamp';
    }

    // For all feedbacks retrieved from the db.
    $feedbacks = [];

    // Build the query.
    $query = Database::getConnection()->select('helpfulness', 'hf');
    $query->fields('hf');
    $query->orderBy($order, $sort);
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender');

    // Fetch all results from the db.
    foreach ($table_sort->execute() as $row) {
      $feedbacks[] = [
        'fid' => $row->fid,
        'status' => $row->status,
        'uid' => $row->uid,
        'helpfulness' => $row->helpfulness,
        'message' => $row->message,
        'useragent' => $row->useragent,
        'timestamp' => $row->timestamp,
        'system_path' => $row->system_path,
        'path_alias' => $row->path_alias,
        'base_url' => $row->base_url,
      ];
    }

    // Build the rows for the table.
    date_default_timezone_set(drupal_get_user_timezone());
    $options_open = [];
    $options_archived = [];

    foreach ($feedbacks as $feedback) {
      $option = [];

      $tmp_user = \Drupal::entityTypeManager()->getStorage('user')->load($feedback['uid']);
      $username = $tmp_user->getDisplayName();

      $option['uid'] = $feedback['uid'] . ' (' . $username . ')';
      $option['helpfulness'] = ($feedback['helpfulness']) ? 'Yes' : 'No';
      $option['message'] = [
        'data' => [
          '#markup' => str_replace("\n", '<br>', Html::escape($feedback['message'])),
        ],
      ];

      $option['system_path'] = [
        'data' => [
          '#type' => 'link',
          '#title' => $feedback['system_path'],
          '#url' => Url::fromUserInput($feedback['system_path']),
        ],
      ];

      $option['path_alias'] = [
        'data' => [
          '#type' => 'link',
          '#title' => $feedback['path_alias'],
          '#url' => Url::fromUserInput($feedback['path_alias']),
        ],
      ];

      $option['base_url'] = $feedback['base_url'];
      $option['date'] = format_date($feedback['timestamp'], 'custom', 'Y-m-d');
      $option['time'] = format_date($feedback['timestamp'], 'custom', 'H:i');
      $option['useragent'] = [
        'data' => [
          '#markup' => $feedback['useragent'],
          '#prefix' => '<div class="useragent_description">',
          '#suffix' => '</div>',
        ],
      ];

      // Add this feedback to the appropriate table.
      switch ($feedback['status']) {
        case HELPFULNESS_STATUS_OPEN:
          $options_open[$feedback['fid']] = $option;
          break;

        case HELPFULNESS_STATUS_ARCHIVED:
          $options_archived[$feedback['fid']] = $option;
          break;

      }

    }

    // Update actions.
    $options = [
      '' => t('Please Select...'),
      HELPFULNESS_STATUS_OPEN => t('Set as "new"'),
      HELPFULNESS_STATUS_ARCHIVED => t('Archive'),
    ];

    // If the user has permissions to delete feedbacks add that option as well.
    if (\Drupal::currentUser()->hasPermission('delete feedback')) {
      $options += [HELPFULNESS_STATUS_DELETED => t('Delete')];
    }

    $form['update_action'] = [
      '#type' => 'select',
      '#title' => t('Update options:'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    // Submit Button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
    ];

    // Add the tables for open feedbacks to the form.
    $form['helpfulness_feedback_open'] = [
      '#type' => 'fieldset',
      '#title' => t('New feedback'),
    ];

    $form['helpfulness_feedback_open']['helpfulness_feedbacks_open_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options_open,
      '#empty' => t('No new feedback found'),
    ];

    // Add the tables for archived feedbacks to the form.
    $form['helpfulness_feedbacks_archived'] = [
      '#type' => 'fieldset',
      '#title' => t('Archived feedback'),
    ];

    $form['helpfulness_feedbacks_archived']['helpfulness_feedbacks_archived_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options_archived,
      '#empty' => t('No archived feedback found'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $open_fids = array_diff($values['helpfulness_feedbacks_open_table'], ["0"]);
    $archived_fids = array_diff($values['helpfulness_feedbacks_archived_table'], ["0"]);

    if (empty($open_fids) && empty($archived_fids)) {
      drupal_set_message(t('Please select the items you would like to update.'), 'error');
      return;
    }

    $action = $values['update_action'];

    if ($action == HELPFULNESS_STATUS_DELETED) {
      $id_string = implode('-', array_merge($open_fids, $archived_fids));
      $form_state->setRedirect('helpfulness.report_confirm_deletions_form',
        ['idstring' => $id_string]);
    }
    else {
      $this->helpfulnessProcessUpdateAction($action, $open_fids);
      $this->helpfulnessProcessUpdateAction($action, $archived_fids);
      drupal_set_message(t('Your selected items have been updated.'));
    }

  }

  /**
   * Implements helpfulnessProcessUpdateAction().
   */
  private function helpfulnessProcessUpdateAction($action, $selected_fids) {
    if (empty($selected_fids)) {
      return;
    }

    // Build the update query and execute.
    $db = Database::getConnection();
    $query = $db->update('helpfulness');
    $query->fields(['status' => $action]);
    $query->condition('fid', $selected_fids, 'IN');
    $query->execute();
  }

}
