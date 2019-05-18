<?php

namespace Drupal\md_site_verify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\domain\DomainStorage;
use Drupal\md_site_verify\Service\DomainSiteVerifyService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Site Verify module routes.
 */
class DomainSiteVerifyController extends ControllerBase {

  /**
   * @var \Drupal\Core\Database\Connection $database
   */
  protected $database;

  /**
   * Domain storage.
   *
   * @var \Drupal\md_site_verify\Service\DomainSiteVerifyService
   *   $domainSiteVerify
   */
  protected $domainSiteVerify;

  /**
   * Domain storage.
   *
   * @var \Drupal\domain\DomainStorageInterface $domainStorage
   */
  protected $domainStorage;

  /**
   * DomainSiteVerifyController constructor.
   *
   * @param \Drupal\domain\DomainStorage $domainStorage
   *   The domain storage.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database loader.
   *
   * @param \Drupal\md_site_verify\Service\DomainSiteVerifyService $domainSiteVerify
   *   The service domain verification.
   */
  public function __construct(DomainStorage $domainStorage, Connection $database, DomainSiteVerifyService $domainSiteVerify) {
    $this->domainStorage = $domainStorage;
    $this->database = $database;
    $this->domainSiteVerify = $domainSiteVerify;
  }

  /**
   * Create function return static.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return static
   *   Return domain loader configuration and database and domain service verfy.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('domain'),
      $container->get('database'),
      $container->get('md_site_verify_service')
    );
  }

  /**
   * Load domain options lists.
   *
   * @param string $domain_id
   *   A string domain id.
   *
   * @return []
   *   An arary of the domain name, or FALSE if not found.
   */
  public function domainSiteVerifyLoadOptionsList($domain_id) {
    $domains = $this->domainStorage->loadOptionsList();
    return isset($domains[$domain_id]) ? $domains[$domain_id] : FALSE;
  }

  /**
   * Create domain link.
   *
   * @param string $domain_id
   *   The domain id.
   *
   * @return []
   *   A path, FALSE if not found.
   */
  public function domainSiteVerifyCreateLink($domain_id) {
    $domains = [];
    $domainLoad = $this->domainStorage->loadMultiple();
    foreach ($domainLoad as $id => $domain) {
      $domains[$id] = $domain->getPath();
    }
    return isset($domains[$domain_id]) ? $domains[$domain_id] : FALSE;
  }

  /**
   * Controller content callback: Verifications List page.
   *
   * @return string
   *   Render Array
   */
  public function verificationsListPage() {

    $options = [];
    $domainLoad = $this->domainStorage->loadMultiple();
    foreach ($domainLoad as $id => $domain) {
      $options[$id] = $domain->getPath();
    }

    $engines = $this->domainSiteVerify->domainSiteVerifyGetEngines();
    $destination = \Drupal::destination()->getAsArray();

    $header = [
      ['data' => $this->t('Engine'), 'field' => 'engine'],
      ['data' => $this->t('Domain'), 'field' => 'domain_id'],
      ['data' => $this->t('Meta tag'), 'field' => 'meta'],
      ['data' => $this->t('File'), 'field' => 'file'],
      ['data' => $this->t('Operations')],
    ];

    $verifications = $this->database->select('md_site_verify', 'sv')
      ->fields('sv')
      ->extend('Drupal\\Core\\Database\\Query\\PagerSelectExtender')
      ->limit(10)
      ->execute();

    $rows = [];
    foreach ($verifications as $verification) {
      $row = ['data' => []];
      $row['data'][] = $engines[$verification->engine]['name'];
      $row['data'][] = $this->domainSiteVerifyLoadOptionsList($verification->domain_id);
      $row['data'][] = $verification->meta ? $this->t('Yes') : $this->t('No');
      $row['data'][] = $verification->file ? $verification->file : $this->t('None');
      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('md_site_verify.verification_edit', ['dsverify' => $verification->dsv_id]),
        'query' => $destination,
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('md_site_verify.verification_delete', ['dsverify' => $verification->dsv_id]),
        'query' => $destination,
      ];
      $row['data']['operations'] = [
        'data' => [
          '#theme' => 'links',
          '#links' => $operations,
          '#attributes' => ['class' => ['links', 'inline']],
        ],
      ];
      $rows[] = $row;
    }

    $build['verification_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No verifications available. <a href="@link">Add verification</a>.', [
        '@link' => Url::fromRoute('md_site_verify.verification_add')
          ->toString(),
      ]),
    ];

    $build['verification_pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Controller content callback: Verifications File content.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response containing the Verification File content.
   */
  public function domainVerificationsFileContent($dsverify) {
    $verification = $this->domainSiteVerify->DomainsiteVerifyLoad($dsverify);
    if ($verification['file_contents'] && $verification['engine']['file_contents']) {
      $response = new Response();
      $response->setContent($verification['file_contents']);
      return $response;
    }
    else {
      $build = [];
      $build['#title'] = $this->t('Verification page');
      $build['#markup'] = $this->t('This is a verification page for the !title search engine.', [
        '!title' => $verification['engine']['name'],
      ]);

      return $build;
    }
  }

}
