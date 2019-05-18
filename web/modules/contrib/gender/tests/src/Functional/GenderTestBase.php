<?php

namespace Drupal\Tests\gender\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for testing the gender field.
 *
 * @group gender
 */
abstract class GenderTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_ui',
    'gender',
    'gender_test',
    'node',
    'user',
    'help',
  ];

  /**
   * The list of genders to use in testing.
   *
   * @var array
   */
  protected $genderList = [
    'non-binary',
    'intergender',
    'genderless',
  ];

  /**
   * The node object to use in testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The user object to use in testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and log in the user.
    $this->user = $this->createUser([
      'administer content types',
      'administer nodes',
      'edit any gender_test content',
      'access content',
      'administer node fields',
    ]);
    $this->drupalLogin($this->user);
    $node_settings = [
      'type'         => 'gender_test',
      'field_gender' => [],
      'uid'          => $this->user->id(),
    ];
    foreach ($this->genderList as $gender) {
      $node_settings['field_gender'][] = [
        'value' => $gender,
      ];
    }
    $this->node = $this->createNode($node_settings);
    // Assert that the node was created properly and has the gender field.
    $this->assertNotEmpty($this->node);
    $this->assertTrue($this->node->hasField('field_gender'));
  }

}
