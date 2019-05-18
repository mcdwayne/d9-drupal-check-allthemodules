<?php

namespace Drupal\Tests\radioactivity\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\radioactivity\Functional\RadioActivityFunctionTestTrait;

/**
 * Base for Radioactivity functional JavaScript tests.
 */
abstract class RadioactivityFunctionalJavascriptTestBase extends JavascriptTestBase {

  use RadioActivityFunctionTestTrait;

  /**
   * An admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The entity that holds the energy field(s).
   *
   * @var \Drupal\core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'radioactivity',
    'node',
    'field',
    'field_ui',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer entity_test fields',
      'administer entity_test form display',
      'administer entity_test display',
      'administer entity_test content',
      'view test entity',
    ]);
    $this->drupalLogin($this->adminUser);

    // Set default entity type and bundle.
    $this->entityType = 'entity_type';
    $this->entityBundle = 'entity_type';
  }

}
