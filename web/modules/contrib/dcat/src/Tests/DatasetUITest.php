<?php

namespace Drupal\dcat\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\dcat\Entity\DcatAgent;
use Drupal\dcat\Entity\DcatVcard;
use Drupal\taxonomy\Entity\Term;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group dcat
 */
class DatasetUITest extends WebTestBase {

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
    $user = $this->drupalCreateUser(['access dataset overview']);
    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_dataset.collection'));
    $this->assertResponse(200);
  }

  /**
   * Test the dataset add form.
   */
  public function testAddForm() {
    // Login.
    $user = $this->drupalCreateUser([
      'add dataset entities',
      'view published dataset entities',
      'view published agent entities',
      'view published vcard entities',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('entity.dcat_dataset.add_form'));

    // Create a publisher agent entity.
    $publisher_name = 'publisher_' . $this->randomMachineName();
    $publisher = DcatAgent::create([
      'external_id' => 'http://example.com/publisher',
      'name' => $publisher_name,
      'type' => 'Publisher',
    ]);
    $publisher->save();

    $theme_name = 'theme_' . $this->randomMachineName();
    $theme = Term::create([
      'name' => $theme_name,
      'vid' => 'dataset_theme',
    ]);
    $theme->save();

    // Create a contact point vcard entity.
    $contact_name = 'contact_' . $this->randomMachineName();
    $contact = DcatVcard::create([
      'type' => 'individual',
      'external_id' => 'http://example.com/contact',
      'name' => $contact_name,
    ]);
    $contact->save();

    // Prepare form.
    $dataset_name = $this->randomMachineName();
    $keyword = $this->randomMachineName();
    $edit = [
      'external_id[0][value]' => 'http://example.com/dataset',
      'name[0][value]' => $dataset_name,
      'description[0][value]' => $this->randomString(128),
      'publisher[form][entity_id]' => $publisher_name . ' (' . $publisher->id() . ')',
      'contact_point[form][entity_id]' => $contact_name . ' (' . $contact->id() . ')',
      'theme[0][target_id]' => $theme_name,
      'keyword[0][target_id]' => $keyword,
      'landing_page[0][uri]' => 'http://example.com/page',
    ];

    // Test required fields.
    $this->drupalPostForm(Url::fromRoute('entity.dcat_dataset.add_form'), [], t('Save'));
    $this->assertText('Dataset IRI field is required.');
    $this->assertText('Title field is required.');
    $this->assertText('Description field is required.');

    // Test adding and viewing entity.
    $this->drupalPostForm(NULL, [], t('Add existing agent'));
    $this->drupalPostForm(NULL, [], t('Add existing vcard'));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Created the ' . $dataset_name . ' Dataset.');
    $this->assertText('http://example.com/dataset');
    $this->assertText($publisher_name);
    $this->assertText($theme_name);
    $this->assertText($contact_name);
    $this->assertText($keyword);
    $this->assertText('http://example.com/page');
  }

}
