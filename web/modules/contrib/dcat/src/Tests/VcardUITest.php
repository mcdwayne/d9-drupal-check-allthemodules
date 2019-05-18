<?php

namespace Drupal\dcat\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group dcat
 */
class VcardUITest extends WebTestBase {

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
  }

  /**
   * Tests that the overview page loads with a 200 response.
   */
  public function testOverview() {
    $user = $this->drupalCreateUser(['access vcard overview']);
    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_vcard.collection'));
    $this->assertResponse(200);
  }

  /**
   * Test the vcard add form.
   */
  public function testIndividualAddForm() {
    $user = $this->drupalCreateUser([
      'add vcard entities',
      'view published vcard entities'
    ]);
    $name = $this->randomMachineName();
    $nickname = $this->randomMachineName();
    $tel = $this->randomMachineName();
    $edit = [
      'external_id[0][value]' => 'http://example.com/vcard',
      'name[0][value]' => $name,
      'nickname[0][value]' => $nickname,
      'email[0][value]' => 'test@example.com',
      'telephone[0][value]' => $tel,
    ];

    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_vcard.add_form', array('dcat_vcard_type' => 'individual')));

    // Required fields.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_vcard.add_form', array('dcat_vcard_type' => 'individual')), [], t('Save'));
    $this->assertText('vCard IRI field is required.');
    $this->assertText('Name field is required.');

    // Adding and viewing entity.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_vcard.add_form', array('dcat_vcard_type' => 'individual')), $edit, t('Save'));
    $this->assertText('Created the ' . $name . ' vCard.');
    $this->assertText($nickname);
    $this->assertText('test@example.com');
    $this->assertText($tel);
  }

  /**
   * Test the vcard add form.
   */
  public function testLocationAddForm() {
    $user = $this->drupalCreateUser([
      'add vcard entities',
      'view published vcard entities'
    ]);
    $name = $this->randomMachineName();
    $edit = [
      'external_id[0][value]' => 'http://example.com/vcard',
      'name[0][value]' => $name,
      'country[0][value]' => $this->randomString(),
      'locality[0][value]' => $this->randomString(),
      'postal_code[0][value]' => $this->randomString(),
      'region[0][value]' => $this->randomString(),
      'street_address[0][value]' => $this->randomString(),
    ];

    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_vcard.add_form', array('dcat_vcard_type' => 'location')));

    // Required fields.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_vcard.add_form', array('dcat_vcard_type' => 'location')), [], t('Save'));
    $this->assertText('vCard IRI field is required.');
    $this->assertText('Name field is required.');

    // Adding and viewing entity.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_vcard.add_form', array('dcat_vcard_type' => 'location')), $edit, t('Save'));
    $this->assertText('Created the ' . $name . ' vCard.');
  }

  /**
   * Test the vcard add form.
   */
  public function testOrganizationAddForm() {
    $user = $this->drupalCreateUser([
      'add vcard entities',
      'view published vcard entities'
    ]);
    $name = $this->randomMachineName();
    $tel = $this->randomMachineName();
    $edit = [
      'external_id[0][value]' => 'http://example.com/vcard',
      'name[0][value]' => $name,
      'email[0][value]' => 'test@example.com',
      'telephone[0][value]' => $tel,
    ];

    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_vcard.add_form', array('dcat_vcard_type' => 'organization')));

    // Required fields.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_vcard.add_form', array('dcat_vcard_type' => 'organization')), [], t('Save'));
    $this->assertText('vCard IRI field is required.');
    $this->assertText('Name field is required.');

    // Adding and viewing entity.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_vcard.add_form', array('dcat_vcard_type' => 'organization')), $edit, t('Save'));
    $this->assertText('Created the ' . $name . ' vCard.');
    $this->assertText('test@example.com');
    $this->assertText($tel);
  }

}
