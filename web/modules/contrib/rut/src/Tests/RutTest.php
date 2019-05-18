<?php

/**
 * @file
 * Contains \Drupal\rut\Tests\RutTest.
 */

namespace Drupal\rut\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;

/**
 * Test the rut_field element.
 *
 * @group rut
 */
class RutTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rut_test');

  protected $profile = 'testing';

  /**
   * Tests that #type 'rut_field' fields are properly validated.
   */
  public function testFormRut() {
    $edit = array();
    $edit['rut'] = '1-2';
    $edit['rut_required'] = ' ';
    $this->drupalPostForm('rut-test', $edit, 'Submit');
    $this->assertRaw(t('The Rut/Run @rut is invalid.', array('@rut' => '1-2')));
    $this->assertRaw(t('@name field is required.', array('@name' => 'Rut')));

    $edit = array();
    $edit['rut'] = '';
    $edit['rut_required'] = '1-9';
    $values = Json::decode($this->drupalPostForm('rut-test', $edit, 'Submit'));
    $this->assertIdentical($values['rut'], '');
    $this->assertEqual($values['rut_required'], '1-9');

    $edit = array();
    $edit['rut'] = '11.111.111-1';
    $edit['rut_required'] = '1-9';
    $values = Json::decode($this->drupalPostForm('rut-test', $edit, 'Submit'));
    $this->assertEqual($values['rut'], '11.111.111-1');
    $this->assertEqual($values['rut_required'], '1-9');
  }
}
