<?php

namespace Drupal\Tests\expandable_formatter\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the expandable field formatter.
 *
 * @group expandable_formatter
 */
class FormatterTest extends JavascriptTestBase {

  /**
   * The view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $entityViewDisplay;

  /**
   * The field that is being tested.
   *
   * @var string
   */
  protected $fieldName = 'test_formatter';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'expandable_formatter',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create user and log in.
    $this->user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->user);

    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'text_long',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'required' => TRUE,
    ])->save();
  }

  /**
   * Tests the formatter.
   */
  public function testFormatter() {
    $displayOptions = [
      'type' => 'expandable_formatter',
      'label' => 'hidden',
      'settings' => [
        'trigger_expanded_label' => 'Zebra',
        'trigger_collapsed_label' => 'LLama',
        'collapsed_height' => 20,
        'use_ellipsis' => TRUE,
      ],
    ];
    $entityViewDisplay = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $entityViewDisplay->setComponent($this->fieldName, $displayOptions);
    $entityViewDisplay->save();

    $entity = EntityTest::create(['test_formatter' => 'Short text']);
    $entity->save();

    $this->drupalGet($entity->url());
    $this->assertLinkIsVisible('Zebra', FALSE);
    $this->assertLinkIsVisible('Llama', FALSE);
    $this->assertSession()->pageTextContains('…', 'The ellipsis is not showing when it has been enabled.');

    // When the text height exceeds the setting, then the collapsed label should
    // appear.
    $entity->set($this->fieldName, str_repeat('Long text ', 200));
    $entity->save();
    $this->drupalGet($entity->url());
    $this->assertLinkIsVisible('Zebra', TRUE);
    $this->assertLinkIsVisible('Llama', FALSE);

    // Ellipsis should not be rendered when disabled.
    $displayOptions['settings']['use_ellipsis'] = FALSE;
    $entityViewDisplay->setComponent($this->fieldName, $displayOptions)->save();
    $this->drupalGet($entity->url());
    $this->assertSession()->pageTextNotContains('…', 'The ellipsis is showing when it has been disabled.');
  }

  /**
   * Asserts if a link is visible.
   *
   * @param string $label
   *   The link text.
   * @param bool $isVisible
   *   (Optional) Whether the link should be visible or not.
   */
  protected function assertLinkIsVisible($label, $isVisible = TRUE) {
    $link = $this->getSession()->getPage()->find('named', ['link', $label]);
    if ($isVisible) {
      $this->assertNotEmpty($link);
      $this->assertTrue($link->isVisible());
    }
    elseif ($link) {
      $this->assertFalse($link->isVisible());
    }
  }

}
