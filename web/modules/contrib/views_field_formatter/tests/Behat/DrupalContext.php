<?php

declare(strict_types = 1);

namespace Drupal\Tests\views_field_formatter\Behat;

use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Class DrupalContext.
 */
class DrupalContext extends RawDrupalContext {

  /**
   * Installs the test module before executing any tests.
   *
   * @param \Behat\Testwork\Hook\Scope\BeforeSuiteScope $scope
   *   The hook scope.
   *
   * @BeforeSuite
   */
  public static function installTestModule(BeforeSuiteScope $scope): void {
    \Drupal::service('module_installer')->install(['views_field_formatter_test']);
  }

  /**
   * Uninstalls the test module after all the tests have run.
   *
   * @param \Behat\Testwork\Hook\Scope\AfterSuiteScope $scope
   *   The hook scope.
   *
   * @AfterSuite
   */
  public static function uninstallTestModule(AfterSuiteScope $scope): void {
    \Drupal::service('module_installer')->uninstall(['views_field_formatter_test']);
  }

}
