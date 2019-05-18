<?php

namespace Drupal\Tests\owntracks\Functional;

use Drupal\owntracks\Entity\OwnTracksLocation;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Class OwnTracksAccessTest.
 *
 * @covers \Drupal\owntracks\Access\OwnTracksEntityAccessControlHandler
 * @covers \Drupal\owntracks\Access\OwnTracksUserMapAccess
 * @covers \Drupal\owntracks\Plugin\views\access\OwnTracks
 *
 * @group owntracks
 */
class OwnTracksAccessTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['owntracks'];

  /**
   * Tests owntracks access controllers and plugins.
   */
  public function testAccess() {
    // Create test accounts.
    $notAccount = $this->createUser();

    $ownAccount = $this->createUser([
      'create owntracks entities',
      'view own owntracks entities',
      'update own owntracks entities',
      'delete own owntracks entities',
    ]);

    $anyAccount = $this->createUser([
      'create owntracks entities',
      'view any owntracks entity',
      'update any owntracks entity',
      'delete any owntracks entity',
    ]);

    $admAccount = $this->createUser([
      'administer owntracks',
    ]);

    // Create test entities.
    $notEntity = OwnTracksLocation::create([
      'uid' => $notAccount->id(),
      'lat' => 7,
      'lon' => 53,
      'tst' => 123456,
    ]);
    $notEntity->save();

    $ownEntity = OwnTracksLocation::create([
      'uid' => $ownAccount->id(),
      'lat' => 7,
      'lon' => 53,
      'tst' => 123456,
    ]);
    $ownEntity->save();

    $anyEntity = OwnTracksLocation::create([
      'uid' => $anyAccount->id(),
      'lat' => 7,
      'lon' => 53,
      'tst' => 123456,
    ]);
    $anyEntity->save();

    $admEntity = OwnTracksLocation::create([
      'uid' => $admAccount->id(),
      'lat' => 7,
      'lon' => 53,
      'tst' => 123456,
    ]);
    $admEntity->save();

    // Create test urls.
    $createEntity = Url::fromRoute('entity.owntracks_location.add_form')
      ->setAbsolute(TRUE)->toString();

    $viewNotEntity = Url::fromRoute('entity.owntracks_location.canonical', ['owntracks_location' => $notEntity->id()])
      ->setAbsolute(TRUE)->toString();
    $updateNotEntity = Url::fromRoute('entity.owntracks_location.edit_form', ['owntracks_location' => $notEntity->id()])
      ->setAbsolute(TRUE)->toString();
    $deleteNotEntity = Url::fromRoute('entity.owntracks_location.delete_form', ['owntracks_location' => $notEntity->id()])
      ->setAbsolute(TRUE)->toString();

    $viewOwnEntity = Url::fromRoute('entity.owntracks_location.canonical', ['owntracks_location' => $ownEntity->id()])
      ->setAbsolute(TRUE)->toString();
    $updateOwnEntity = Url::fromRoute('entity.owntracks_location.edit_form', ['owntracks_location' => $ownEntity->id()])
      ->setAbsolute(TRUE)->toString();
    $deleteOwnEntity = Url::fromRoute('entity.owntracks_location.delete_form', ['owntracks_location' => $ownEntity->id()])
      ->setAbsolute(TRUE)->toString();

    $viewAnyEntity = Url::fromRoute('entity.owntracks_location.canonical', ['owntracks_location' => $anyEntity->id()])
      ->setAbsolute(TRUE)->toString();
    $updateAnyEntity = Url::fromRoute('entity.owntracks_location.edit_form', ['owntracks_location' => $anyEntity->id()])
      ->setAbsolute(TRUE)->toString();
    $deleteAnyEntity = Url::fromRoute('entity.owntracks_location.delete_form', ['owntracks_location' => $anyEntity->id()])
      ->setAbsolute(TRUE)->toString();

    $viewAdmEntity = Url::fromRoute('entity.owntracks_location.canonical', ['owntracks_location' => $admEntity->id()])
      ->setAbsolute(TRUE)->toString();
    $updateAdmEntity = Url::fromRoute('entity.owntracks_location.edit_form', ['owntracks_location' => $admEntity->id()])
      ->setAbsolute(TRUE)->toString();
    $deleteAdmEntity = Url::fromRoute('entity.owntracks_location.delete_form', ['owntracks_location' => $admEntity->id()])
      ->setAbsolute(TRUE)->toString();

    $notAccountMap = Url::fromRoute('owntracks.user_map', ['user' => $notAccount->id()])
      ->setAbsolute(TRUE)->toString();
    $notAccountView = Url::fromRoute('view.owntracks_location.current', ['user' => $notAccount->id()])
      ->setAbsolute(TRUE)->toString();

    $ownAccountMap = Url::fromRoute('owntracks.user_map', ['user' => $ownAccount->id()])
      ->setAbsolute(TRUE)->toString();
    $ownAccountView = Url::fromRoute('view.owntracks_location.current', ['user' => $ownAccount->id()])
      ->setAbsolute(TRUE)->toString();

    $anyAccountMap = Url::fromRoute('owntracks.user_map', ['user' => $anyAccount->id()])
      ->setAbsolute(TRUE)->toString();
    $anyAccountView = Url::fromRoute('view.owntracks_location.current', ['user' => $anyAccount->id()])
      ->setAbsolute(TRUE)->toString();

    $admAccountMap = Url::fromRoute('owntracks.user_map', ['user' => $admAccount->id()])
      ->setAbsolute(TRUE)->toString();
    $admAccountView = Url::fromRoute('view.owntracks_location.current', ['user' => $admAccount->id()])
      ->setAbsolute(TRUE)->toString();

    // Test access.
    $this->drupalLogin($notAccount);
    $this->drupalGet($createEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($viewNotEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($notEntity->access('view', $notAccount));
    $this->drupalGet($viewOwnEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($ownEntity->access('view', $notAccount));
    $this->drupalGet($updateNotEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($notEntity->access('update', $notAccount));
    $this->drupalGet($updateOwnEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($ownEntity->access('update', $notAccount));
    $this->drupalGet($deleteNotEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($notEntity->access('delete', $notAccount));
    $this->drupalGet($deleteOwnEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($ownEntity->access('delete', $notAccount));

    $this->drupalGet($notAccountMap);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ownAccountMap);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($notAccountView);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ownAccountView);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($ownAccount);
    $this->drupalGet($createEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($viewOwnEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($ownEntity->access('view', $ownAccount));
    $this->drupalGet($viewAnyEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($anyEntity->access('view', $ownAccount));
    $this->drupalGet($updateOwnEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($ownEntity->access('update', $ownAccount));
    $this->drupalGet($updateAnyEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($anyEntity->access('update', $ownAccount));
    $this->drupalGet($deleteOwnEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($ownEntity->access('delete', $ownAccount));
    $this->drupalGet($deleteAnyEntity);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertFalse($anyEntity->access('delete', $ownAccount));

    $this->drupalGet($ownAccountMap);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($anyAccountMap);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ownAccountView);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($anyAccountView);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($anyAccount);
    $this->drupalGet($createEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($viewAnyEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($anyEntity->access('view', $anyAccount));
    $this->drupalGet($viewAdmEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($admEntity->access('view', $anyAccount));
    $this->drupalGet($updateAnyEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($anyEntity->access('update', $anyAccount));
    $this->drupalGet($updateAdmEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($admEntity->access('update', $anyAccount));
    $this->drupalGet($deleteAnyEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($anyEntity->access('delete', $anyAccount));
    $this->drupalGet($deleteAdmEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($admEntity->access('delete', $anyAccount));

    $this->drupalGet($anyAccountMap);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($admAccountMap);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($anyAccountView);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($admAccountView);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($admAccount);
    $this->drupalGet($createEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($viewAnyEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($anyEntity->access('view', $admAccount));
    $this->drupalGet($viewAdmEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($admEntity->access('view', $admAccount));
    $this->drupalGet($updateAnyEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($anyEntity->access('update', $admAccount));
    $this->drupalGet($updateAdmEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($admEntity->access('update', $admAccount));
    $this->drupalGet($deleteAnyEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($anyEntity->access('delete', $admAccount));
    $this->drupalGet($deleteAdmEntity);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($admEntity->access('delete', $admAccount));

    $this->drupalGet($anyAccountMap);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($admAccountMap);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($anyAccountView);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($admAccountView);
    $this->assertSession()->statusCodeEquals(200);
  }

}
