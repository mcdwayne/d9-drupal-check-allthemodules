<?php

namespace Drupal\Tests\sel\FunctionalJavascript;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\link\LinkItemInterface;
use Drupal\node\Entity\Node;

/**
 * Tests that email links are accessible with js.
 *
 * @group link
 */
class SelLinkFormatterJavascriptTest extends JavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'node',
    'link',
    'spamspan',
    'sel',
  ];

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The instance used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'test_page',
      'name' => 'Test page',
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'create test_page content',
      'edit own test_page content',
      'link to any page',
    ]));
  }

  /**
   * Tests link filter.
   */
  public function testEmailLinkSanitizerFormatter() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'link',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'test_page',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ]);
    $this->field->save();
    entity_get_form_display('node', 'test_page', 'default')
      ->setComponent($field_name, [
        'type' => 'link_default',
        'settings' => [],
      ])
      ->save();
    entity_get_display('node', 'test_page', 'default')
      ->setComponent($field_name, [
        'type' => 'sel_link',
      ])
      ->save();

    // Create a new node.
    $node = Node::create([
      'type' => 'test_page',
      'title' => $this->randomString(),
    ]);
    $node->set($field_name, [
      [
        'uri' => 'mailto:an.user@example.com',
        'title' => '',
      ],
      [
        'uri' => 'mailto:an.user@example.com',
        'title' => 'Email link',
      ],
    ]);
    $node->save();

    $this->drupalGet($node->urlInfo());
    $page = $this->getSession()->getPage();

    //
    // Check that email links are processed properly by the formatter.
    //
    $links = $page->findAll('xpath', '//div[contains(concat(" ", normalize-space(@class), " "), " field--name-' . $field_name . ' ")]//a');
    // Verify simple 'mailto:an.user@example.com' link.
    $this->assertEquals('mailto:an.user@example.com', $links[0]->getAttribute('href'));
    $this->assertEquals('an.user@example.com', $links[0]->getText());
    // Verify 'mailto:an.user@example.com' link with title 'Email link'.
    $this->assertEquals('mailto:an.user@example.com', $links[1]->getAttribute('href'));
    $this->assertEquals('Email link', $links[1]->getText());
  }

}
