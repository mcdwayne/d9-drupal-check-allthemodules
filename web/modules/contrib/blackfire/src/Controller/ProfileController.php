<?php

namespace Drupal\blackfire\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Display Blackfire profiles.
 *
 * @package Drupal\blackfire\Controller
 */
class ProfileController implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * ProfileController constructor.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter.
   */
  public function __construct(Connection $db, DateFormatter $dateFormatter) {
    $this->db = $db;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * List profiles.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listProfiles(Request $request) {
    $headers = [
      ['data' => $this->t('Profile'), 'field' => 'title'],
      ['data' => $this->t('Page'), 'field' => 'uri'],
      ['data' => $this->t('Method'), 'field' => 'method'],
      ['data' => $this->t('Time'), 'field' => 'timestamp', 'sort' => 'desc'],
    ];

    $query = $this->db->select('blackfire_profiles')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->fields('blackfire_profiles')
      ->orderByHeader($headers);
    $rows = [];
    foreach ($query->execute()->fetchAll() as $profile) {
      $row = [];

      $blackfire_uri = Url::fromUri('https://blackfire.io/profiles/' .
        $profile->profile_id . '/graph');
      $title = $profile->title ?: $this->t('Profile');
      $title = Unicode::truncate($title, 50, FALSE, TRUE);
      $row['data']['title'] = Link::fromTextAndUrl($title, $blackfire_uri);

      $uri = Unicode::truncate($profile->uri, 100, FALSE, TRUE);
      $row['data']['uri'] = Link::fromTextAndUrl($uri,
        Url::fromUri($profile->uri));
      $row['data']['method'] = $profile->method;
      $row['data']['time'] = $this->dateFormatter->format($profile->timestamp);

      $rows[] = $row;
    }

    $build['profiles'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No Blackfire profiles found'),
    ];
    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

}
