<?php

namespace Drupal\domain_path_redirect;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\domain_path_redirect\Entity\DomainPathRedirect;
use Drupal\redirect\Exception\RedirectLoopException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Loads redirects records.
 */
class DomainPathRedirectRepository {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * An array of found redirect IDs to avoid recursion.
   *
   * @var array
   */
  protected $foundRedirects = [];

  /**
   * DomainPathRedirectRequestSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The default database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->config = $config_factory->get('redirect.settings');
    $this->requestStack = $request_stack;
  }

  /**
   * Gets a redirect for given path, query and language.
   *
   * @param string $source_path
   *   The redirect source path.
   * @param string $domain_id
   *   The ID of the domain.
   * @param array $query
   *   The redirect source path query.
   * @param string $language
   *   The language for which is the redirect.
   *
   * @return \Drupal\domain_path_redirect\Entity\DomainPathRedirect
   *   The matched redirect entity.
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   */
  public function findMatchingRedirect($source_path, $domain_id, array $query = [], $language = Language::LANGCODE_NOT_SPECIFIED) {
    $hashes = [
      DomainPathRedirect::generateDomainHash($source_path, $domain_id, $query, $language),
    ];
    if ($language != Language::LANGCODE_NOT_SPECIFIED) {
      $hashes[] = DomainPathRedirect::generateDomainHash($source_path, $domain_id, $query, Language::LANGCODE_NOT_SPECIFIED);
    }

    // Add a hash without the query string if using passthrough querystrings.
    if (!empty($query) && $this->config->get('passthrough_querystring')) {
      $hashes[] = DomainPathRedirect::generateDomainHash($source_path, $domain_id, [], $language);
      if ($language != Language::LANGCODE_NOT_SPECIFIED) {
        $hashes[] = DomainPathRedirect::generateDomainHash($source_path, $domain_id, [], Language::LANGCODE_NOT_SPECIFIED);
      }
    }

    // Load redirects by hash. A direct query is used to improve performance.
    $rid = $this->connection->query('SELECT rid FROM {domain_path_redirect} WHERE hash IN (:hashes[]) ORDER BY LENGTH(redirect_source__query) DESC', [':hashes[]' => $hashes])->fetchField();

    if (!empty($rid)) {
      // Check if this is a loop.
      if (in_array($rid, $this->foundRedirects)) {
        throw new RedirectLoopException('/' . $source_path, $rid);
      }
      $this->foundRedirects[] = $rid;

      $redirect = $this->load($rid);

      // Find chained redirects.
      if ($recursive = $this->findByRedirect($redirect, $domain_id, $language)) {
        // Reset found redirects.
        $this->foundRedirects = [];
        return $recursive;
      }

      return $redirect;
    }

    return NULL;
  }

  /**
   * Helper function to find recursive redirects.
   *
   * @param \Drupal\domain_path_redirect\Entity\DomainPathRedirect $redirect
   *   The redirect object.
   * @param string $domain_id
   *   The ID of the domain.
   * @param string $language
   *   The language to use.
   *
   * @return \Drupal\domain_path_redirect\Entity\DomainPathRedirect
   *   The matched redirect entity.
   */
  protected function findByRedirect(DomainPathRedirect $redirect, $domain_id, $language) {
    $uri = $redirect->getRedirectUrl();
    $baseUrl = $this->requestStack->getCurrentRequest()->getBaseUrl();
    $path = ltrim(substr($uri->toString(), strlen($baseUrl)), '/');
    $query = $uri->getOption('query') ?: [];
    return $this->findMatchingRedirect($path, $domain_id, $query, $language);
  }

  /**
   * Finds redirects based on the source path.
   *
   * @param string $source_path
   *   The redirect source path (without the query).
   *
   * @return \Drupal\domain_path_redirect\Entity\DomainPathRedirect[]
   *   Array of redirect entities.
   */
  public function findBySourcePath($source_path) {
    $ids = $this->entityTypeManager->getStorage('domain_path_redirect')->getQuery()
      ->condition('redirect_source.path', $source_path, 'LIKE')
      ->execute();
    return $this->entityTypeManager->getStorage('domain_path_redirect')->loadMultiple($ids);
  }

  /**
   * Finds redirects based on the destination URI.
   *
   * @param string[] $destination_uri
   *   List of destination URIs, for example ['internal:/node/123'].
   *
   * @return \Drupal\domain_path_redirect\Entity\DomainPathRedirect[]
   *   Array of redirect entities.
   */
  public function findByDestinationUri(array $destination_uri) {
    $storage = $this->entityTypeManager->getStorage('domain_path_redirect');
    $ids = $storage->getQuery()
      ->condition('redirect_redirect.uri', $destination_uri, 'IN')
      ->execute();
    return $storage->loadMultiple($ids);
  }

  /**
   * Load redirect entity by id.
   *
   * @param int $redirect_id
   *   The redirect id.
   *
   * @return \Drupal\domain_path_redirect\Entity\DomainPathRedirect
   *   The redirect entity.
   */
  public function load($redirect_id) {
    return $this->entityTypeManager->getStorage('domain_path_redirect')->load($redirect_id);
  }

  /**
   * Loads multiple redirect entities.
   *
   * @param array $redirect_ids
   *   Redirect ids to load.
   *
   * @return \Drupal\domain_path_redirect\Entity\DomainPathRedirect[]
   *   List of redirect entities.
   */
  public function loadMultiple(array $redirect_ids = NULL) {
    return $this->entityTypeManager->getStorage('domain_path_redirect')->loadMultiple($redirect_ids);
  }

}
