<?php
namespace Drupal\node_expire\Tests;

/**
 * Tests for the administrative UI.
 * 
 * @group node_expire
 */
class NodeExpireAdminTestCase extends \Drupal\simpletest\WebTestBase {

  protected $profile = 'standard';

  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return [
      'name' => 'Node Expire UI',
      'description' => 'Tests for the administrative UI.',
      'group' => 'Node Expire',
    ];
  }

  /**
   * Overrides setUp().
   */
  public function setUp() {
    parent::setUp(['rules', 'node_expire']);

    // Create test user.
    $this->admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer site configuration',
      'administer modules',
    ]);
    $this->drupalLogin($this->admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomName(8)) . '_test';
    $type = $this->drupalCreateContentType([
      'name' => $type_name,
      'type' => $type_name,
    ]);
    $this->type = $type->type;
    // Store a valid URL name, with hyphens instead of underscores.
    $this->hyphen_type = str_replace('_', '-', $this->type);
  }

  /**
   * Makes test.
   */
  public function testFieldAdminHandler() {
    $test_path = 'admin/config/workflow/node_expire/settings';
    $this->drupalGet($test_path);
    $this->assertText(t('Allow expire date in the past'), 'The text "Allow expire date in the past" appears on the Node expire settings page.');
  }

}
