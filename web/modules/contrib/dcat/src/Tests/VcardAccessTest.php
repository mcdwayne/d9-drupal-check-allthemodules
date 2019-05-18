<?php

namespace Drupal\dcat\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\dcat\Entity\DcatVcard;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group dcat
 */
class VcardAccessTest extends WebTestBase {

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
      ->getAccessControlHandler('dcat_vcard');
  }

  /**
   * Tests the Vcard view access.
   */
  public function testView() {
    $vcard_published = DcatVcard::create([
      'type' => 'individual',
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
    ]);
    $vcard_unpublished = DcatVcard::create([
      'type' => 'individual',
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 0,
    ]);

    $this->assertFalse($this->accessHandler->access($vcard_published, 'view'), "Published Vcard can't be viewed");
    $this->assertFalse($this->accessHandler->access($vcard_unpublished, 'view'), "Unpublished Vcard can't be viewed");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($vcard_published, 'view', $user1), "Published Vcard can't be viewed");
    $this->assertFalse($this->accessHandler->access($vcard_unpublished, 'view', $user1), "Unpublished Vcard can't be viewed");

    $user2 = $this->drupalCreateUser(['view published vcard entities']);
    $this->assertTrue($this->accessHandler->access($vcard_published, 'view', $user2), "Published Vcard can be viewed");
    $this->assertFalse($this->accessHandler->access($vcard_unpublished, 'view', $user2), "Unpublished Vcard can't be viewed");

    $user3 = $this->drupalCreateUser(['view unpublished vcard entities']);
    $this->assertFalse($this->accessHandler->access($vcard_published, 'view', $user3), "Published Vcard can't be viewed");
    $this->assertTrue($this->accessHandler->access($vcard_unpublished, 'view', $user3), "Unpublished Vcard can be viewed");
  }

  /**
   * Tests the Vcard create access.
   */
  public function testCreate() {
    $this->assertFalse($this->accessHandler->createAccess(), "Vcard can't be created");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->createAccess(NULL, $user1), "Vcard can't be created");

    $user2 = $this->drupalCreateUser(['add vcard entities']);
    $this->assertTrue($this->accessHandler->createAccess(NULL, $user2), 'Vcard can be created');
  }

  /**
   * Tests the Vcard update access.
   */
  public function testUpdate() {
    $vcard = DcatVcard::create([
      'type' => 'individual',
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $this->assertFalse($this->accessHandler->access($vcard, 'update'), "Vcard can't be updated");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($vcard, 'update', $user1), "Vcard can't be updated");

    $user2 = $this->drupalCreateUser(['edit vcard entities']);
    $this->assertTrue($this->accessHandler->access($vcard, 'update', $user2), "Vcard can be updated");
  }

  /**
   * Tests the Vcard delete access.
   */
  public function testDelete() {
    $vcard = DcatVcard::create([
      'type' => 'individual',
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $this->assertFalse($this->accessHandler->access($vcard, 'delete'), "Vcard can't be deleted");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($vcard, 'delete', $user1), "Vcard can't be deleted");

    $user2 = $this->drupalCreateUser(['delete vcard entities']);
    $this->assertTrue($this->accessHandler->access($vcard, 'delete', $user2), "Vcard can be deleted");
  }

}
