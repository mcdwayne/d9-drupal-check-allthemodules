<?php

namespace Drupal\dcat\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group dcat
 */
class DistributionUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dcat'];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests that the overview page loads with a 200 response.
   */
  public function testOverview() {
    $user = $this->drupalCreateUser(['access distribution overview']);
    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_distribution.collection'));
    $this->assertResponse(200);
  }

  /**
   * Test the distribution add form.
   */
  public function testAddForm() {
    $user = $this->drupalCreateUser([
      'add distribution entities',
      'view published distribution entities'
    ]);
    $name = $this->randomMachineName();
    // ToDo: We could add some more non-required values.
    $edit = [
      'external_id[0][value]' => 'http://example.com/distribution.csv',
      'access_url[0][uri]' => 'http://example.com/distribution.csv',
      'name[0][value]' => $name,
      'description[0][value]' => $this->randomString(128),
    ];

    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_distribution.add_form'));

    // Required fields.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_distribution.add_form'), [], t('Save'));
    $this->assertText('Distribution IRI field is required.');
    $this->assertText('Access URL field is required.');

    // Adding and viewing entity.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_distribution.add_form'), $edit, t('Save'));
    $this->assertText('Created the ' . $name . ' Distribution.');
    $this->assertText('http://example.com/distribution.csv');
  }

}
