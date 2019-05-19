<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ScopeInterface;

class ContainerSpy implements ContainerInterface {
  /** @var array */
  private $retrievedServices = [];

  /**
   * {@inheritdoc}
   */
  public function set($id, $service, $scope = self::SCOPE_CONTAINER) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE) {
    $this->retrievedServices[] = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function has($id) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getParameter($name) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function hasParameter($name) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function setParameter($name, $value) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function enterScope($name) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function leaveScope($name) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function addScope(ScopeInterface $scope) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function hasScope($name) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function isScopeActive($name) {
    // Intentionally left empty.
  }

  public function assertNumberOfServicesRetrieved($count) {
    Assert::assertCount($count, $this->retrievedServices);
  }

  public function assertServiceRetrieved($name) {
    Assert::assertContains($name, $this->retrievedServices);
  }
}
