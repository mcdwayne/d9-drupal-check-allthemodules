<?php

namespace Drupal\Tests\crm_core_user_sync\Kernel;

use Drupal\crm_core_user_sync\Entity\Relation;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the relation entity class.
 *
 * @group crm_core_user_sync
 */
class RelationEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'name',
    'crm_core_contact',
    'crm_core_user_sync',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('crm_core_user_sync_relation');
  }

  /**
   * Tests some of the methods.
   *
   * @see \Drupal\crm_core_user_sync\Entity\Relation::setUserId()
   * @see \Drupal\crm_core_user_sync\Entity\Relation::setIndividualId()
   * @see \Drupal\crm_core_user_sync\Entity\Relation::getUserId()
   * @see \Drupal\crm_core_user_sync\Entity\Relation::getIndividualId()
   */
  public function testRelationMethods() {
    $user_id_1 = 1;
    $individual_id_1 = 1;
    $user_id_2 = 2;
    $individual_id_2 = 2;

    $relation = Relation::create([
      'user_id' => $user_id_1,
      'individual_id' => $individual_id_1,
    ]);
    $this->assertEquals($user_id_1, $relation->getUserId(), 'User ID match');
    $this->assertEquals($individual_id_1, $relation->getIndividualId(), 'Individual ID match');

    $relation->setUserId($user_id_2);
    $this->assertEquals($user_id_2, $relation->getUserId(), 'User ID match');

    $relation->setIndividualId($individual_id_2);
    $this->assertEquals($individual_id_2, $relation->getIndividualId(), 'Individual ID match');
  }

}
