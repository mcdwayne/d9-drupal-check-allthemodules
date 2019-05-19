<?php

namespace Drupal\Tests\system_tags\Functional;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\system_tags\Config\SystemTagDefinitions;
use Drupal\Tests\BrowserTestBase;

/**
 * Class DefaultSystemTagsTest.
 *
 * @package \Drupal\system_tags\Tests
 *
 * @group system_tags
 */
class DefaultSystemTagsTest extends BrowserTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'system_tags'];

  /**
   * The user.
   *
   * @var \Drupal\user\UserInterface
   */
  private $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'administer system tags',
      'administer nodes',
      'access content',
      'administer content types',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Test the correct installation of the module & the associated default Tags.
   */
  public function testDefaultSystemTagsExists() {
    $this->drupalGet('/admin/structure/system_tags');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(SystemTagDefinitions::TAG_HOMEPAGE);
    $this->assertSession()->pageTextContains(SystemTagDefinitions::TAG_ACCESS_DENIED);
    $this->assertSession()->pageTextContains(SystemTagDefinitions::TAG_PAGE_NOT_FOUND);
  }

  /**
   * Test front page via System Tag 'homepage'.
   */
  public function testHomepageSystemTagNode() {
    $this->drupalLogout();
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Log in');

    $this->drupalLogin($this->user);
    // Create content type 'Page'.
    $this->drupalCreateContentType(['type' => 'page']);
    // Create reference field.
    $this->createEntityReferenceField('node', 'page', 'field_system_tags', 'System tags', 'system_tag');
    // Create node.
    $values = [
      'title' => 'Hello, world!',
      'type' => 'page',
    ];
    $values['field_system_tags'][]['target_id'] = SystemTagDefinitions::TAG_HOMEPAGE;
    $node = $this->drupalCreateNode($values);
    drupal_flush_all_caches();
    $this->drupalLogout();

    // Visit front and test if the title and body of the node are corresponding.
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($node->getTitle());
    $this->assertSession()->pageTextContains($node->get('body')->value);
  }

}
