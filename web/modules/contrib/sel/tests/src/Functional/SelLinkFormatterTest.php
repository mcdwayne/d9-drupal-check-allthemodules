<?php

namespace Drupal\Tests\sel\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that links link fields are well-handled.
 *
 * @group link
 */
class SelLinkFormatterTest extends BrowserTestBase {

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
  public function testLinkFormatter() {
    $absoluteHost = \Drupal::request()->getSchemeAndHttpHost();

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

    // Create a new node of the new node type.
    $node = Node::create([
      'type' => 'test_page',
      'title' => $this->randomString(),
    ]);
    $node->set($field_name, [
      [
        'uri' => 'internal:/<front>',
        'title' => '',
      ],
      [
        'uri' => $absoluteHost . '/user',
        'title' => '',
      ],
      [
        'uri' => 'https://example.com',
        'title' => '',
      ],
      [
        'uri' => $absoluteHost . 'er',
        'title' => '',
      ],
      [
        'uri' => 'mailto:an.user@example.com',
        'title' => '',
      ],
      [
        'uri' => 'internal:/<front>',
        'title' => 'Home link',
        'options' => serialize([
          'attributes' => [
            'target' => '_blank',
          ],
        ]),
      ],
      [
        'uri' => $absoluteHost . '/user',
        'title' => 'User page link',
      ],
      [
        'uri' => 'https://example.com',
        'title' => 'External link',
        'options' => serialize([
          'attributes' => [
            'target' => '_self',
            'rel' => 'nofollow',
          ],
        ]),
      ],
      [
        'uri' => 'https://example.com',
        'title' => 'External link with noreferrer',
        'options' => serialize([
          'attributes' => [
            'target' => '_blank',
            'rel' => 'noreferrer',
          ],
        ]),
      ],
      [
        'uri' => $absoluteHost . 'er',
        'title' => 'Alternate external link',
      ],
      [
        'uri' => 'mailto:an.user@example.com',
        'title' => 'Email link',
      ],
    ]);
    $node->save();

    $this->drupalGet($node->urlInfo());

    //
    // Check links are processed properly.
    //
    $links = $this->xpath("//div[contains(@class, :class)]//a", [
      ':class' => 'field--name-' . $field_name,
    ]);
    $front_page_path = Url::fromRoute('<front>')->toString();

    // Verify <front> link.
    $this->assertEquals(NULL, $links[0]->getAttribute('target'));
    $this->assertEquals(NULL, $links[0]->getAttribute('rel'));
    $this->assertEquals($front_page_path, $links[0]->getAttribute('href'));
    $this->assertEquals($front_page_path, $links[0]->getText());

    // Verify 'https://local.test/user' link.
    $this->assertEquals(NULL, $links[1]->getAttribute('target'));
    $this->assertEquals(NULL, $links[1]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . '/user', $links[1]->getAttribute('href'));
    $this->assertEquals($absoluteHost . '/user', $links[1]->getText());

    // Verify 'https://example.com' link.
    $this->assertEquals('_blank', $links[2]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[2]->getAttribute('rel'));
    $this->assertEquals('https://example.com', $links[2]->getAttribute('href'));
    $this->assertEquals('https://example.com', $links[2]->getText());

    // Verify 'https://local.tester' link.
    $this->assertEquals('_blank', $links[3]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[3]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . 'er', $links[3]->getAttribute('href'));
    $this->assertEquals($absoluteHost . 'er', $links[3]->getText());

    // Verify <front> link with label 'Home link'.
    $this->assertEquals('_blank', $links[4]->getAttribute('target'));
    $this->assertEquals(NULL, $links[4]->getAttribute('rel'));
    $this->assertEquals($front_page_path, $links[4]->getAttribute('href'));
    $this->assertEquals('Home link', $links[4]->getText());

    // Verify 'https://local.test/user' link with label
    // 'User page link'.
    $this->assertEquals(NULL, $links[5]->getAttribute('target'));
    $this->assertEquals(NULL, $links[5]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . '/user', $links[5]->getAttribute('href'));
    $this->assertEquals('User page link', $links[5]->getText());

    // Verify 'https://example.com' link with label 'External link'.
    $this->assertEquals('_blank', $links[6]->getAttribute('target'));
    $this->assertEquals(TRUE, strpos($links[6]->getAttribute('rel'), 'noreferrer') !== FALSE);
    $this->assertEquals(TRUE, strpos($links[6]->getAttribute('rel'), 'nofollow') !== FALSE);
    $this->assertEquals('https://example.com', $links[6]->getAttribute('href'));
    $this->assertEquals('External link', $links[6]->getText());

    // Verify 'https://example.com' link with label
    // 'External link with noreferrer'.
    $this->assertEquals('_blank', $links[7]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[7]->getAttribute('rel'));
    $this->assertEquals('https://example.com', $links[7]->getAttribute('href'));
    $this->assertEquals('External link with noreferrer', $links[7]->getText());

    // Verify 'https://local.tester' link with label 'Alternate external link'.
    $this->assertEquals('_blank', $links[8]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[8]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . 'er', $links[8]->getAttribute('href'));
    $this->assertEquals('Alternate external link', $links[8]->getText());

    //
    // Check that email links are sanitized properly (without javascript).
    //
    $spamspans = $this->xpath("//div[contains(@class, :class)]//span[contains(@class, :span-class)]", [
      ':class' => 'field--name-' . $field_name,
      ':span-class' => 'spamspan',
    ]);

    // Verify 'mailto:an.user@example.com' email link is sanitizes.
    $this->assertEquals('an.user [at] example.com', $spamspans[0]->getText());

    // Verify 'mailto:an.user@example.com' email link with label 'Email link'
    // is sanitized.
    $this->assertEquals('an.user [at] example.com (Email link)', $spamspans[1]->getText());
  }

}
