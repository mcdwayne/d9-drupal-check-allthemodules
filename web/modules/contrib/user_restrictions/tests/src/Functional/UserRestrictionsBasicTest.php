<?php

namespace Drupal\Tests\user_restrictions\Functional;

/**
 * Defines basic user restriction tests.
 *
 * @group user_restrictions
 */
class UserRestrictionsBasicTest extends UserRestrictionsTestBase {

  /**
   * ID (machine name) of restriction rule used in this test.
   *
   * @var string
   */
  protected $id = 'test_rule_1';

  /**
   * Label used in rule.
   *
   * @var string
   */
  protected $label = 'Test rule #1';

  /**
   * ID of used restriction type plugin.
   *
   * @var string
   */
  protected $type = 'name';

  /**
   * Ensure the restriction exists in the database.
   */
  public function testUserRestrictionsRecordExists() {
    $restriction = $this->storage->load($this->id);
    $this->assertTrue($restriction, 'User restriction exists in the database');
    $this->assertEqual($restriction->label(), $this->label, 'User restriction name matches');
    $this->assertEqual($restriction->getRuleType(), $this->type, 'User restriction type matches');
  }

}
