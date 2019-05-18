<?php

namespace Drupal\Tests\multiversion\Functional;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the MultiversionManager class.
 *
 * @group multiversion
 */
class MultiversionManagerTest extends BrowserTestBase {

  const REVISION_HASH_REGEX = '[0-9a-f]{32}';

  protected $strictConfigSchema = FALSE;

  protected static $modules = ['multiversion', 'entity_test'];

  /**
   * @var \Drupal\multiversion\MultiversionManager
   */
  protected $multiversionManager;

  protected function setUp() {
    parent::setUp();
    $this->multiversionManager = \Drupal::service('multiversion.manager');
  }

  protected function extractRevisionHash($rev) {
    preg_match('/\d\-(' . self::REVISION_HASH_REGEX . ')/', $rev, $matches);
    return isset($matches[1]) ? $matches[1] : FALSE;
  }

  public function assertRevisionId($index, $value, $message) {
    $this->assertTrue(preg_match('/' . $index . '\-' . self::REVISION_HASH_REGEX . '/', $value), $message);
  }

  public function testRevisionIdGeneration() {
    $entity = EntityTestRev::create();
    $first_rev = $this->multiversionManager->newRevisionId($entity, 0);
    $this->assertRevisionId(1, $first_rev, 'First revision ID was generated correctly.');

    $new_rev = $this->multiversionManager->newRevisionId($entity, 0);
    $this->assertEqual($first_rev, $new_rev, 'Identical revision IDs with same input parameters.');

    $second_rev = $this->multiversionManager->newRevisionId($entity, 1);
    $this->assertRevisionId(2, $second_rev, 'Second revision ID was generated correctly.');

    $this->assertEqual($this->extractRevisionHash($first_rev), $this->extractRevisionHash($second_rev), 'First and second revision hashes was identical (entity did not change).');

    $revs = [$first_rev];

    $test_entity = clone $entity;
    $test_entity->_rev->value = $first_rev;
    $revs[] = $this->multiversionManager->newRevisionId($test_entity, 0);
    $this->assertTrue(count($revs) == count(array_unique($revs)), 'Revision ID varies on old revision.');

    $test_entity = clone $entity;
    $test_entity->name = $this->randomMachineName();
    $revs[] = $this->multiversionManager->newRevisionId($test_entity, 0);
    $this->assertTrue(count($revs) == count(array_unique($revs)), 'Revision ID varies on entity fields.');

    $test_entity = clone $entity;
    $test_entity->_deleted->value = TRUE;
    $revs[] = $this->multiversionManager->newRevisionId($test_entity, 0);
    $this->assertTrue(count($revs) == count(array_unique($revs)), 'Revision ID varies on deleted flag.');
  }
  
  public function testGetSupportedEntityTypes() {
    foreach ($this->multiversionManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      $label = $entity_type->get('label');
      $this->assertTrue($entity_type instanceof ContentEntityTypeInterface, "$label is an instance of ContentEntityTypeInterface");
    }
  }

}
