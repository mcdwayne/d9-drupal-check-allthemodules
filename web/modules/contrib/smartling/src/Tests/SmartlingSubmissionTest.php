<?php

/**
 * @file
 * Contains
 */

namespace Drupal\smartling\Tests;

use Drupal\simpletest\KernelTestBase;
use Drupal\smartling\Entity\SmartlingSubmission;

/**
 * Class SmartlingSubmissionTest
 * @package Drupal\smartling\Tests
 * @group smartling
 */
class SmartlingSubmissionTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field', 'options', 'smartling', 'entity_test');

  /**
   * The test values.
   *
   * @var array
   */
  protected $values;

  /**
   * The test entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityBase
   */
  protected $entity;

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The class name of the test class.
   *
   * @var string
   */
  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('user');
    $this->installEntitySchema('smartling_submission');

    // User create needs sequence table.
    $this->installSchema('system', array('sequences'));

    // Create a test user to use as the entity owner.
    $this->user = $this->container->get('entity_type.manager')->getStorage('user')->create([
      'name' => 'smartling_test_user',
      'mail' => 'foo@example.com',
      'pass' => '123456',
    ]);
    $this->user->save();

    // Create a test entity to serialize.
    $this->values = [
      'name' => $this->randomMachineName(),
      'user_id' => $this->user->id(),
    ];

    $this->entity = entity_create('entity_test_mulrev', $this->values);
    $this->entity->save();
  }

  public function testGenerateFileName() {
    $values = [
      'entity_id' => $this->entity->id(),
      'entity_type' => $this->entity->getEntityTypeId(),
      'entity_bundle' => $this->entity->bundle(),
      'title' => $this->entity->label(),
      'original_language' => $this->entity->language()->getId(),
      'target_language' => 'ru',
      'submitter' => $this->user->id(),
    ];

    $submission = entity_create('smartling_submission', $values);
    $submission->save();
    // @todo cover some other cases.
    $this->assertEqual($submission->generateFileName(), strtolower($this->entity->getEntityTypeId() . '.' .  $this->entity->id() . '.' . $this->entity->language()->getId() . '.xml'));
  }
  
  public function testGetFromDrupalEntity() {
    $submission = SmartlingSubmission::getFromDrupalEntity($this->entity, 'ru');
    $submission->save();
    $this->assertEqual($submission->entity_id->value, $this->entity->id());
    $this->assertEqual($submission->entity_type->value, $this->entity->getEntityTypeId());
    $this->assertEqual($submission->entity_bundle->value, $this->entity->bundle());
    $this->assertEqual($submission->title->value, $this->entity->label());
    $this->assertEqual($submission->original_language->value, $this->entity->language()->getId());
    $this->assertEqual($submission->target_language->value, 'ru');
    $this->assertEqual($submission->submitter->target_id, \Drupal::currentUser()->id());

    $this->assertNotNull($submission->changed->value);
    $this->assertNotNull($submission->created->value);
  }
  
  public function testLoadMultipleByConditions() {
    // Create a test entity to serialize.
    $entity_values = [
      [
        'name' => $this->randomMachineName(),
        'user_id' => $this->user->id(),
      ],
      [
        'name' => $this->randomMachineName(),
        'user_id' => $this->user->id(),
      ],
      [
        'name' => $this->randomMachineName(),
        'user_id' => $this->user->id(),
      ],
    ];

    foreach ($entity_values as $values) {
      $entity = entity_create('entity_test_mulrev', $values);
      $entity->save();
      $entities[] = $entity;
      $submission = SmartlingSubmission::getFromDrupalEntity($entity, 'ru');
      $submission->save();
      $submissions[] = $submission;
    }

    $this->assertEqual(3, count(SmartlingSubmission::loadMultipleByConditions(['entity_type' => 'entity_test_mulrev'])));
    $this->assertEqual(1, count(SmartlingSubmission::loadMultipleByConditions(['title' => $entity_values[0]['name']])));
    $this->assertEqual(1, count(SmartlingSubmission::loadMultipleByConditions(['entity_id' => 2])));
    $this->assertEqual(0, count(SmartlingSubmission::loadMultipleByConditions(['entity_id' => 12])));
  }
  
  public function testSetStatusByEvent() {
    
  }
  
  public function getFileName() {
    
  }
  
  public function getStatus() {
    
  }
  
  public function getRelatedEntity() {
    
  }
  
}
