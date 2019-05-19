<?php

namespace Drupal\Tests\uc_catalog\Functional;

/**
 * Tests for the Ubercart catalog breadcrumbs.
 *
 * @group ubercart
 */
class CatalogBreadcrumbTest extends CatalogTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the product node breadcrumb.
   */
  public function testProductBreadcrumb() {
    $this->drupalLogin($this->adminUser);

    $grandparent = $this->createCatalogTerm();
    $parent = $this->createCatalogTerm(['parent' => $grandparent->id()]);
    $term = $this->createCatalogTerm(['parent' => $parent->id()]);
    $product = $this->createProduct([
      'taxonomy_catalog' => [$term->id()],
    ]);

    $this->drupalGet($product->toUrl());

    // Fetch each node title in the current breadcrumb.
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $func = function ($element) {
      return $element->getText();
    };
    $links = array_map($func, $links);
    $this->assertEquals(count($links), 5, 'The correct number of links were found.');
    $this->assertEquals($links[0], 'Home');
    $this->assertEquals($links[1], 'Catalog');
    $this->assertEquals($links[2], $grandparent->label());
    $this->assertEquals($links[3], $parent->label());
    $this->assertEquals($links[4], $term->label());
  }

  /**
   * Tests the catalog view breadcrumb.
   */
  public function testCatalogBreadcrumb() {
    $this->drupalLogin($this->adminUser);

    $grandparent = $this->createCatalogTerm();
    $parent = $this->createCatalogTerm(['parent' => $grandparent->id()]);
    $term = $this->createCatalogTerm(['parent' => $parent->id()]);
    $product = $this->createProduct([
      'taxonomy_catalog' => [$term->id()],
    ]);

    $this->drupalGet('catalog');
    $this->clickLink($grandparent->label());
    $this->clickLink($parent->label());
    $this->clickLink($term->label());

    // Fetch each node title in the current breadcrumb.
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $func = function ($element) {
      return $element->getText();
    };
    $links = array_map($func, $links);
    $this->assertEquals(count($links), 4, 'The correct number of links were found.');
    $this->assertEquals($links[0], 'Home');
    $this->assertEquals($links[1], 'Catalog');
    $this->assertEquals($links[2], $grandparent->label());
    $this->assertEquals($links[3], $parent->label());
  }

}
