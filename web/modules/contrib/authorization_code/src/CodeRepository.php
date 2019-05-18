<?php

namespace Drupal\authorization_code;

use Drupal\authorization_code\Entity\LoginProcess;
use Drupal\authorization_code\Exceptions\FailedToSaveCodeException;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The code repository class.
 */
class CodeRepository implements ContainerInjectionInterface {

  /**
   * The code storage object.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  private $codeStorage;

  /**
   * The attempts storage object.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  private $attemptsStorage;

  /**
   * The number of seconds after which the code will expire.
   *
   * @var int
   */
  private $secondsToExpire;

  /**
   * The maximum number of times a code can be fetched.
   *
   * @var int
   */
  private $maxFetches;

  /**
   * The password manager service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  private $passwordsManager;

  /**
   * CodeRepository constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $code_storage
   *   The code storage object.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $attempts_storage
   *   The attempts storage object.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The authorization_code configuration object.
   * @param \Drupal\Core\Password\PasswordInterface $passwords_manager
   *   The password manager service.
   */
  public function __construct(KeyValueStoreExpirableInterface $code_storage, KeyValueStoreExpirableInterface $attempts_storage, ImmutableConfig $config, PasswordInterface $passwords_manager) {
    $this->codeStorage = $code_storage;
    $this->attemptsStorage = $attempts_storage;
    $this->secondsToExpire = $config->get('seconds_to_expire');
    $this->maxFetches = $config->get('max_fetches');
    $this->passwordsManager = $passwords_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('authorization_code.code_storage'),
      $container->get('authorization_code.attempts_storage'),
      $container->get('authorization_code.config'),
      $container->get('password')
    );
  }

  /**
   * Saves the code to the storage class.
   *
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $code
   *   The code.
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   */
  public function saveCode(LoginProcess $login_process, UserInterface $user, $code) {
    try {
      $this->codeStorage->setWithExpire(
        $login_process->id() . ':' . $user->uuid(),
        $this->passwordsManager->hash($code),
        $this->secondsToExpire);
    }
    catch (\Exception $e) {
      throw new FailedToSaveCodeException($e);
    }
  }

  /**
   * Deletes an authorization code from the storage class.
   *
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   * @param \Drupal\user\UserInterface $user
   *   The user.
   */
  public function deleteCode(LoginProcess $login_process, UserInterface $user) {
    $this->codeStorage->delete($login_process->id() . ':' . $user->uuid());
  }

  /**
   * Checks if the provided code is valid.
   *
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $code
   *   The code to check.
   *
   * @return bool
   *   TRUE if the provided code valid, FALSE otherwise.
   */
  public function isValidCode(LoginProcess $login_process, UserInterface $user, string $code): bool {
    $hash = $this->codeStorage->get($login_process->id() . ':' . $user->uuid());
    if (!is_string($hash)) {
      return FALSE;
    }
    if (!$this->hasRemainingFetches($login_process, $user, $hash)) {
      return FALSE;
    }

    $this->decreaseRemainingFetches($login_process, $user, $hash);
    return $this->passwordsManager->check($code, $hash);
  }

  /**
   * Does this user-code pair have any remaining fetch attempts?
   *
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $code_hash
   *   The hashed code.
   *
   * @return bool
   *   Does this user-code pair have any remaining fetch attempts?
   */
  private function hasRemainingFetches(LoginProcess $login_process, UserInterface $user, string $code_hash): bool {
    return 0 < $this->attemptsStorage->get($this->attemptsKey($login_process, $user, $code_hash), $this->maxFetches);
  }

  /**
   * Decreases the number of fetch attempts for a user-code pair.
   *
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $code_hash
   *   The hashed code.
   */
  private function decreaseRemainingFetches(LoginProcess $login_process, UserInterface $user, string $code_hash) {
    $attempts = $this->attemptsStorage->get($this->attemptsKey($login_process, $user, $code_hash), $this->maxFetches);
    $this->attemptsStorage->setWithExpire(
      $this->attemptsKey($login_process, $user, $code_hash),
      max(0, $attempts - 1),
      $this->secondsToExpire
    );
  }

  /**
   * Composes a key from the user and hashed code.
   *
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $code_hash
   *   The hashed code.
   *
   * @return string
   *   The storage key.
   */
  private function attemptsKey(LoginProcess $login_process, UserInterface $user, string $code_hash): string {
    return sprintf('%s:%s:%s', $login_process->id(), $user->uuid(), $code_hash);
  }

}
