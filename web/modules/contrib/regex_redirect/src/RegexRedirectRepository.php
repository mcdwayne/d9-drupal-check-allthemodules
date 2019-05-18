<?php

namespace Drupal\regex_redirect;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\regex_redirect\Entity\RegexRedirect;
use Drupal\redirect\Exception\RedirectLoopException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RegexRedirectRepository, based on the redirect module repository.
 *
 * @package Drupal\regex_redirect
 */
class RegexRedirectRepository {

  /**
   * Used to retrieve regex redirects.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The redirect source path.
   *
   * @var string
   */
  protected $sourcePath;

  /**
   * An array of matching redirect IDs to avoid recursion.
   *
   * @var array
   */
  protected $matchingRedirectIds = [];

  /**
   * A reference to the named capturing group retrieved from the regex.
   *
   * @var array
   */
  protected $namedGroupReference = [];

  /**
   * Constructs a RegexRedirectRequestSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->requestStack = $request_stack;
  }

  /**
   * Gets a redirect for given path, query and language.
   *
   * The redirect contrib module uses configuration settings for queries,
   * this contrib module will always allow queries. Unlike the redirect
   * module, this function will not be basing queries on hashes since that
   * does not work for regex patterns.
   *
   * @param string $source_path
   *   The redirect source path.
   * @param string $language
   *   The language for which is the redirect.
   *
   * @return \Drupal\regex_redirect\Entity\RegexRedirect|null
   *   The matched redirect entity.
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function findMatchingRedirect($source_path, $language = Language::LANGCODE_NOT_SPECIFIED) {
    $this->sourcePath = $source_path;

    // A regex match should only be retrieved when the current page
    // requires a redirect.
    if ($this->requiresRedirect() === FALSE) {
      return NULL;
    }

    /** @var \Drupal\regex_redirect\Entity\RegexRedirect|null $redirect */
    $redirect = $this->retrieveMatchingRedirect();
    if ($redirect === NULL) {
      return NULL;
    }

    $this->replaceRegexWithActualUrl($redirect);

    // Find and return chained redirects recursively.
    if ($recursive_redirect = $this->findRedirectRecursively($redirect, $language)) {
      // Reset matching redirects.
      $this->matchingRedirectIds = [];
      return $recursive_redirect;
    }

    return $redirect;
  }

  /**
   * Finds redirects based on the source path.
   *
   * @param string $path
   *   The parsed source path.
   *
   * @return \Drupal\regex_redirect\Entity\RegexRedirect[]|null
   *   Array of redirect entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function findBySourcePath($path) {
    $ids = $this->entityTypeManager->getStorage('regex_redirect')->getQuery()
      ->condition('regex_redirect_source.path', $path, 'LIKE')
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    /** @var \Drupal\regex_redirect\Entity\RegexRedirect[] $regex_redirects */
    $regex_redirects = $this->loadMultiple($ids);
    return $regex_redirects;
  }

  /**
   * Load redirect entity by id.
   *
   * @param int $redirect_id
   *   The redirect id.
   *
   * @return \Drupal\regex_redirect\Entity\RegexRedirect|null
   *   The regex redirect entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function load($redirect_id) {
    /** @var \Drupal\regex_redirect\Entity\RegexRedirect $regex_redirect */
    $regex_redirect = $this->entityTypeManager->getStorage('regex_redirect')->load($redirect_id);
    return $regex_redirect;
  }

  /**
   * Loads multiple redirect entities.
   *
   * @param array $redirect_ids
   *   Redirect ids to load.
   *
   * @return \Drupal\regex_redirect\Entity\RegexRedirect[]
   *   List of redirect entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function loadMultiple(array $redirect_ids = NULL) {
    /** @var \Drupal\regex_redirect\Entity\RegexRedirect[] $regex_redirects */
    $regex_redirects = $this->entityTypeManager->getStorage('regex_redirect')->loadMultiple($redirect_ids);
    return $regex_redirects;
  }

  /**
   * Helper function to find regex redirects.
   *
   * @param \Drupal\regex_redirect\Entity\RegexRedirect $redirect
   *   The redirect object.
   * @param string $language
   *   The language to use.
   *
   * @return \Drupal\regex_redirect\Entity\RegexRedirect|null
   *   The matched redirect entity.
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function findRedirectRecursively(RegexRedirect $redirect, $language) {
    $uri = $redirect->getRedirectUrl();
    $base_url = $this->requestStack->getCurrentRequest()->getBaseUrl();
    $path = ltrim(substr($uri->toString(), strlen($base_url)), '/');

    // Named groups should be replaced in order to allow recursive redirects.
    foreach ($this->namedGroupReference as $key => $value) {
      $path = ltrim(preg_replace('/<' . $key . '>/', $value, $path), '/');
    }

    return $this->findMatchingRedirect($path, $language);
  }

  /**
   * Checks whether the current page needs to be redirected.
   *
   * @return bool
   *   Requires redirect.
   */
  protected function requiresRedirect() {
    // There is no need to query all pages, therefore we skip admin pages
    // and existing nodes.
    if (strpos($this->sourcePath, 'node/') === 0) {
      return FALSE;
    }
    elseif (strpos($this->sourcePath, 'admin/') === 0) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Retrieve the redirect matching the source path.
   *
   * @return \Drupal\regex_redirect\Entity\RegexRedirect|null
   *   The regex redirect.
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function retrieveMatchingRedirect() {
    // Load all regex redirects by source path. A direct query is used to
    // improve performance.
    /** @var \Drupal\regex_redirect\Entity\RegexRedirect[] $regex_redirects */
    $regex_redirects = $this->connection->query('SELECT rid, regex_redirect_source FROM {regex_redirect}')->fetchAll();
    if (empty($regex_redirects)) {
      return NULL;
    }

    // For each database entry, check whether there is a match with the source
    // path. There should not be too many entries in the database to avoid
    // performance issues. Unfortunately, checking via hashes is impossible
    // in combination with regular expressions.
    foreach ($regex_redirects as $regex_redirect) {
      $path = $regex_redirect->regex_redirect_source;
      $delimiter = '/';
      $regex_path = $delimiter . $path . $delimiter;

      // Nothing needs to be done except when a match has been found.
      // There will only be a single match due to validation on entity create.
      if (!$this->matchSourceWithRegexPattern($regex_path)) {
        continue;
      }

      $redirect_id = $regex_redirect->rid;
      $this->dealWithRedirectLoop($redirect_id);
      return $this->load($redirect_id);
    }

    return NULL;
  }

  /**
   * Checks whether we might be stuck in a redirect loop.
   *
   * @param int $redirect_id
   *   The redirect id.
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   */
  protected function dealWithRedirectLoop($redirect_id) {
    // Check if this is a loop.
    if (in_array($redirect_id, $this->matchingRedirectIds)) {
      throw new RedirectLoopException('/' . $this->sourcePath, $redirect_id);
    }

    $this->matchingRedirectIds[] = $redirect_id;
  }

  /**
   * Match the source url with the regex pattern.
   *
   * @param string $regex_path
   *   The regex pattern.
   *
   * @return bool
   *   Whether a match has been found.
   */
  protected function matchSourceWithRegexPattern($regex_path) {
    // Match the source url with the regex pattern of the database entry
    // and return the matches. The entire source path is matched via regex.
    // The u is needed to set a multibyte utf-8 preg_match regexp.
    // Some legacy urls have multibyte utf-8 characters in the url
    // that need to be interpreted and redirected.
    $regex_match = preg_match($regex_path . 'u', $this->sourcePath, $matches);

    // Do not run the query if there is no regex match.
    if ($regex_match !== 1) {
      return FALSE;
    }

    // Retrieve the valid named captures.
    foreach ($matches as $key => $match) {
      if (is_numeric($key)) {
        continue;
      }
      $this->namedGroupReference[$key] = $match;
    }

    return TRUE;
  }

  /**
   * Replace the regex in the destination url with actual values.
   *
   * @param \Drupal\regex_redirect\Entity\RegexRedirect $redirect
   *   The redirect object.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function replaceRegexWithActualUrl(RegexRedirect $redirect) {
    // Replaced all the named capture names in the redirect with the
    // matched values from the source.
    $redirect_regex_url = str_replace('base:', '', $redirect->getRedirectUrl()->toUriString());
    foreach ($this->namedGroupReference as $key => $value) {
      $pattern = '/<' . $key . '>/';
      $redirect_regex_url = preg_replace($pattern, $value, $redirect_regex_url);
    }

    // Set the redirect with the specific url.
    $redirect->setRedirect($redirect_regex_url);
  }

}
