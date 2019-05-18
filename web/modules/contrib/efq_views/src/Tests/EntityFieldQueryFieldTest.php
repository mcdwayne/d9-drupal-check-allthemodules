<?php

/**
 * @file
 * Tests for EFQ Views query features.
 */

namespace Drupal\efq_views\Tests;

/**
 * Class EntityFieldQueryViewsFieldTestCase
 *
 * @package Drupal\efq_views\Tests
 */
class EntityFieldQueryViewsFieldTest extends EntityFieldQueryViewsTestBase {

  protected $entities_array = array(
    array(
      'ebundle' => 'bundle1',
      'elabel' => 'test label 1',
      'uid' => 1,
      'test_boolean' => TRUE,
      'test_integer' => 15,
      'test_decimal' => 78,
      'test_duration' => 900,
      'test_date' => 1336236850,
      'test_text' => 'test text',
      'field_boolean' => array(LANGUAGE_NONE => array(array('value' => 1))),
    ),
  );

  public function getInfo() {
    return array(
      'name' => 'EFQ Views fields',
      'description' => 'Tests EFQ Views field handlers',
      'group' => 'EFQ Views',
    );
  }

  public function testFields() {
    $this->runView();
    $entity = $this->entities[0];
    $this->assertPattern("/Entity ID: +$entity->eid/", 'Entity ID found');
    $this->assertPattern("/Efq views test ID: +$entity->eid/", 'Entity ID (meta) found');
    $this->assertPattern("/Test_boolean: +Yes/", 'Boolean found');
    $this->assertPattern('/Test_decimal: +' . ($entity->test_decimal / 100) . '/', 'Decimal found');
    $this->assertPattern('/Test_date: +' . preg_quote(format_date($entity->test_date), '/') . '/', 'Date found');
    $this->assertPattern('/Test_text: +test text/', 'Test text found');
    $url = url("custom/$entity->uid", array('absolute' => TRUE));
    $this->assertPattern('/URL: +' . preg_quote(l($url, $url), '/') . '/', 'URL found');
    $this->assertPattern('/field boolean: +On/', 'Boolean field API field found');
    $this->assertPattern('/Bundle: +' . $entity->ebundle . '/', 'Bundle field found');
    $this->assertPattern('/Bundle label: +' . preg_quote(t('Bundle 1'), '/') . '/', 'Bundle label field found');
    $this->assertPattern('/Label: +' . preg_quote($entity->elabel, '/') . '/', 'Label field found');
  }

}
