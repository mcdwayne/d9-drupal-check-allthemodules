<?php

declare(strict_types=1);

namespace Drupal\testtools;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\testtools\Assert\AssertCallable;
use Drupal\testtools\Assert\AssertInterface;
use Drupal\testtools\Assert\CombinedAssertAnd;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * Adds permission testing to a test class.
 */
trait PermissionTestTrait {

  /**
   * Returns the permission matrix object.
   *
   * @return PermissionMatrixInterface
   */
  abstract protected function getPermissionMatrix(): PermissionMatrixInterface;

  /**
   * Asserts that $condition is true.
   *
   * @param bool $condition
   * @param string $message
   */
  abstract public static function assertTrue($condition, $message = '');

  /**
   * Retrieves a Drupal path or an absolute path.
   *
   * @param string|\Drupal\Core\Url $path
   * @param array $options
   * @param array $headers
   *
   * @return string
   *
   * @see \Drupal\Tests\BrowserTestBase::drupalGet()
   * @see \Drupal\Tests\BrowserTestBase::getHttpClient()
   */
  abstract protected function drupalGet($path, array $options = [], array $headers = []);

  /**
   * Returns a Mink session.
   *
   * @see \Drupal\Tests\BrowserTestBase::getSession()
   *
   * @param null $name
   *   Session name.
   *
   * @return \Behat\Mink\Session
   *   The active Mink session object.
   */
  abstract public function getSession($name = NULL);

  /**
   * Logs in a user using the Mink controlled browser.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object representing the user to log in.
   *
   * @see drupalCreateUser()
   * @see \Drupal\Tests\UiHelperTrait::drupalLogin()
   */
  abstract protected function drupalLogin(AccountInterface $account);

  /**
   * Logs a user out of the Mink controlled browser and confirms.
   *
   * @see \Drupal\Tests\UiHelperTrait::drupalLogout()
   */
  abstract protected function drupalLogout();

  /**
   * Tests the permission matrix.
   */
  public function testPermissions(): void {
    foreach ($this->getPermissionMatrix() as $result) {
      /** @var \Drupal\testtools\PermissionCheckResult $result */
      $name = $result->getName();
      $accountName = $result->getAccount()->getAccountName() ?: 'anonymous';
      self::assertEquals(
        $result->getExpected(),
        $result->getActual(),
        "Access result for {$accountName}" . ($name ? " for {$name}" : "")
      );
    }
  }

  /**
   * Creates an assert for entity access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string ...$ops
   *   Entity operations.
   *
   * @return callable
   *   The created assert.
   */
  protected function assertEntityAccess(EntityInterface $entity, string ...$ops): callable {
    if (in_array('edit', $ops)) {
      throw new UnexpectedValueException('Did you mean update?');
    }

    $asserts = array_map(function (string $op) use ($entity): AssertInterface {
      return new AssertCallable("{$entity->getEntityTypeId()} ({$entity->label()}) {$op} access", function (AccountInterface $account) use ($op, $entity): bool {
        return $entity->access($op, $account);
      });
    }, $ops);

    return count($asserts) === 1 ?
      reset($asserts) : new CombinedAssertAnd(...$asserts);
  }

  /**
   * Creates an assert for entity create access.
   *
   * @param string $entity_type
   *   Entity type name.
   * @param string $bundle
   *   Bundle name.
   *
   * @return callable
   *   The created assert.
   */
  protected function assertEntityCreateAccess(string $entity_type, string $bundle): callable {
    return new AssertCallable("create {$entity_type} of {$bundle} access", function (AccountInterface $account) use ($entity_type, $bundle): bool {
      return \Drupal::entityTypeManager()->getAccessControlHandler($entity_type)->createAccess($bundle, $account, [], FALSE);
    });
  }

  /**
   * Creates an assert for entity field access.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A fieldable entity.
   * @param string $field_name
   *   Machine name of the field.
   * @param string ...$ops
   *   Entity field operations.
   *
   * @return callable
   *   The created assert.
   */
  protected function assertEntityFieldAccess(FieldableEntityInterface $entity, string $field_name, string ...$ops): callable {
    if (in_array('update', $ops)) {
      throw new UnexpectedValueException('Did you mean edit?');
    }

    $asserts = array_map(function (string $op) use ($entity, $field_name): AssertInterface {
      return new AssertCallable("{$entity->getEntityTypeId()}->{$field_name} ({$entity->label()}) {$op} access", function (AccountInterface $account) use ($op, $entity, $field_name): bool {
        return $entity->get($field_name)->access($op, $account, FALSE);
      });
    }, $ops);

    return count($asserts) === 1 ?
      reset($asserts) : new CombinedAssertAnd(...$asserts);
  }

  /**
   * Creates an assert for route access.
   *
   * @param string $route_name
   *   Route name.
   * @param array $parameters
   *   Route parameters.
   *
   * @return callable
   *   The created assert.
   */
  protected function assertRouteAccess(string $route_name, array $parameters): callable {
    $url = Url::fromRoute($route_name, $parameters);
    return new AssertCallable("route access of {$url->toString()}", function (AccountInterface $account) use ($route_name, $parameters): bool {
      /** @var \Drupal\Core\Access\AccessManager $manager */
      $manager = \Drupal::service('access_manager');
      return $manager->checkNamedRoute($route_name, $parameters, $account);
    });
  }

  /**
   * Creates an assert that loads a page.
   *
   * @param string $route_name
   *   Route name for the page.
   * @param array $parameters
   *   Route parameters for the page.
   *
   * @return callable
   *   The created assert.
   */
  protected function assertPageAccessible(string $route_name, array $parameters): callable {
    $url = Url::fromRoute($route_name, $parameters);
    return new AssertCallable("page access of {$url->toString()}", function (AccountInterface $account) use ($url): bool {
      return $this->withAccount($account, function () use ($url): int {
        $this->drupalGet($url);
        return $this->getSession()->getStatusCode();
      }) !== Response::HTTP_FORBIDDEN;
    });
  }

  /**
   * Creates an assert that checks if a link exists on a page.
   *
   * @param string $route_name
   *   Route name for the page.
   * @param array $parameters
   *   Route parameters for the page.
   * @param string $link
   *   Label of the link to check.
   *
   * @return callable
   *   The created assert.
   */
  protected function assertLinkExistsOnPage(string $route_name, array $parameters, string $link): callable {
    $url = Url::fromRoute($route_name, $parameters);
    return new AssertCallable("link {$link} exists on {$url->toString()}", function (AccountInterface $account) use ($url, $link): bool {
      return $this->withAccount($account, function () use ($url, $link): int {
        $this->drupalGet($url);
        if ($this->getSession()->getStatusCode() !== Response::HTTP_FORBIDDEN) {
          $links = $this->getSession()->getPage()->findAll('named', ['link', $link]);
          return count($links);
        }

        return 0;
      }) > 0;
    });
  }

  /**
   * Logs in an account and performs an action with it.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param callable $action
   *   The action taking no arguments and returning an int.
   *
   * @return int
   *   Callable result.
   */
  private function withAccount(AccountInterface $account, callable $action): int {
    try {
      if ($account->id()) {
        $this->drupalLogin($account);
      }
      elseif ($this->getLoggedInUser()) {
        $this->drupalLogout();
      }

      return $action();
    }
    finally {
      if ($this->getLoggedInUser()) {
        $this->drupalLogout();
      }
    }
  }

  /**
   * Returns the logged in user.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The logged in user or null if not logged in.
   *
   * @see https://bugs.php.net/bug.php?id=67979
   */
  private function getLoggedInUser(): ?AccountInterface {
    if (property_exists($this, 'loggedInUser')) {
      return $this->loggedInUser ?: NULL;
    }

    return NULL;
  }

}
