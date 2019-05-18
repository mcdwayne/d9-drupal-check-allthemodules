<?php

namespace Drupal\Tests\hierarchical_term_formatter\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RandomGeneratorTrait;

/**
 * Verifies fix for formatter Fatal error.
 *
 * @see https://www.drupal.org/project/hierarchical_term_formatter/issues/2902843
 *
 * @group hierarchical_term_formatter
 */
class HierarchicalTermFormatterErrorTest extends BrowserTestBase {

  use RandomGeneratorTrait;
  use TermCreationTrait;

  /**
   * Collection of taxonomy terms created for this test.
   *
   * An array whose keys are term names and whose
   * values are term Ids.
   *
   * @var array
   */
  protected $createdTerms;

  /**
   * Required modules.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'node',
    'taxonomy',
    'hierarchical_term_formatter',
    'hierarchical_term_formatter_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create a tree we can work on.
    $items = [
      '1' => [
        '1.1' => [
          '1.1.1' => '1.1.1',
        ],
      ],
    ];
    $this->createTerms($items);

    // Create a user with proper permissions.
    $this->user = $this->drupalCreateUser([
      'preview node view modes',
    ]);
  }

  /**
   * Tests Fatal error no longer occurs.
   *
   * @dataProvider dataProvider
   */
  public function testFormatterError($view_mode) {
    // Create a node.
    $node = $this->container->get('entity.manager')->getStorage('node')->create([
      'title' => $this->randomMachineName(),
      'type' => 'number_story',
      'field_number' => $this->createdTerms['1.1.1'],
    ]);
    $node->save();
    $nid = $node->id();

    $this->drupalLogin($this->user);

    $this->drupalGet("node/$nid/preview/$view_mode");
    $this->assertResponse(200);

    // Should be the same if we remove the term.
    $term = $this->container->get('entity.manager')->getStorage('taxonomy_term')->load($this->createdTerms['1.1.1']);
    $term->delete();

    $this->container->get('cache_tags.invalidator')->invalidateTags($node->getCacheTagsToInvalidate());
    $this->drupalGet("node/$nid/preview/$view_mode");
    $this->assertResponse(200);

    $this->drupalLogout();
  }

  /**
   * Provides data for testFormatterError().
   */
  public function dataProvider() {
    return [
      ['all'],
      ['linked'],
      ['nonroot_only'],
      ['parents_only'],
      ['reversed'],
      ['root_only'],
      ['selected_only'],
      ['separator'],
    ];
  }

}
