<?php

namespace Drupal\dcat\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group dcat
 */
class AgentUITest extends WebTestBase {

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
    $user = $this->drupalCreateUser(['access agent overview']);
    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_agent.collection'));
    $this->assertResponse(200);
  }

  /**
   * Test the agent add form.
   */
  public function testAddForm() {
    $user = $this->drupalCreateUser([
      'add agent entities',
      'view published agent entities'
    ]);
    $name = $this->randomMachineName();
    $edit = [
      'external_id[0][value]' => 'http://example.com/agent',
      'name[0][value]' => $name,
      'type[0][value]' => $this->randomString(),
    ];

    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_agent.add_form'));

    // Required fields.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_agent.add_form'), [], t('Save'));
    $this->assertText('Agent IRI field is required.');
    $this->assertText('Name field is required.');

    // Adding and viewing entity.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_agent.add_form'), $edit, t('Save'));
    $this->assertText('Created the ' . $name . ' Agent.');
  }

}
