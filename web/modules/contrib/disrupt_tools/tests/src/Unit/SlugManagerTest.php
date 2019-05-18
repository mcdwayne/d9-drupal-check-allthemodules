<?php

namespace Drupal\Tests\disrupt_tools\Unit;

use Drupal\disrupt_tools\Service\SlugManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\disrupt_tools\Service\SlugManager
 * @group disrupt_tools
 */
class SlugManagerTest extends UnitTestCase {

  /**
   * PHPUnit setup.
   */
  public function setUp() {
    // Mock url.
    $urlProphet = $this->prophesize('\Drupal\Core\Url');
    $urlProphet->toString()->willReturn('it');

    // Mock taxonomy entity.
    $this->taxonomyProphet = $this->prophesize('\Drupal\taxonomy\Entity\Term');
    $taxonomyProphet = $this->taxonomyProphet;
    $taxonomyProphet->id()->willReturn(1);
    $taxonomyProphet->label()->willReturn('Test Term');
    $taxonomyProphet->toUrl()->willReturn($urlProphet->reveal());

    // Mock taxonomy storage interface.
    $termStorageProphet = $this->prophesize('\Drupal\taxonomy\TermStorageInterface');
    $termStorageProphet->load(1)->willReturn($taxonomyProphet->reveal());
    $termStorageProphet->load(2)->willReturn(NULL);

    // Mock entity type manager.
    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityManagerProphet->getStorage('taxonomy_term')->willReturn($termStorageProphet->reveal());

    // Mock alias manager.
    $aliasManagerProphet = $this->prophesize('\Drupal\Core\Path\AliasManagerInterface');
    $aliasManagerProphet->getPathByAlias('/work/it')->willReturn('/taxonomy/term/1');
    $aliasManagerProphet->getPathByAlias('/work/admin')->willReturn('/taxonomy/term/2');

    // Slug manager.
    $this->slugManager = new SlugManager($aliasManagerProphet->reveal(), $entityManagerProphet->reveal());

  }

  /**
   * Check the Slug 2 Taxonomy work properly with existing term.
   */
  public function testSlug2TaxonomyWork() {
    $term = $this->slugManager->slug2Taxonomy('it', '/work/');
    $this->assertEquals('Test Term', $term->label());
  }

  /**
   * Check the Slug 2 Taxonomy fail properly with non-existing term.
   */
  public function testSlug2TaxonomyFail() {
    $this->assertEquals(NULL, $this->slugManager->slug2Taxonomy('admin', '/work/'));
  }

  /**
   * Check the Taxonomy 2 Slug work properly with existing term.
   */
  public function testTaxonomy2SlugWork() {
    $term = $this->taxonomyProphet->reveal();
    $this->assertEquals('it', $this->slugManager->taxonomy2Slug($term, '/work/'));
  }

}
