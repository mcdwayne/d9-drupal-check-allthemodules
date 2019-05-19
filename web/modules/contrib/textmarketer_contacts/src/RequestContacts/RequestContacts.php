<?php

/**
 * @file
 * Allows the site to send and receive user contacts to and from Text Marketer.
 */

namespace Drupal\textmarketer_contacts\RequestContacts;

use Drupal\Component\Utility;
use Drupal\Component\Utility\Html;
use Drupal\Core\Annotation\Action;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * RequestContacts class.
 */
class RequestContacts extends ControllerBase {

  protected $configFactory;
  protected $httpClient;
  protected $db;

  /**
   * The constructor.
   */
  public function __construct(Client $http_client, Connection $db, ConfigFactory $config_factory) {

    $this->db = $db;
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * Returns custom http client and the configuration settings.
   *
   * @return \GuzzleHttp\Client and configuration settings.
   *   A guzzle http client instance and settings.
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('textmarketer_contacts.client'),
      $container->get('database'),
      $container->get('config.factory')
    );
  }

  /**
   * Generates the table for displaying users.
   *
   * @return array
   *   Returns the generated table.
   */
  public function generateTable() {

    try {

      return $this->prepareTable();
    }
    catch (\Exception $e) {
      $message = t('An error has occured with status code: @code',
        ['@code' => $e->getMessage()]);

      // @todo: Use service logger factory.
      \Drupal::logger('textmarketer')->error($message);
    }
  }

  /**
   * Prepares the table used for displaying users.
   *
   * @return array
   *   Returns the prepared table.
   */
  protected function prepareTable() {

    $results = $this->queryUsers();
    $matching = $results['matching'];
    $non_matching = $results['non_matching'];
    $output = '<h4>' . t('Contacts matching the site users are listed in a
    table.') . '</h4>';

    if ($matching) {
      // Render the download button.
      $output .= '<div class="textmarketer-download-csv">'
        . render($this->downloadButton()) . '</div><br>';
      $rows = [];
      $message = $this->getMessage(count($matching), count($non_matching));

      drupal_set_message($message);

      foreach ($matching as $user) {
        foreach ($user as $row) {
          if ($row->uid > 0) {
            $rows[] = [
              Html::escape($row->telephone),
              $row->entity_id,
              // @todo: Add links service to the container.
              Link::fromTextAndUrl($row->name,
                Url::fromRoute('entity.user.edit_form',
                  ['user' => $row->entity_id])),
              $row->mail,
              date('d F Y', $row->created),
            ];
          }
        }
      }

      $output .= $this->getTable($rows);
    }
    else {
      drupal_set_message(t('There were no matching results.'), 'warning');
    }

    return ['#markup' => $output];
  }

  /**
   * Queries the site users for matching numbers.
   *
   * @return mixed
   *   Returns array of matching and non-matching numbers.
   *
   * @throws \Exception
   *   Throws error on failure.
   */
  protected function queryUsers() {

    $numbers = $this->responseParser();
    $field_phone = $this->getConfig()->get('field_telephone');

    foreach ($numbers as $number) {
      $query = $this->db->select('user__' . $field_phone, 't');
      $query->fields('t', ['entity_id']);
      $query->addField('t', $field_phone . '_value', 'telephone');

      // Checks if we have a matching telephone number.
      $match = $this->queryCondition($query, $number);

      if ($match) {
        // If there is a matching telephone get the rest of the user details.
        $query->Join('users_field_data', 'u', 't.entity_id = u.uid');
        $query->fields('u', ['uid', 'name', 'mail', 'created']);
        $query->orderBy('created', 'DESC');

        $this->numbers['matching'][] = $query->execute()->fetchAll();
      }
      else {
        $this->numbers['non_matching'][] = $number;
      }
      // @todo: Add database exception.
    }

    return $this->numbers;
  }

  /**
   * Handles the client's response and parses it.
   *
   * @return array
   *   Telephone numbers
   *
   * @throws \Exception
   *   Throws exception of failure.
   */
  protected function responseParser() {

    switch ($this->getClient()->getStatusCode()) {
      case '200':
        $xml = simplexml_load_string($this->getClient()->getBody());
        $json_decode = json_decode(json_encode($xml), TRUE);

        foreach ($json_decode['group']['number'] as $number) {
          if (isset($number)) {
            $this->numbers[] = substr_replace($number, 0, 0, 2);
          }
        }

        return $this->numbers;

      default:
        $message = $this->getClient()->error . ' ;' . $this->getClient()->data;
        throw new \Exception($message);
    }
  }

  /**
   * Uses the Http Client to initiate a get operation.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|\Psr\Http\Message\ResponseInterface
   *   initiates get operation
   */
  protected function getClient() {

    $group_name = $this->getConfig()->get('group_name');
    $op = "/services/rest/group/{$group_name}";

    try {
      $this->response = $this->httpClient->get($op, ['http_errors' => FALSE]);

      return $this->response;
    }
    catch (RequestException $e) {

      return ($this->t('Error'));
    }
  }

  /**
   * Helper function prepares the configuration settings.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   Returns the configs.
   */
  private function getConfig() {

    $configuration = $this->configFactory->get('textmarketer_contacts.settings');

    return $configuration;
  }

  /**
   * The conditions used when querying users for matching numbers.
   *
   * @param object $query
   *   The query conditions.
   * @param string $number
   *   The telephone number.
   *
   * @return mixed
   *   Returns query conditions.
   */
  private function queryCondition($query, $number) {

    $field_phone = $this->getConfig()->get('field_telephone');
    $bundle = 'user';

    // Check if we have a matching telephone number.
    return $this->match = $query
      ->condition('t.' . $field_phone . '_value',
        $this->db->escapeLike($number), 'LIKE')
      ->condition('t.bundle', $bundle, '=')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Prepares a themed download button.
   *
   * @return array|\mixed[]
   *   Returns download button with attributes.
   */
  protected function downloadButton() {
    $url = Url::fromRoute('textmarketer_contacts.download_csv');
    $this->button = Link::fromTextAndUrl(t('Dowload CSV'), $url);
    $this->button = $this->button->toRenderable();
    $this->button['#attributes'] = [
      'class' => [
        'button',
        'button--primary',
      ],
    ];

    return $this->button;
  }

  /**
   * Helper function formats a message in singular and plural.
   *
   * @param int $a
   *   The count to be inspected.
   * @param int $b
   *   The count to be inspected.
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableMarkup
   *   Returns formatted message.
   */
  protected function getMessage($a, $b) {

    $this->message = $this->formatPlural($a,
      'We found 1 user with matching mobile number, and %non_matching numbers
        did not match any users.',
      'We found @count users with matching mobile numbers, and %non_matching
        numbers did not match any users.',
      ['%non_matching' => $b]);

    return $this->message;
  }

  /**
   * Returns a rendered table.
   *
   * @param array $rows
   *   Table rows.
   *
   * @return string
   *   Rendered table.
   */
  protected function getTable(array $rows) {

    $table = [
      '#type' => 'table',
      '#header' => $this->getTableHeader(),
      '#rows' => $rows,
      '#attributes' => ['id' => 'textmarketer_contacts'],
    ];
    $this->table = render($table);
    $pager = ['#theme' => 'pager'];
    $this->table .= render($pager);

    return $this->table;
  }

  /**
   * Prepares table header.
   *
   * @return array
   *   Table headers.
   */
  protected function getTableHeader() {

    return [
      t('Telephone'),
      t('User ID'),
      t('Username'),
      t('Email address'),
      t('Created'),
    ];
  }

  /**
   * Prepares the CSV file and forces a file download.
   */
  public function generateCsv() {

    // Gets the service container - Symfony closure.
    $container = $this->container;
    $response = new StreamedResponse(function () use ($container) {

      $this->csvFile();
    });

    $this->setHttpHeader($response);

    // @todo: Add exception.
    return $response;

  }

  /**
   * Generates a CSV file.
   */
  protected function csvFile() {

    $matching_results = $this->queryUsers()['matching'];

    foreach ($matching_results as $result) {
      foreach ($result as $user) {
        $user_details[] = $user;
      }
    }

    $fp = fopen('php://output', 'w');

    fputcsv($fp, [t('Number'), t('Username'), t('Email')]);

    foreach ($user_details as $row) {
      unset($row->entity_id);
      unset($row->uid);
      unset($row->created);
      fputcsv($fp, (array) $row);
    }

    fclose($fp);
  }

  /**
   * Sets the http headers.
   *
   * @param object $response
   *   StreamedResponse using container.
   */
  protected function setHttpHeader($response) {

    $name = 'textmarketer_' . microtime(TRUE) . '.csv';

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename='
      . $name);
  }

}
