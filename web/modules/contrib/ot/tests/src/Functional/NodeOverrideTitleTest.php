<?php

namespace Drupal\Tests\ot\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Browser tests for overriding title.
 *
 * @ingroup ot
 *
 * @group ot
 */
class NodeOverrideTitleTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['ot'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['view ot', 'add ot']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests Add Override title.
   */
  public function testAddOverrideTitle() {
    $this->drupalGet('admin/structure/ot');
    $this->assertResponse(200);
    $this->drupalGet('admin/structure/ot/add');
    $edit = array(
      'ot_type'=>'node_path',
      'ot_type_id'=>'/',
      'ot_title'=>'Test fornt page override',
      'ot_location'=>'both',
      'ot_status'=>'1'
    );
    $this->drupalPostForm('admin/structure/ot/add', $edit, t('Submit'));
  }

}
