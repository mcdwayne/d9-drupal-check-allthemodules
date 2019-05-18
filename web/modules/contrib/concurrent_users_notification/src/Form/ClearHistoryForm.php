<?php

namespace Drupal\concurrent_users_notification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ClearHistoryForm.
 *
 * @package Drupal\concurrent_users_notification\Form
 */
class ClearHistoryForm extends FormBase {

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database')
    );
  }

  /**
   * TableSortExampleController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *    The connection to database.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clear_history_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['clear_history'] = array(
      '#type' => 'button',
      '#title' => 'Clear History',
      '#value' => 'Clear History',
      '#description' => 'Clear all data from Database.',
      '#ajax' => array(
        'callback' => '::clearHistoryCallback',
        'wrapper' => 'cuncurrent-user-history-table-wrapper',
        'method' => 'replace',
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => 'clearing history table',
        )
      )
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit form Code goes here.
  }

  /**
   * The clear history Callback.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return string $content
   *    The return string of data.
   */
  public function clearHistoryCallback(array &$form, FormStateInterface $form_state) {
    db_truncate('concurrent_users_notification')
        ->execute();
    // Disable caching on this form.
    $form_state->setCached(FALSE);
    $header = array(
      array('data' => 'ID', 'field' => 't.item_id'),
      array('data' => 'Date', 'field' => 't.concurrent_logins_date'),
      array('data' => 'Concurrent User Count (MAX)', 'field' => 't.concurrent_logins_count'),
    );

    $rows = array();
    $content = array();

    $content['content']['tablesort_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'cuncurrent-user-history-table-wrapper'],
      '#empty' => 'No entries available.',
    );
    $content['content']['#markup'] = 'Entries deleted successfully.';
    return $content;
  }

}
