<?php

/**
 * @file
 * Definition of Drupal\dfw\Tests\DfwFormsTest.
 */

namespace Drupal\dfw\Tests;

use Drupal\simpletest\WebTestBase;

class DfwFormsTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dfw' /*, 'field', 'field_ui'*/);

  protected $profile = 'standard';

  /**
   * Implementation of setUp().
   */
  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array(
      'administer site configuration'
    ));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('Dfw Forms'),
      'description' => t('Tests for Drupal Firewall forms.'),
      'group' => t('Dfw'),
    );
  }

  /**
   * Forms tests.
   */
  function testDfwForms() {

    // admin form test
    $this->drupalGet('admin/config/dfw/config');
    $this->assertText(t('Configure dfw'));

    // @todo change this when dfw_select hooks_form() immpemented
    $this->assertText(t('hook_menu filter setting - check modules to disable menu'));
    $this->assertText(t('hook_cron filter setting - check modules to disable menu'));
    $this->assertText(t('Cache parameters'));
    $this->assertText(t('dfw parameters'));

    // admin form submit test
    // @todo submit test


    // debug form test

    // test autocomplete processed form element
    // @todo #fixme
    // $this->assertRaw('<input class="autocomplete autocomplete-processed" id="edit-drupal-container-autocomplete" type="hidden"');
    $this->drupalGet('admin/config/dfw/debug');

    $this->assertText(t('Debug'));
    $this->assertText(t('drupal_container() debugger'));


    // test empty form submit
    $this->drupalPost('admin/config/dfw/config', NULL, t('Submit'));
    $this->assertText(t('Clear cache all - drupal_flush_all_caches'));
    // test some hooks disabled
    $edit1 = array(
      'system_menu' => TRUE,
      'system_cron' => TRUE,
      'cca' => TRUE,
    );
    $this->drupalPost('admin/config/dfw/config', $edit1, t('Submit'));
    $this->assertText(t('all caches cleared'));
    $this->assertText(t('Dfw: all hook_menu re-executed'));
    $this->assertFieldChecked('edit-system-menu');
    $this->assertRaw('<div class="description" id="edit-system-menu--description">Disabled in config. Firewalled!!!</div>');
    $this->assertFieldChecked('edit-system-cron');
    $this->assertRaw('<div class="description" id="edit-system-cron--description">Disabled in config. Firewalled!!!</div>');

    // test dfw clear settings
    $edit2 = array(
      'system_menu' => FALSE,
    );
    $this->drupalPost('admin/config/dfw/config', $edit2, t('Submit'));
    $this->assertNoFieldChecked('edit-system-menu');
    $this->assertRaw('<div class="description" id="edit-system-menu--description">Disabled in config. Firewalled!!!</div>');
    $this->assertFieldChecked('edit-system-cron');
    $this->assertRaw('<div class="description" id="edit-system-cron--description">Disabled in config. Firewalled!!!</div>');

    // test dfw clear settings
    $edit3 = array(
      'cca' => TRUE,
    );
    $this->drupalPost('admin/config/dfw/config', $edit3, t('Submit'));
    $this->assertNoFieldChecked('edit-system-menu');
    $this->assertFieldChecked('edit-system-cron');
    $this->assertRaw('<div class="description" id="edit-system-menu--description">Got via getImplementations()</div>');
    $this->assertRaw('<div class="description" id="edit-system-cron--description">Disabled in config. Firewalled!!!</div>');
  }
}
