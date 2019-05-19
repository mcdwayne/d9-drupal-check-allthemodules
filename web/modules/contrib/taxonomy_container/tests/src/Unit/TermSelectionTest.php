<?php

namespace Drupal\Tests\taxonomy_container\Unit;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy_container\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Tests selection of taxonomy terms by the Taxonomy Container module.
 *
 * @group taxonomy_container
 * @coversDefaultClass \Drupal\taxonomy_container\Plugin\EntityReferenceSelection\TermSelection
 */
class TermSelectionTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityManager;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

  /**
   * The mocked taxonomy term entity storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $termStorage;

  /**
   * A mocked test user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Define basic settings for the plugin.
    $this->configuration = ['handler_settings' => []];

    // Provide mocks for the dependencies.
    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->termStorage = $this->prophesize(TermStorageInterface::class);
    $this->user = $this->prophesize(AccountInterface::class);

    $this->entityManager->getStorage('taxonomy_term')->willReturn($this->termStorage->reveal());
  }

  /**
   * Tests that terms are returned in the correct hierarchical format.
   *
   * @covers ::getReferenceableEntities
   *
   * @dataProvider getReferenceableEntitiesProvider
   */
  public function testGetReferenceableEntities(array $configuration, array $vocabularies, array $expected_result) {
    $plugin = $this->instantiatePlugin($configuration);

    // It is expected that the plugin will request information about the
    // available taxonomy term bundles, so that it can use this as defaults in
    // case no specific target bundles have been configured in the settings for
    // the entity reference field.
    $vocabulary_bundles = array_keys($vocabularies);
    $bundle_info = array_combine($vocabulary_bundles, array_map(function ($bundle) {
      return ['label' => $bundle];
    }, $vocabulary_bundles));
    $this->entityManager->getBundleInfo('taxonomy_term')->willReturn($bundle_info);

    // It is expected that the plugin will load the available taxonomy term
    // trees for every vocabulary.
    foreach ($vocabularies as $bundle => $parent_terms) {
      // Each tree will contain the terms from our test case. Populate it with
      // the parent terms as well as the child terms.
      $tree = [];

      foreach ($parent_terms as $parent_id => $parent_term) {
        // Created a mocked Term entity for the parent term.
        $tree[] = $this->getMockTerm($parent_id, $parent_term['label']);

        // Created mocked Term entities for the child terms.
        foreach ($parent_term['children'] as $child_id => $child_label) {
          $tree[] = $this->getMockTerm($child_id, $child_label, $parent_id);
        }
      }

      $this->termStorage->loadTree($bundle, 0, NULL, TRUE)->willReturn($tree);
    }

    $result = $plugin->getReferenceableEntities();

    $this->assertEquals($expected_result, $result);
  }

  /**
   * Returns a mocked taxonomy term entity.
   *
   * @param int|string $id
   *   The term ID. Can be a numeric ID or a string ID.
   * @param string $label
   *   The taxonomy term label.
   * @param int|string $parent
   *   Optional ID of the first parent term. Omit this or set to 0 to indicate
   *   that this is a root level term.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mocked term entity.
   */
  protected function getMockTerm($id, $label, $parent = 0) {
    // We're using MockBuilder instead of Prophecy so we can mock the accessing
    // of the public properties $term->parents and $term->depth. This is not
    // supported by Prophecy.
    $term = $this->getMockBuilder(Term::class)
      ->disableOriginalConstructor()
      ->getMock();
    $term->expects($this->any())
      ->method('id')
      ->willReturn($id);
    $term->expects($this->any())
      ->method('label')
      ->willReturn($label);
    // Mock accessing the public properties through the magic __get() method.
    $term->expects($this->any())
      ->method('__get')
      ->will($this->returnValueMap([
        ['parents', [0 => $parent]],
        ['depth', $parent === 0 ? 0 : 1],
      ]));
    return $term;
  }

  /**
   * Returns an instance of the plugin being tested.
   *
   * @return \Drupal\taxonomy_container\Plugin\EntityReferenceSelection\TermSelection
   *   The plugin being tested.
   */
  protected function instantiatePlugin(array $configuration = []) {
    return new TermSelection(
      $configuration,
      'taxonomy_container',
      [],
      $this->entityManager->reveal(),
      $this->moduleHandler->reveal(),
      $this->user->reveal()
    );
  }

  /**
   * Data provider for testGetReferenceableEntities().
   *
   * @return array
   *   Array of test data. Each array consists of an indexed array with two
   *   items:
   *   1. An array representing a two dimensional taxonomy tree, keyed by
   *      vocabulary name. Each tree consists of an associative array of root
   *      level taxonomy terms, keyed by term ID, with two values:
   *      - 'label': the term label of the root level taxonomy term.
   *      - 'children': an associative array of child terms, keyed by term ID.
   *   2. The expected select box options array as returned by the term
   *      selection plugin.
   */
  public function getReferenceableEntitiesProvider() {
    return [
      // Test case using numeric IDs and custom list item prefix.
      [
        // Configuration.
        [
          'prefix' => '+',
        ],
        // Two vocabularies, 'insults' and 'exclamations'.
        [
          'insults' => [
            1 => [
              'label' => 'Dunderheaded coconuts!',
              'children' => [
                2 => 'Gibbering anthropoids!',
                3 => 'Great flat-footed grizzly bear!',
              ],
            ],
            4 => [
              'label' => 'Livery-livered landlubbers!',
              'children' => [
                5 => 'Macrocephalic baboon!',
                6 => 'Purple profiteering jellyfish!',
              ],
            ],
          ],
          'exclamations' => [
            7 => [
              'label' => 'Billions of blue blistering boiled and barbecued barnacles!',
              'children' => [
                8 => 'Misguided missiles!',
              ],
            ],
          ],
        ],
        // Expected result.
        [
          'insults' => [
            'Dunderheaded coconuts!' => [
              2 => '+Gibbering anthropoids!',
              3 => '+Great flat-footed grizzly bear!',
            ],
            'Livery-livered landlubbers!' => [
              5 => '+Macrocephalic baboon!',
              6 => '+Purple profiteering jellyfish!',
            ],
            'Billions of blue blistering boiled and barbecued barnacles!' => [
              8 => '+Misguided missiles!',
            ],
          ],
        ],
      ],
      // Test case using string based numeric IDs, as provided by default by the
      // core Taxonomy module.
      [
        // Configuration.
        [],
        // Two vocabularies, 'insults' and 'exclamations'.
        [
          'insults' => [
            '1' => [
              'label' => 'Dunderheaded coconuts!',
              'children' => [
                '2' => 'Gibbering anthropoids!',
                '3' => 'Great flat-footed grizzly bear!',
              ],
            ],
            '4' => [
              'label' => 'Livery-livered landlubbers!',
              'children' => [
                '5' => 'Macrocephalic baboon!',
                '6' => 'Purple profiteering jellyfish!',
              ],
            ],
          ],
          'exclamations' => [
            '7' => [
              'label' => 'Billions of blue blistering boiled and barbecued barnacles!',
              'children' => [
                '8' => 'Misguided missiles!',
              ],
            ],
          ],
        ],
        // Expected result.
        [
          'insults' => [
            'Dunderheaded coconuts!' => [
              '2' => '-Gibbering anthropoids!',
              '3' => '-Great flat-footed grizzly bear!',
            ],
            'Livery-livered landlubbers!' => [
              '5' => '-Macrocephalic baboon!',
              '6' => '-Purple profiteering jellyfish!',
            ],
            'Billions of blue blistering boiled and barbecued barnacles!' => [
              '8' => '-Misguided missiles!',
            ],
          ],
        ],
      ],

      // Test case using string IDs, as can be provided by custom taxonomy
      // implementations (such as for example the RDF Taxonomy module).
      [
        // Configuration.
        [],
        // Two vocabularies, 'insults' and 'exclamations'.
        [
          'insults' => [
            'bldsckrs' => [
              'label' => 'Bloodsuckers!',
              'children' => [
                'cshnftdqdrpds' => 'Cushion-footed quadrupeds!',
                'hdrcrbn' => 'Hydrocarbon!',
              ],
            ],
            'lthsmbrt' => [
              'label' => 'Loathsome brute!',
              'children' => [
                'msrblrthwrms' => 'Miserable earth worms!',
                'sctrmp' => 'Saucy tramp!',
              ],
            ],
          ],
          'exclamations' => [
            'tnthsndthnderngtphns' => [
              'label' => 'Ten thousand thundering typhoons!',
              'children' => [
                'blblstrngbllbttmdbldrdsh' => 'Blue Blistering Bell-Bottomed Balderdash!',
              ],
            ],
          ],
        ],
        // Expected result.
        [
          'insults' => [
            'Bloodsuckers!' => [
              'cshnftdqdrpds' => '-Cushion-footed quadrupeds!',
              'hdrcrbn' => '-Hydrocarbon!',
            ],
            'Loathsome brute!' => [
              'msrblrthwrms' => '-Miserable earth worms!',
              'sctrmp' => '-Saucy tramp!',
            ],
            'Ten thousand thundering typhoons!' => [
              'blblstrngbllbttmdbldrdsh' => '-Blue Blistering Bell-Bottomed Balderdash!',
            ],
          ],
        ],
      ],
    ];
  }

}
