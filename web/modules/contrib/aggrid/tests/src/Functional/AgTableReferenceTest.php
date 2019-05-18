<?php

namespace Drupal\Tests\aggrid\Functional;

use Drupal\aggrid\Entity\Aggrid;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensure ag test entities can be used in entity_reference fields.
 *
 * @group aggrid
 *
 * @ingroup aggrid
 */
class AgTableReferenceTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'aggrid'
    , 'aggrid_demo'
    , 'node'
    , 'field'
    , 'field_ui'
  ];

  /**
   * {@inheritdoc}
   *
   * We use the minimal profile because otherwise local actions aren't placed in
   * a block anywhere.
   */
  protected $profile = 'minimal';

  /**
   * Ensure we can use ag-Grid entities as reference fields.
   */
  public function testEntityReference() {
    $assert = $this->assertSession();

    $type = $this->createContentType();

    $this->drupalLogin($this->createUser([
      'create ' . $type->id() . ' content',
      'administer node fields',
    ]));

    // - Go to the "manage fields" section of a content entity.
    $this->drupalGet('admin/structure/types/manage/' . $type->id() . '/fields');
    $assert->statusCodeEquals(200);

    // - Click on the "add field" button.
    $this->clickLink('Add field');

    // - Under "Reference" select "other".
    // - Choose a label and click continue.
    $this->drupalPostForm(NULL, [
      'new_storage_type' => 'entity_reference',
      'field_name' => 'aggrid',
      'label' => 'ag-Grid',
    ], 'Save and continue');
    $assert->statusCodeEquals(200);

    // - Under configuration select "aggrid".
    $this->drupalPostForm(NULL, [
      'settings[target_type]' => 'aggrid',
    ], 'Save field settings');
    $assert->statusCodeEquals(200);

    // - Create a content entity containing the created reference field. Select
    //   "ag-Grid DEMO Vehicles and Colors".
    // - Click save.
    $aggrid = Aggrid::loadMultiple();
    /* @var aggrid \Drupal\aggrid\Entity\Aggrid */
    $aggrid = reset($aggrid);
    $this->drupalPostForm(Url::fromRoute('node.add', ['node_type' => $type->id()]), [
      'title[0][value]' => 'title',
      'field_aggrid[0][target_id]' => $aggrid->label(),
    ], 'Save');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains($aggrid->label());
  }

}
