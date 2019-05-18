<?php

namespace Drupal\Tests\hierarchical_term_formatter\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RandomGeneratorTrait;
use Drupal\Core\Url;

/**
 * Verifies Hierarchical Term Formatter.
 *
 * @group hierarchical_term_formatter
 */
class HierarchicalTermFormatterTest extends BrowserTestBase {

  use RandomGeneratorTrait;
  use TermCreationTrait;

  /**
   * The privileged user performing the actions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

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
   * Generates a concatenated string of term links.
   */
  protected function generateLinkString(array $term_names, $separator) {
    $links = [];
    foreach ($term_names as $term_name) {
      $url = new Url('entity.taxonomy_term.canonical', [
        'taxonomy_term' => $this->createdTerms[$term_name],
      ]);
      $link = $this->container->get('link_generator')->generate($term_name, $url);
      $links[] = (string) $link;
    }
    return implode($separator, $links);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create a tree we can work on.
    $items = [
      '1' => [
        '1.1' => [
          '1.1.1' => [
            '1.1.1.1',
            '1.1.1.2',
          ],
          '1.1.2' => '1.1.2',
          '1.1.3' => '1.1.3',
        ],
        '1.2' => '1.2',
      ],
      '2' => [
        '2.1',
        '2.2',
      ],
    ];
    $this->createTerms($items);

    // Create a user with proper permissions.
    $this->user = $this->drupalCreateUser([
      'preview node view modes',
    ]);
  }

  /**
   * Tests Hierarchical Term Formatter core functionality.
   *
   * @dataProvider dataProvider
   */
  public function testFormatter($term_label, $expected) {
    // Create the node.
    $node = $this->container->get('entity.manager')->getStorage('node')->create([
      'title' => $this->randomMachineName(),
      'type' => 'number_story',
      'field_number' => $this->createdTerms[$term_label],
    ]);
    $node->save();
    $nid = $node->id();

    $this->drupalLogin($this->user);

    // Perform the actual test.
    foreach ($expected as $display_mode => $expected_value) {
      if ($display_mode == 'linked') {
        // We need to build the concatenated link string.
        // We do this here because we need the Service container.
        $expected_value = $this->generateLinkString($expected_value['items'], $expected_value['separator']);
      }
      $this->drupalGet("node/$nid/preview/$display_mode");
      $this->assertRaw(">$expected_value<");
    }

    $this->drupalLogout();
  }

  /**
   * Provides data for testFormatter().
   */
  public function dataProvider() {
    $data = [];

    $data[] = [
      '1.1.1.1',
      [
        'all' => '1 » 1.1 » 1.1.1 » 1.1.1.1',
        'linked' => [
          'items' => ['1', '1.1', '1.1.1', '1.1.1.1'],
          'separator' => ' » ',
        ],
        'nonroot_only' => '1.1 » 1.1.1 » 1.1.1.1',
        'parents_only' => '1 » 1.1 » 1.1.1',
        'reversed' => '1.1.1.1 » 1.1.1 » 1.1 » 1',
        'root_only' => '1',
        'selected_only' => '1.1.1.1',
        'separator' => '1 | 1.1 | 1.1.1 | 1.1.1.1',
      ],
    ];

    $data[] = [
      '1.1.1.2',
      [
        'all' => '1 » 1.1 » 1.1.1 » 1.1.1.2',
        'linked' => [
          'items' => ['1', '1.1', '1.1.1', '1.1.1.2'],
          'separator' => ' » ',
        ],
        'nonroot_only' => '1.1 » 1.1.1 » 1.1.1.2',
        'parents_only' => '1 » 1.1 » 1.1.1',
        'reversed' => '1.1.1.2 » 1.1.1 » 1.1 » 1',
        'root_only' => '1',
        'selected_only' => '1.1.1.2',
        'separator' => '1 | 1.1 | 1.1.1 | 1.1.1.2',
      ],
    ];

    $data[] = [
      '1.1.2',
      [
        'all' => '1 » 1.1 » 1.1.2',
        'linked' => [
          'items' => ['1', '1.1', '1.1.2'],
          'separator' => ' » ',
        ],
        'nonroot_only' => '1.1 » 1.1.2',
        'parents_only' => '1 » 1.1',
        'reversed' => '1.1.2 » 1.1 » 1',
        'root_only' => '1',
        'selected_only' => '1.1.2',
        'separator' => '1 | 1.1 | 1.1.2',
      ],
    ];

    $data[] = [
      '2',
      [
        'all' => '2',
        'linked' => [
          'items' => ['2'],
          'separator' => ' » ',
        ],
        'reversed' => '2',
        'root_only' => '2',
        'selected_only' => '2',
        'separator' => '2',
      ],
    ];

    $data[] = [
      '2.2',
      [
        'all' => '2 » 2.2',
        'linked' => [
          'items' => ['2', '2.2'],
          'separator' => ' » ',
        ],
        'nonroot_only' => '2.2',
        'parents_only' => '2',
        'reversed' => '2.2 » 2',
        'root_only' => '2',
        'selected_only' => '2.2',
        'separator' => '2 | 2.2',
      ],
    ];

    return $data;
  }

}
