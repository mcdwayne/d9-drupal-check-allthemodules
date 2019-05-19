<?php

namespace Drupal\user_expire\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Report controller of User Expire module.
 */
class UserExpireReport extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a \Drupal\user_expire\Controller object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *    The date formatter service.
   *
   * @param \Drupal\Core\Database\Connection $database
   *    The database service.
   */
  public function __construct(DateFormatterInterface $date_formatter, Connection $database) {
    $this->dateFormatter = $date_formatter;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function listOfUsers() {
    $header = array(
      'username' => array(
        'data' => $this->t('Username'),
        'field' => 'u.name',
      ),
      'access' => array(
        'data' => $this->t('Last access'),
        'field' => 'u.access',
      ),
      'expiration' => array(
        'data' => $this->t('Expiration'),
        'field' => 'expiration',
        'sort' => 'asc',
      ),
    );
    $rows = array();

    $query = $this->database->select('user_expire', 'ue');
    $query->join('users_field_data', 'u', 'ue.uid = u.uid');

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query
      ->fields('u', array('uid', 'name', 'access'))
      ->fields('ue', array('expiration'))
      ->limit(50)
      ->orderByHeader($header);

    $accounts = $query->execute();

    foreach ($accounts as $account) {
      $username = array(
        '#theme' => 'username',
        '#account' => $this->entityTypeManager()->getStorage('user')->load($account->uid),
      );

      $rows[$account->uid] = array(
        'username' => render($username),
        'access' => $account->access ? $this->t('@time ago', array('@time' => $this->dateFormatter->formatInterval(REQUEST_TIME - $account->access))) : $this->t('never'),
        'expiration' => $this->t('@time from now', array('@time' => $this->dateFormatter->formatInterval($account->expiration - REQUEST_TIME))),
      );
    }

    $table = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $table;
  }
}
