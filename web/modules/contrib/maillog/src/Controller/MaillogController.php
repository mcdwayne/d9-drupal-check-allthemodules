<?php

/**
 * @file
 * Contains \Drupal\maillog\Controller\MaillogController
 */

namespace Drupal\maillog\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MaillogController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection;
   */
  protected $database;

  /**
   * Constructs a \Drupal\maillog\Controller\MaillogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Page callback - Get the Maillog Entry.
   *
   * @param int $maillog_id
   *   The Maillog ID
   *
   * @return array
   *   The output fields
   */
  public function details($maillog_id) {
    $maillog_entry = $this->getMaillogEntry(intval($maillog_id));

    if (!$maillog_entry) {
      throw new NotFoundHttpException();
    }

    $output = array();

    $output['#title'] = $maillog_entry['subject'];

    $output['header_from'] = array(
      '#title' => t('From'),
      '#type' => 'item',
      '#markup' => SafeMarkup::checkPlain($maillog_entry['header_from']),
    );
    $output['header_to'] = array(
      '#title' => t('To'),
      '#type' => 'item',
      '#markup' => SafeMarkup::checkPlain($maillog_entry['header_to']),
    );
    $output['header_reply_to'] = array(
      '#title' => t('Reply to'),
      '#type' => 'item',
      '#markup' => SafeMarkup::checkPlain($maillog_entry['header_reply_to']),
    );
    $output['header_all'] = array(
      '#title' => t('All'),
      '#type' => 'item',
      '#markup' => '<pre>',
    );

    foreach ($maillog_entry['header_all'] as $header_all_name => $header_all_value) {
      $output['header_all']['#markup'] .= SafeMarkup::checkPlain($header_all_name) . ': ' . SafeMarkup::checkPlain($header_all_value) . '<br/>';
    }

    $output['header_all']['#markup'] .= '</pre>';

    $output['body'] = array(
      '#title' => t('Body'),
      '#type' => 'item',
      '#markup' => '<pre>' . SafeMarkup::checkPlain($maillog_entry['body']) . '</pre>',
    );

    return $output;
  }

  /**
   * Page Callback - Delete a specific maillog entry.
   *
   * @param int $maillog_id
   *   The maillog ID.
   */
  public function delete($maillog_id) {
    $idmaillog = intval($maillog_id);
    $this->database->query("DELETE FROM {maillog} WHERE idmaillog = :id", array(':id' => $idmaillog));
    drupal_set_message(t('Mail with ID @idmaillog has been deleted!', array('@idmaillog' => $idmaillog)));

    return $this->redirect('view.maillog_overview.page_1');
  }

  /**
   * Loads the Maillog Entry.
   *
   * @param int $maillog_id
   *   The maillog ID.
   *
   * @return array
   *   Maillog entry as Array
   */
  protected function getMaillogEntry($maillog_id) {
    $result = $this->database->query("SELECT idmaillog, header_from, header_to, header_reply_to, header_all, subject, body FROM {maillog} WHERE idmaillog=:id", array(
      ':id' => $maillog_id,
    ));

    if ($maillog = $result->fetchAssoc()) {
      // Unserialize values.
      $maillog['header_all'] = unserialize($maillog['header_all']);
    }
    return $maillog;
  }
}
