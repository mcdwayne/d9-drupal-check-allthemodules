<?php

/**
 * @file
 * Definition of Drupal\region\Tests\RegionTest.
 */

namespace Drupal\region\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test for region management.
 */
class RegionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('region');

  public static function getInfo() {
    return array(
      'name' => 'Region management',
      'description' => 'Tests region management.',
      'group' => 'Region',
    );
  }

  /**
   * Tests the default regions.
   */
  public function testDefaultRegions() {
    // Create a new user, allow him to manage the blocks and the languages.
    $admin_user = $this->drupalCreateUser(array(
      'administer regions',
    ));
    $this->drupalLogin($admin_user);

    // Check that these regions show up on the user interface.
    $base_regions = array(
      'header_a' => 'Header A',
      'header_b' => 'Header B',
      'header_c' => 'Header C',
      'subheader_a' => 'Subheader A',
      'subheader_b' => 'Subheader B',
      'subheader_c' => 'Subheader C',
      'navigation' => 'Navigation',
      'title' => 'Title',
      'body' => 'Body',
      'sidebar_a' => 'Sidebar A',
      'sidebar_b' => 'Sidebar B',
      'sidebar_c' => 'Sidebar C',
      'footer_a' => 'Footer A',
      'footer_b' => 'Footer B',
      'footer_c' => 'Footer C',
    );

    // Check if the visibility setting is available.
    $this->drupalGet('admin/structure/regions');
    foreach($base_regions as $machine_name => $label) {
      $this->assertText($machine_name);
      $this->assertText($label);
    }
  }

  /**
   * Tests editing a default region.
   */
  public function testEditDefaultRegion() {
    // Create a new user, allow him to manage the blocks and the languages.
    $admin_user = $this->drupalCreateUser(array(
      'administer regions',
    ));
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/regions/region/body/edit');
    $this->assertResponse(200);
    $this->assertPattern('!disabled="disabled"(.+)id="edit-id"(.+)value="body"!', 'Existing region name machine name field is disabled.');

    $edit = array('label' => 'Page content');
    $this->drupalPost('admin/structure/regions/region/body/edit', $edit, t('Save'));
    $this->assertText('Page content');
    $this->assertNoText('Body');
    $this->assertRaw(t('Region %label saved.', array('%label' => 'Page content')));
  }

  /**
   * Tests editing a default region.
   */
  public function testDeleteDefaultRegion() {
    // Create a new user, allow him to manage the blocks and the languages.
    $admin_user = $this->drupalCreateUser(array(
      'administer regions',
    ));
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/regions/region/body/delete');
    $this->assertResponse(200);

    $this->drupalPost('admin/structure/regions/region/body/delete', array(), t('Delete'));
    $this->assertRaw(t('Region %label has been deleted.', array('%label' => 'Body')));
    $this->assertNoText('body');
  }

  /**
   * Tests adding a new region and all actions on that.
   */
  public function testNewRegion() {
    // Create a new user, allow him to manage the blocks and the languages.
    $admin_user = $this->drupalCreateUser(array(
      'administer regions',
    ));
    $this->drupalLogin($admin_user);

    $edit = array('label' => 'Banner', 'id' => 'banner');
    $this->drupalPost('admin/structure/regions/add', $edit, t('Save'));
    $this->assertText('banner');
    $this->assertRaw(t('Region %label saved.', array('%label' => 'Banner')));

    $edit = array('label' => 'Highlight');
    $this->drupalPost('admin/structure/regions/region/banner/edit', $edit, t('Save'));
    $this->assertNoText('Banner');
    $this->assertText('banner');
    $this->assertRaw(t('Region %label saved.', array('%label' => 'Highlight')));

    $edit = array('label' => 'Conflicting banner', 'id' => 'banner');
    $this->drupalPost('admin/structure/regions/add', $edit, t('Save'));
    $this->assertText(t('The machine-readable name is already in use. It must be unique.'));

    $this->drupalGet('admin/structure/regions/region/banner/edit');
    $this->assertPattern('!disabled="disabled"(.+)id="edit-id"(.+)value="banner"!', 'Existing region name machine name field is disabled.');

    $this->drupalPost('admin/structure/regions/region/banner/delete', array(), t('Delete'));
    $this->assertRaw(t('Region %label has been deleted.', array('%label' => 'Highlight')));
    $this->assertNoText('banner');
  }

}
