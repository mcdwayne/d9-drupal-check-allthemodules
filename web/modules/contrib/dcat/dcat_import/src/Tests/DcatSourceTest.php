<?php

namespace Drupal\dcat_import\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\dcat_import\Entity\DcatSource;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group dcat_import
 */
class DcatSourceTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dcat_import'];

  /**
   * Tests that the overview page loads with a 200 response.
   */
  public function testOverview() {
    $user = $this->drupalCreateUser(['administer dcat sources']);
    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_source.collection'));
    $this->assertResponse(200);
  }

  /**
   * Test the agent add form.
   */
  public function testAddForm() {
    $user = $this->drupalCreateUser([
      'administer dcat sources'
    ]);
    $name = $this->randomMachineName();
    $id = strtolower($this->randomMachineName());
    $edit = [
      'label' => $name,
      'id' => $id,
      'format' => 'turtle',
      'iri' => 'http://example.com/dcat_source',
      'description' => $this->randomString(256),
      'global_theme' => 0,
      'lowercase_taxonomy_terms' => 0,
    ];

    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_source.add_form'));

    // Required fields.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_source.add_form'), [], t('Save'));
    $this->assertText('Label field is required.');
    $this->assertText('Machine-readable name field is required.');
    $this->assertText('IRI field is required.');

    // Adding and viewing entity.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_source.add_form'), $edit, t('Save'));
    $this->assertText('Created the ' . $name . ' DCAT source.');
    $this->drupalGet('/admin/structure/dcat/settings/dcat_source/' . $id . '/edit');
    $this->assertResponse(200);
    $this->assertText($name);
    $this->assertText($id);
  }

  /**
   * Test automated migrate config creation.
   */
  public function testMigrateConfig() {
    $label = $this->randomMachineName();
    $id = strtolower($this->randomMachineName());
    $iri = 'http://example.com/' . $this->randomMachineName(4);
    $source = DcatSource::create([
      'label' => $label,
      'id' => $id,
      'iri' => $iri,
      'global_theme' => FALSE,
    ]);
    $source->saveMigrations();
    $source->save();

    $group = \Drupal::config('migrate_plus.migration_group.dcat_import_' . $id);
    $dataset = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_dataset');
    $distribution = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_distribution');
    $dataset_keyword = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_dataset_keyword');
    $agent = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_agent');
    $vcard = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_vcard');
    $theme = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_theme');

    $this->assertEqual($group->get('label'), $label);

    $group_id = 'dcat_import_' . $id;
    $this->assertEqual($dataset->get('migration_group'), $group_id);
    $this->assertEqual($distribution->get('migration_group'), $group_id);
    $this->assertEqual($dataset_keyword->get('migration_group'), $group_id);
    $this->assertEqual($agent->get('migration_group'), $group_id);
    $this->assertEqual($vcard->get('migration_group'), $group_id);
    $this->assertEqual($theme->get('migration_group'), $group_id);
  }

  /**
   * Test automated migrate config creation with global themes.
   */
  public function testMigrateConfigGlobalTheme() {
    $label = $this->randomMachineName();
    $id = strtolower($this->randomMachineName());
    $iri = 'http://example.com/' . $this->randomMachineName(4);
    $source = DcatSource::create([
      'label' => $label,
      'id' => $id,
      'iri' => $iri,
      'global_theme' => TRUE,
    ]);
    $source->saveMigrations();
    $source->save();

    $group = \Drupal::config('migrate_plus.migration_group.dcat_import_' . $id);
    $dataset = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_dataset');
    $distribution = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_distribution');
    $dataset_keyword = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_dataset_keyword');
    $agent = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_agent');
    $vcard = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_vcard');
    $theme = \Drupal::config('migrate_plus.migration.dcat_import_' . $id . '_theme');

    $this->assertEqual($group->get('label'), $label);

    $group_id = 'dcat_import_' . $id;
    $this->assertEqual($dataset->get('migration_group'), $group_id);
    $this->assertEqual($distribution->get('migration_group'), $group_id);
    $this->assertEqual($dataset_keyword->get('migration_group'), $group_id);
    $this->assertEqual($agent->get('migration_group'), $group_id);
    $this->assertEqual($vcard->get('migration_group'), $group_id);
    $this->assertNull($theme->get('migration_group'), $group_id);
  }

}
