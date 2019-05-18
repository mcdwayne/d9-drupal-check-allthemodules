<?php

namespace Drupal\dcat\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\dcat\Entity\DcatDataset;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group dcat
 */
class DatasetAccessTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dcat'];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('dcat_dataset');
  }

  /**
   * Tests the Dataset view access.
   */
  public function testView() {
    $dataset_published = DcatDataset::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
    ]);
    $dataset_unpublished = DcatDataset::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 0,
    ]);

    $this->assertFalse($this->accessHandler->access($dataset_published, 'view'), "Published Dataset can't be viewed");
    $this->assertFalse($this->accessHandler->access($dataset_unpublished, 'view'), "Unpublished Dataset can't be viewed");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($dataset_published, 'view', $user1), "Published Dataset can't be viewed");
    $this->assertFalse($this->accessHandler->access($dataset_unpublished, 'view', $user1), "Unpublished Dataset can't be viewed");

    $user2 = $this->drupalCreateUser(['view published dataset entities']);
    $this->assertTrue($this->accessHandler->access($dataset_published, 'view', $user2), "Published Dataset can be viewed");
    $this->assertFalse($this->accessHandler->access($dataset_unpublished, 'view', $user2), "Unpublished Dataset can't be viewed");

    $user3 = $this->drupalCreateUser(['view unpublished dataset entities']);
    $this->assertFalse($this->accessHandler->access($dataset_published, 'view', $user3), "Published Dataset can't be viewed");
    $this->assertTrue($this->accessHandler->access($dataset_unpublished, 'view', $user3), "Unpublished Dataset can be viewed");
  }

  /**
   * Tests the Dataset create access.
   */
  public function testCreate() {
    $this->assertFalse($this->accessHandler->createAccess(), "Dataset can't be created");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->createAccess(NULL, $user1), "Dataset can't be created");

    $user2 = $this->drupalCreateUser(['add dataset entities']);
    $this->assertTrue($this->accessHandler->createAccess(NULL, $user2), 'Dataset can be created');
  }

  /**
   * Tests the Dataset update access.
   */
  public function testUpdate() {
    $dataset = DcatDataset::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $this->assertFalse($this->accessHandler->access($dataset, 'update'), "Dataset can't be updated");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($dataset, 'update', $user1), "Dataset can't be updated");

    $user2 = $this->drupalCreateUser(['edit dataset entities']);
    $this->assertTrue($this->accessHandler->access($dataset, 'update', $user2), "Dataset can be updated");
  }

  /**
   * Tests the Dataset delete access.
   */
  public function testDelete() {
    $dataset = DcatDataset::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $this->assertFalse($this->accessHandler->access($dataset, 'delete'), "Dataset can't be deleted");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($dataset, 'delete', $user1), "Dataset can't be deleted");

    $user2 = $this->drupalCreateUser(['delete dataset entities']);
    $this->assertTrue($this->accessHandler->access($dataset, 'delete', $user2), "Dataset can be deleted");
  }

}
