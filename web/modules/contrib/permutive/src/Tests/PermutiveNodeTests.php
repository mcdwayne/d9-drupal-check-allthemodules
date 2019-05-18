<?php

namespace Drupal\permutive\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Permutive tags.
 *
 * @group Permutive
 */
class PermutiveNodeTests extends WebTestBase {

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('permutive');

  /**
   * A user with the 'Administer Permutive' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer permutive',
      'create page content',
    ]);
  }

  /**
   * Test permutive on nodes.
   */
  function testPermutiveNode() {
    $this->drupalLogin($this->adminUser);

    // Configure module.
    $uuid_service = \Drupal::service('uuid');
    $api_key = $uuid_service->generate();
    $project_id = $uuid_service->generate();
    $edit = [
      'api_key' => $api_key,
      'project_id' => $project_id,
    ];
    $this->drupalPostForm('admin/config/system/permutive', $edit, t('Save configuration'));

    // Create a basic page.
    $title = $this->randomGenerator->word(10);
    $body = $this->randomGenerator->sentences(10);
    $edit = [
      'title[0][value]' => $title,
      'body[0][value]' => $body,
      'body[0][format]' => 'basic_html',
    ];
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Check Permutive tags.
    $this->assertRaw('window.permutive=e,e.q=[]');
    $this->assertRaw('document,window.permutive');
    $this->assertRaw($api_key);
    $this->assertRaw($project_id);
    $this->assertRaw('permutive.addon("web", {"page":{"publisher":{"name":"Drupal","type":"page"}');
    $this->assertRaw('"content":{"headline":"' . $title . '","description":"' . $body . '"}}}');
    $this->assertRaw('<script src="https://cdn.permutive.com/');

    // Check homepage.
    $this->drupalGet('node');
    $this->assertNoRaw('document,window.permutive');
  }

}
