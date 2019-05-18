<?php

namespace Drupal\domain_traversal\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain\Entity\Domain;
use Drupal\domain_traversal\DomainTraversalInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DomainTraversal extends ControllerBase {

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The domains traversal service.
   *
   * @var \Drupal\domain_traversal\DomainTraversalInterface
   */
  protected $domain_traversal;

  /**
   * The domains negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domain_negotiator;

  // @todo convert to config
  protected $secret_timeout = 30;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, DomainTraversalInterface $domain_traversal, DomainNegotiatorInterface $domain_negotiator) {
    $this->database = $database;
    $this->domain_traversal = $domain_traversal;
    $this->domain_negotiator = $domain_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('domain_traversal'),
      $container->get('domain.negotiator')
    );
  }

  /**
   * Redirects the user to the domain with a secret-key to login.
   *
   * @param \Drupal\domain\Entity\Domain $domain
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   */
  public function traverse(Domain $domain) {
    $account = User::load(\Drupal::service('current_user')->id());
    $timestamp = \Drupal::time()->getRequestTime();
    $secret = $this->secretKey($account, $domain->id(), $timestamp);

    $params = [
      'uid' => $account->id(),
      'domain' => $domain->id(),
      'timestamp' => $timestamp,
      'secret' => $secret,
    ];

    $this->database->insert('domain_traversal')->fields($params)->execute();

    $options = [
      'base_url' => trim($domain->getPath(), '/'),
      'absolute' => TRUE,
    ];
    $url = Url::fromRoute('domain_traversal.login', $params, $options);

    return new TrustedRedirectResponse($url->toString(TRUE)->getGeneratedUrl());
  }

  /**
   * Checks if the user has more than 1 domain to traverse to.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function domainTraversalAccess(AccountInterface $account) {
    $loaded_account = User::load($account->id());
    if ($this->domain_traversal->accountMayTraverseAllDomains($loaded_account)) {
      return AccessResult::allowed();
    }

    $domain_ids = $this->domain_traversal->getAccountTraversableDomainIds($loaded_account);
    return AccessResult::allowedIf(count($domain_ids) > 1);
  }

  /**
   * Checks if the user may access the traverse callback.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\domain\Entity\Domain $domain
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function traverseAccess(AccountInterface $account, Domain $domain) {
    // You can't "traverse" to the current domain.
    if ($domain->id() == $this->domain_negotiator->getActiveId()) {
      return AccessResult::forbidden();
    }

    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    if ($account->hasPermission('traverse all domains')) {
      return AccessResult::allowed();
    }

    return AccessResult::allowedIfHasPermission($account, 'traverse domains')
      ->andIf(AccessResult::allowedIf($this->domain_traversal->accountMayTraverseDomain(User::load($account->id()), $domain)));
  }

  /**
   * Tries to login the user with the provided secret.
   *
   * @param \Drupal\domain\Entity\Domain $domain
   * @param int $uid
   * @param int $timestamp
   * @param string $secret
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function login(Domain $domain, $uid, $timestamp, $secret) {
    $current_user = User::load($this->currentUser()->id());
    $request_time = \Drupal::time()->getRequestTime();

    if (!$current_user->isAnonymous()) {
      // Someone is already logged in.
      if ($current_user->id() == $uid) {
        // It me!
        $this->cleanupSecret($domain, $timestamp, $secret, $request_time);
        drupal_set_message($this->t('You are now logged in.'));
      }
      else {
        // Someone else is already logged in.
        drupal_set_message($this->t('Another user is already logged into the site on this computer. Please <a href=":logout">log out</a> and try using the link again.', array(
          ':logout' => $this->getUrlGenerator()->generateFromRoute('user.logout'),
        )), 'warning');
      }

      return $this->redirect('<front>');
    }

    $account = User::load($uid);
    user_login_finalize($account);
    \Drupal::logger('domain-traversal')->notice('User %name used one-time domain-traversal link at time %timestamp.', array(
      '%name' => $account->getDisplayName(),
      '%timestamp' => $timestamp,
    ));

    drupal_set_message($this->t('You are now logged in.'));

    return $this->redirect('<front>');
  }

  /**
   * Grants access to login the user with the provided secret.
   *
   * @param \Drupal\domain\Entity\Domain $domain
   * @param int $uid
   * @param int $timestamp
   * @param string $secret
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function loginAccess(Domain $domain, $uid, $timestamp, $secret) {
    $request_time = \Drupal::time()->getRequestTime();

    $invalid_link_message = $this->t('You have tried to use an invalid one-time-domain-traversal link.');

    if ((int) $timestamp < $request_time - $this->secret_timeout) {
      return $this->accessDenied($this->t('You have tried to use a one-time-domain-traversal link that has expired.'));
    }

    $account_id = $this->database->select('domain_traversal', 'dt')
      ->fields('dt', array('uid'))
      ->range(0, 1)
      ->condition('domain', $domain->id())
      ->condition('timestamp', $timestamp)
      ->condition('secret', $secret)
      ->execute()
      ->fetchField();

    $this->cleanupSecret($domain, $timestamp, $secret, $request_time);

    if ($account_id != $uid) {
      return $this->accessDenied($invalid_link_message);
    }

    $account = User::load($uid);
    if (!$account->isActive()) {
      return $this->accessDenied($invalid_link_message);
    }

    if (!$this->domain_traversal->accountMayTraverseDomain($account, $domain)) {
      return $this->accessDenied($this->t('You do not have access to the requested domain.'));
    }

    $secret_key = $this->secretKey($account, $domain->id(), $timestamp);
    if (!Crypt::hashEquals($secret, $secret_key)) {
      return $this->accessDenied($invalid_link_message);
    }

    return AccessResult::allowed();
  }

  /**
   * Shows an error message and throws access denied exception.
   *
   * @param string $message
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  protected function accessDenied($message) {
    drupal_set_message($message, 'error');

    return AccessResult::forbidden();
  }

  /**
   * Create a secret string.
   *
   * @param \Drupal\user\Entity\User $account
   * @param string $domain_id
   * @param int $timestamp
   *
   * @return string
   */
  protected function secretKey($account, $domain_id, $timestamp) {
    return Crypt::hmacBase64($timestamp . $account->id(), Settings::getHashSalt() . $domain_id . $account->getPassword());
  }

  /**
   * Cleanup the used secret and other expired secrets.
   *
   * @param \Drupal\domain\Entity\Domain $domain
   * @param int $timestamp
   * @param string $secret
   * @param int $request_time
   */
  protected function cleanupSecret(Domain $domain, $timestamp, $secret, $request_time) {
    $and = new Condition('AND');
    $and->condition('domain', $domain->id())
      ->condition('timestamp', $timestamp)
      ->condition('secret', $secret);

    $or = new Condition('OR');
    $or->condition($and)
      ->condition('timestamp', $request_time - $this->secret_timeout, '<');

    $this->database->delete('domain_traversal')
      ->condition($or)
      ->execute();
  }

}
