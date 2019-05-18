<?php

namespace Drupal\dcat\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\dcat\Entity\DcatDistribution;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group dcat
 */
class DistributionAccessTest extends WebTestBase {

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
      ->getAccessControlHandler('dcat_distribution');
  }

  /**
   * Tests the Distribution view access.
   */
  public function testView() {
    $distribution_published = DcatDistribution::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
    ]);
    $distribution_unpublished = DcatDistribution::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 0,
    ]);

    $this->assertFalse($this->accessHandler->access($distribution_published, 'view'), "Published Distribution can't be viewed");
    $this->assertFalse($this->accessHandler->access($distribution_unpublished, 'view'), "Unpublished Distribution can't be viewed");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($distribution_published, 'view', $user1), "Published Distribution can't be viewed");
    $this->assertFalse($this->accessHandler->access($distribution_unpublished, 'view', $user1), "Unpublished Distribution can't be viewed");

    $user2 = $this->drupalCreateUser(['view published distribution entities']);
    $this->assertTrue($this->accessHandler->access($distribution_published, 'view', $user2), "Published Distribution can be viewed");
    $this->assertFalse($this->accessHandler->access($distribution_unpublished, 'view', $user2), "Unpublished Distribution can't be viewed");

    $user3 = $this->drupalCreateUser(['view unpublished distribution entities']);
    $this->assertFalse($this->accessHandler->access($distribution_published, 'view', $user3), "Published Distribution can't be viewed");
    $this->assertTrue($this->accessHandler->access($distribution_unpublished, 'view', $user3), "Unpublished Distribution can be viewed");
  }

  /**
   * Tests the Distribution create access.
   */
  public function testCreate() {
    $this->assertFalse($this->accessHandler->createAccess(), "Distribution can't be created");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->createAccess(NULL, $user1), "Distribution can't be created");

    $user2 = $this->drupalCreateUser(['add distribution entities']);
    $this->assertTrue($this->accessHandler->createAccess(NULL, $user2), 'Distribution can be created');
  }

  /**
   * Tests the Distribution update access.
   */
  public function testUpdate() {
    $distribution = DcatDistribution::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $this->assertFalse($this->accessHandler->access($distribution, 'update'), "Distribution can't be updated");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($distribution, 'update', $user1), "Distribution can't be updated");

    $user2 = $this->drupalCreateUser(['edit distribution entities']);
    $this->assertTrue($this->accessHandler->access($distribution, 'update', $user2), "Distribution can be updated");
  }

  /**
   * Tests the Distribution delete access.
   */
  public function testDelete() {
    $distribution = DcatDistribution::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $this->assertFalse($this->accessHandler->access($distribution, 'delete'), "Distribution can't be deleted");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($distribution, 'delete', $user1), "Distribution can't be deleted");

    $user2 = $this->drupalCreateUser(['delete distribution entities']);
    $this->assertTrue($this->accessHandler->access($distribution, 'delete', $user2), "Distribution can be deleted");
  }

}
