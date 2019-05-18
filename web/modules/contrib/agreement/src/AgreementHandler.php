<?php

namespace Drupal\agreement;

use Drupal\agreement\Entity\Agreement;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;

/**
 * Agreement handler provides methods for looking up agreements.
 */
class AgreementHandler implements AgreementHandlerInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Initialize method.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The path matcher service.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entityTypeManager, PathMatcherInterface $pathMatcher) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->pathMatcher = $pathMatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function agree(Agreement $agreement, AccountProxyInterface $account, $agreed = 1) {
    try {
      /** @var \Drupal\Core\Database\Transaction $transaction */
      $transaction = $this->connection->startTransaction();

      $this->connection->delete('agreement')
        ->condition('uid', $account->id())
        ->condition('type', $agreement->id())
        ->execute();

      $id = $this->connection->insert('agreement')
        ->fields([
          'uid' => $account->id(),
          'type' => $agreement->id(),
          'agreed' => $agreed,
          'sid' => session_id(),
          'agreed_date' => REQUEST_TIME,
        ])
        ->execute();
    }
    catch (DatabaseExceptionWrapper $e) {
      $transaction->rollback();
      return FALSE;
    }
    catch (\Exception $e) {
      $transaction->rollback();
      return FALSE;
    }

    return isset($id);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAgreed(Agreement $agreement, AccountProxyInterface $account) {
    $settings = $agreement->getSettings();

    $query = $this->connection->select('agreement');
    $query
      ->fields('agreement', ['agreed'])
      ->condition('uid', $account->id())
      ->condition('type', $agreement->id())
      ->range(0, 1);

    if ($settings['frequency'] == 0) {
      // Must agree on every login.
      $query->condition('sid', session_id());
    }
    else {
      // Must agree when frequency is set greater than zero (number of days).
      $timestamp = $agreement->getAgreementFrequencyTimestamp();
      if ($timestamp > 0) {
        $query->condition('agreed_date', $agreement->getAgreementFrequencyTimestamp(), '>=');
      }
    }

    $agreed = $query->execute()->fetchField();
    return $agreed !== NULL && $agreed > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function lastAgreed(Agreement $agreement, UserInterface $account) {
    $query = $this->connection->select('agreement');
    $query
      ->fields('agreement', ['agreed_date'])
      ->condition('uid', $account->id())
      ->condition('type', $agreement->id())
      ->range(0, 1);

    $agreed_date = $query->execute()->fetchField();
    return $agreed_date === FALSE || $agreed_date === NULL ? -1 : $agreed_date;
  }

  /**
   * {@inheritdoc}
   */
  public function canAgree(Agreement $agreement, AccountProxyInterface $account) {
    return !$account->hasPermission('bypass agreement') &&
      $agreement->accountHasAgreementRole($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getAgreementByUserAndPath(AccountProxyInterface $account, $path) {
    $agreement_types = $this->entityTypeManager->getStorage('agreement')->loadMultiple();
    $default_exceptions = [
      '/user/logout',
      '/admin/config/people/agreement',
      '/admin/config/people/agreement/*',
      '/admin/config/people/agreement/manage/*',
    ];

    // Get a list of pages to never display agreements on.
    $exceptions = array_reduce($agreement_types, function (&$result, Agreement $item) {
      $result[] = $item->get('path');
      return $result;
    }, $default_exceptions);

    $exception_string = implode("\n", $exceptions);
    if ($this->pathMatcher->matchPath($path, $exception_string)) {
      return FALSE;
    }

    // Reduce the agreement types based on the user role.
    $agreements_with_roles = array_reduce($agreement_types, function (&$result, Agreement $item) use ($account) {
      if ($item->accountHasAgreementRole($account)) {
        $result[] = $item;
      }
      return $result;
    }, []);

    // Try to find an agreement type that matches the path.
    $pathMatcher = $this->pathMatcher;
    $self = $this;
    $info = array_reduce($agreements_with_roles, function (&$result, Agreement $item) use ($account, $path, $pathMatcher, $self) {
      if ($result) {
        // Always returns the first matched agreement.
        return $result;
      }

      $pattern = $item->getVisibilityPages();
      $has_match = $pathMatcher->matchPath($path, $pattern);
      $has_agreed = $self->hasAgreed($item, $account);
      $visibility = (int) $item->getVisibilitySetting();
      if (0 === $visibility && FALSE === $has_match && !$has_agreed) {
        // An agreement exists that matches any page.
        $result = $item;
      }
      elseif (1 === $visibility && $has_match && !$has_agreed) {
        // An agreement exists that matches the current path.
        $result = $item;
      }
      return $result;
    }, FALSE);

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function prefixPath($value) {
    return $value ? '/' . $value : $value;
  }

}
