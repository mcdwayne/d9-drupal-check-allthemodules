<?php

namespace Drupal\Tests\instagram_field\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\config\Tests\SchemaCheckTestTrait;

/**
 * Tests that the field works.
 *
 * @group instagram_field
 */
class InstagramFieldTest extends BrowserTestBase {
  use SchemaCheckTestTrait;
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'instagram_field',
  ];

  /**
   * Use the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin;

  /**
   * Node one.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node1;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Client ID.
   *
   * @var string
   */
  const CLIENTID = '0123456789';

  /**
   * Client secret.
   *
   * @var string
   */
  const CLIENTSECRET = '987654321';

  /**
   * Instagram API initial code.
   *
   * @var string
   */
  const INITIALCODE = 'c0de';

  /**
   * Instagram API access token.
   *
   * @var string
   */
  const ACCESSTOKEN = '9876543210ABCDEF';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    // Module configuration.
    $this->drupalPostForm('/admin/config/services/instagram_field', [
      'clientid' => self::CLIENTID,
      'clientsecret' => self::CLIENTSECRET,
    ], 'Save configuration');

    $this->drupalGet($this->baseUrl . '/_instagram_field_callback?code=' . self::INITIALCODE);
    $this->assertSession()->fieldValueEquals('accesstoken', self::ACCESSTOKEN);

    $this->drupalGet('admin/structure/types/manage/page/fields');
    $this->clickLink('Add field');

    // Field configuration.
    $this->drupalPostForm(NULL, [
      'new_storage_type' => 'instagramfield',
      'label' => 'instagramrecent',
      'field_name' => 'instagramrecent',
    ], 'Save and continue');

    $this->drupalPostForm(NULL, [
      'cardinality' => 'number',
      'cardinality_number' => '1',
    ], 'Save field settings');

    $this->drupalPostForm(NULL, [], 'Save settings');

    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $edit = [
      'fields[field_instagramrecent][type]' => 'instagramfield_default',
      'fields[field_instagramrecent][region]' => 'content',
      'fields[field_instagramrecent][weight]' => '110',
      'fields[field_instagramrecent][parent]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->drupalGet('admin/structure/types/manage/page/display');
    $edit1 = [
      'fields[field_instagramrecent][region]' => 'content',
      'fields[field_instagramrecent][label]' => 'above',
      'fields[field_instagramrecent][type]' => 'instagramfield_formatter',
      'fields[field_instagramrecent][weight]' => '110',
      'fields[field_instagramrecent][parent]' => '',
    ];
    $this->drupalPostForm(NULL, $edit1, t('Save'));

    // Create node.
    $this->node1 = $this->createNode([
      'title' => 'Node one',
      'type' => 'page',
    ]);

  }

  /**
   * Test instagram field.
   */
  public function testField() {
    $this->drupalLogin($this->admin);

    $this->drupalGet('/node/' . $this->node1->id() . '/edit');
    $this->drupalPostForm(NULL, [
      'body[0][value]' => 'testbody',
    ], t('Save'));

    // Does field exists in frontend.
    $this->drupalGet('/node/' . $this->node1->id());
    $this->assertSession()->elementExists('css', '.field--name-field-instagramrecent A IMG');
  }

}
