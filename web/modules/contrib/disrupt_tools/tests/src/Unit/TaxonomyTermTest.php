<?php

namespace Drupal\Tests\disrupt_tools\Unit;

use Drupal\disrupt_tools\Service\TaxonomyHelpers;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\disrupt_tools\Service\TaxonomyHelpers
 * @group disrupt_tools
 */
class TaxonomyTermTest extends UnitTestCase {
  private $terms = [
    '10' => [
      'tid' => '10',
      'vid' => 'category',
      'children' => [
        '30',
      ],
    ],
    '20' => [
      'tid' => '20',
      'vid' => 'category',
    ],
    '40' => [
      'tid' => '40',
      'vid' => 'category',
      'children' => [
        '50',
        '60',
      ],
    ],
    '50' => [
      'tid' => '50',
      'vid' => 'category',
      'parent' => '40',
    ],
    '60' => [
      'tid' => '60',
      'vid' => 'category',
      'children' => [
        '30',
      ],
      'parent' => '40',

    ],
    '30' => [
      'tid' => '30',
      'vid' => 'category',
      'parent' => '60',
    ],
  ];

  /**
   * PHPUnit setup.
   */
  public function setUp() {
    // Mock taxonomies entity.
    foreach ($this->terms as $id => $term) {
      $this->{'taxonomyProphet_' . $id} = $this->prophesize('\Drupal\taxonomy\Entity\Term');
      $this->{'taxonomyProphet_' . $id}->id()->willReturn($id);
      $this->{'taxonomyProphet_' . $id}->getVocabularyId()->willReturn($term['vid']);
    }

    // Mock taxonomies storage interface.
    $this->termStorageProphet = $this->prophesize('\Drupal\taxonomy\TermStorageInterface');
    $this->termStorageProphet->load(NULL)->willReturn(NULL);
    $this->termStorageProphet->loadParents(NULL)->willReturn(NULL);

    $tree = [];
    $this->all = [];
    foreach ($this->terms as $id => $term) {
      $this->termStorageProphet->load($id)->willReturn($this->{'taxonomyProphet_' . $id}->reveal());
      $this->all[] = $this->{'taxonomyProphet_' . $id}->reveal();

      // Generate tree for children.
      $children = NULL;
      if (isset($term['children'])) {
        $children = [];
        foreach ($term['children'] as $tid) {
          $children[] = $this->{'taxonomyProphet_' . $tid}->reveal();
        }
      }
      $this->termStorageProphet->loadTree($term['vid'], $id, 1, TRUE)->willReturn($children);

      // Generate tree for loadTree.
      $parent = [];
      if (isset($term['parent'])) {
        $parent[] = $this->{'taxonomyProphet_' . $term['parent']}->reveal();
      }
      else {
        $tree[] = $this->{'taxonomyProphet_' . $id}->reveal();
      }

      $this->termStorageProphet->loadParents($id)->willReturn($parent);
    }
    // Whole tree.
    $this->termStorageProphet->loadTree('category', 0, 1, TRUE)->willReturn($tree);

    // Mock entity type manager.
    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityManagerProphet->getStorage('taxonomy_term')->willReturn($this->termStorageProphet->reveal());

    // Mock query factory.
    $queryFactoryProphet = $this->prophesize('\Drupal\Core\Entity\Query\QueryFactory');

    // Taxonomy Term.
    $this->taxonomyHelpers = $this->getMockBuilder('\Drupal\disrupt_tools\Service\TaxonomyHelpers')
      ->setConstructorArgs([$entityManagerProphet->reveal(), $queryFactoryProphet->reveal()])
      ->setMethods(['getDepth', 'getParents'])
      ->getMock();

    // Mock the method fileExist.
    $this->taxonomyHelpers
      ->expects($this->any())
      ->method('getDepth')
      ->will(
              $this->returnValueMap([
                  ['10', 0],
                  ['20', 0],
                  ['40', 0],
                  ['50', 1],
                  ['60', 1],
                  ['30', 2],
              ])
          );

    // Mock the method fileExist.
    $this->taxonomyHelpers
      ->expects($this->any())
      ->method('getParents')
      ->will(
              $this->returnValueMap([
                  ['10', []],
                  ['20', []],
                  ['40', []],
                  ['50', [$this->terms['40']]],
                  ['60', [$this->terms['40']]],
                  ['30', [$this->terms['60'], $this->terms['40']]],
              ])
          );
  }

  /**
   * Check all the siblings terms of Term(10) work properly.
   */
  public function testGetSiblings10Work() {
    $siblings = $this->taxonomyHelpers->getSiblings('10');
    $this->assertEquals(3, count($siblings));
    $this->assertEquals(10, $siblings[0]->id());
    $this->assertEquals(20, $siblings[1]->id());
    $this->assertEquals(40, $siblings[2]->id());
  }

  /**
   * Check all the siblings terms of Term(50) work properly.
   */
  public function testGetSiblings50Work() {
    $siblings = $this->taxonomyHelpers->getSiblings('50');
    $this->assertEquals(2, count($siblings));
    $this->assertEquals(50, $siblings[0]->id());
    $this->assertEquals(60, $siblings[1]->id());
  }

  /**
   * Check siblings fail properly with non-existing term.
   */
  public function testGetSiblingsFaillWhenNull() {
    $this->assertEquals(NULL, $this->taxonomyHelpers->getSiblings(NULL));
  }

  /**
   * Check the top parent term of Term(10) work properly.
   */
  public function testGetTopParent10Work() {
    $parent = $this->taxonomyHelpers->getTopParent('10');
    $this->assertEquals(10, $parent->id());
  }

  /**
   * Check the top parent term of Term(50) work properly.
   */
  public function testGetTopParent50Work() {
    $parent = $this->taxonomyHelpers->getTopParent('50');
    $this->assertEquals(40, $parent->id());
  }

  /**
   * Check getTopParent fail properly with non-existing term.
   */
  public function testGetTopParentFaillWhenNull() {
    $this->assertEquals(NULL, $this->taxonomyHelpers->getTopParent(NULL));
  }

  /**
   * Check the depth of Term(10) work properly.
   */
  public function testGetDepth10Work() {
    $depth = $this->taxonomyHelpers->getDepth('10');
    $this->assertEquals(0, $depth);
  }

  /**
   * Check the depth of Term(50) work properly.
   */
  public function testGetDepth50Work() {
    $depth = $this->taxonomyHelpers->getDepth('50');
    $this->assertEquals(1, $depth);
  }

  /**
   * Check get parents fail properly with non-existing term.
   */
  public function testGetParentsFaillWhenNull() {
    $this->assertEquals(NULL, $this->taxonomyHelpers->getParents(NULL));
  }

  /**
   * Check get parents of Term(10) work properly.
   */
  public function testGetParents10Work() {
    $parents = $this->taxonomyHelpers->getParents('10');
    $this->assertEquals([], $parents);
  }

  /**
   * Check get parents of Term(50) work properly.
   */
  public function testGetParents50Work() {
    $parents = $this->taxonomyHelpers->getParents('50');
    $this->assertEquals([$this->terms['40']], $parents);
  }

}
