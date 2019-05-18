<?php

namespace Drupal\Tests\linky\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\linky\Entity\Linky;

/**
 * Tests linky functionality.
 *
 * @group linky
 */
class LinkyFunctionalTest extends JavascriptTestBase {

  /**
   * Screenshot counter.
   *
   * @var int
   */
  protected $screenshotCounter = 0;

  /**
   * Enter key code.
   */
  const ENTER_KEY = 13;

  /**
   * Escape key code.
   */
  const ESCAPE_KEY = 27;

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Test links.
   *
   * @var \Drupal\linky\LinkyInterface[]
   */
  protected $links = [];

  /**
   * Test entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $testEntity;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'link',
    'linky',
    'user',
    'dynamic_entity_reference',
    'field_ui',
    'entity_test',
    'views',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'view test entity',
    'administer entity_test fields',
    'administer entity_test display',
    'administer entity_test form display',
    'administer entity_test content',
    'add linky entities',
    'edit linky entities',
    'view linky entities',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    // Test admin user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    // A test link.
    $link = Linky::create([
      'link' => [
        'uri' => 'http://example.com',
        'title' => 'This amazing site',
      ],
    ]);
    $link->save();
    $this->links[] = $link;
    // Another test link.
    $link2 = Linky::create([
      'link' => [
        'uri' => 'http://exhample.com',
        'title' => 'This hammy site',
      ],
    ]);
    $link2->save();
    $this->links[] = $link2;
    // Test entity.
    $this->testEntity = EntityTest::create([
      'name' => $this->randomMachineName(5),
      'type' => 'entity_test',
    ]);
    $this->testEntity->save();
  }

  /**
   * Tests Linky widget.
   */
  public function testLinkyWidget() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Add EntityTestBundle for EntityTestWithBundle.
    EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ])->save();
    $this->drupalLogin($this->adminUser);

    // Add a new dynamic entity reference field.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $edit = [
      'label' => 'Linky list',
      'field_name' => 'linky',
      'new_storage_type' => 'dynamic_entity_reference',
    ];
    $this->submitForm($edit, t('Save and continue'), 'field-ui-field-storage-add-form');
    $entity_type_ids_select = $assert_session->selectExists('settings[entity_type_ids][]', $page);
    $entity_type_ids_select->selectOption('linky');
    $entity_type_ids_select->selectOption('entity_test', TRUE);
    $assert_session->selectExists('cardinality', $page)
      ->selectOption(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $page->uncheckField('settings[exclude_entity_types]');
    $this->submitForm([], t('Save field settings'), 'field-storage-config-edit-form');
    $page->checkField('settings[entity_test][handler_settings][target_bundles][entity_test]');
    $assert_session->assertWaitOnAjaxRequest();
    $page->checkField('settings[linky][handler_settings][auto_create]');
    $this->submitForm([], t('Save settings'), 'field-config-edit-form');
    $assert_session->pageTextContains('Saved Linky list configuration');
    $this->drupalGet('entity_test/structure/entity_test/form-display');
    // We can't use ::submitForm here because of AJAX.
    $assert_session->fieldExists('fields[field_linky][type]')->selectOption('linky');
    // Wait for AJAX.
    $assert_session->assertWaitOnAjaxRequest();
    $page->findButton('Save')->click();
    $this->drupalGet('entity_test/structure/entity_test/display');
    $assert_session->fieldExists('fields[field_linky][type]')->selectOption('linky_label');
    $assert_session->assertWaitOnAjaxRequest();
    $page->findButton('Save')->click();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    // Test adding field values.
    $this->drupalGet('entity_test/add');

    // On the first field, we change it to a linky entity type.
    $target_type_select = $assert_session->selectExists('field_linky[0][target_type]');
    $target_type_select->selectOption('entity_test');
    // Wait for dom.
    $this->assertTitleFieldInvisible();
    $target_type_select->selectOption('linky');
    // Now we've selected linky as target, field should be visible.
    $this->assertTitleFieldVisible();

    // Add another item and ensure the title field is still visible.
    $button = $page->findButton('Add another item');
    $button->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTitleFieldVisible();
    // Add a third option.
    $button->click();
    $assert_session->assertWaitOnAjaxRequest();

    $autocomplete_field = $page->findField('field_linky[0][target_id]');
    // Search on link title.
    $this->performAutocompleteQuery('This amazing site', $autocomplete_field);
    // The autocomplete is open, so the field label should be present.
    $assert_session->pageTextContains($this->links[0]->label());
    $this->clearAutocomplete($autocomplete_field);
    // Search on link uri.
    $this->performAutocompleteQuery('http://example.com', $autocomplete_field);
    // The autocomplete is open, so the field label should be present.
    $assert_session->pageTextContains($this->links[0]->label());
    // Now if we select the element, the link title should hidden.
    $this->selectAutocompleteOption();
    // Wait for dom.
    $this->assertTitleFieldInvisible();
    $this->assertEquals('This amazing site (http://example.com) (' . $this->links[0]->id() . ')', $autocomplete_field->getValue());

    // Now lets populate the second one with another entity.
    $target_type_select_1 = $assert_session->selectExists('field_linky[1][target_type]');
    $target_type_select_1->selectOption('entity_test');
    $autocomplete_field_1 = $page->findField('field_linky[1][target_id]');
    $autocomplete_field_1->setValue($this->testEntity->label());

    // For the third, we're going to use the autocreate function here.
    $target_type_select_2 = $assert_session->selectExists('field_linky[2][target_type]');
    $target_type_select_2->selectOption('linky');
    $autocomplete_field_2 = $page->findField('field_linky[2][target_id]');
    $this->performAutocompleteQuery('http://exhample.com', $autocomplete_field_2);
    // We don't select from the list here. But add a new one, with a new title.
    $linky_title_2 = $assert_session->fieldExists('field_linky[2][linky][linky_title]');
    $linky_title_2->setValue('Who likes ham');
    $page->findButton('Save')->click();

    // Ensure once entity is saved adding another item does not display the
    // title field for existing values.
    $button = $page->findButton('Add another item');
    $button->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTitleFieldInvisible();

    preg_match('|entity_test/manage/(\d+)|', $this->getSession()->getCurrentUrl(), $match);
    $id = $match[1];
    $assert_session->elementTextContains('css', '.messages', sprintf('entity_test %s has been created.', $id));

    // Check new entity was created and saved correctly.
    $test_entity = EntityTest::load($id);
    $link = $test_entity->field_linky->get(2)->entity;
    $this->assertEquals('http://exhample.com', $link->link->uri);
    $this->assertEquals('Who likes ham', $link->link->title);

    // Test allow_duplicate_urls disabled.
    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $assert_session->buttonExists('field_linky_settings_edit')->press();
    // Wait for AJAX.
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('fields[field_linky][settings_edit_form][settings][allow_duplicate_urls]')->uncheck();
    $assert_session->buttonExists('field_linky_plugin_settings_update')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $page->findButton('Save')->click();

    $this->drupalGet('entity_test/manage/' . $id . '/edit');
    $target_type_select_3 = $assert_session->selectExists('field_linky[3][target_type]');
    $target_type_select_3->selectOption('linky');
    $assert_session->assertWaitOnAjaxRequest();
    $autocomplete_field_3 = $page->findField('field_linky[3][target_id]');
    $this->performAutocompleteQuery('http://example.com', $autocomplete_field_3);
    // Don't choose from the dropdown, and set a different title to ensure
    // it uses the existing entity.
    $linky_title_3 = $assert_session->fieldExists('field_linky[3][linky][linky_title]');
    $linky_title_3->setValue('This awful site');
    $page->findButton('Save')->click();

    \Drupal::entityTypeManager()->getStorage('entity_test')->resetCache([$id]);
    $test_entity = EntityTest::load($id);
    $link = $test_entity->field_linky->get(3)->entity;
    $this->assertEquals('http://example.com', $link->link->uri);
    $this->assertEquals('This amazing site', $link->link->title);

    // Test autocomplete validation.
    $target_type_select_4 = $assert_session->selectExists('field_linky[4][target_type]');
    $target_type_select_4->selectOption('linky');
    $assert_session->assertWaitOnAjaxRequest();
    // Validation should fail with no title.
    $page->findField('field_linky[4][target_id]')->setValue('notavalidurl.com');
    $page->findButton('Save')->click();
    $assert_session->elementTextContains('css', '.messages', 'You must provide a title.');
    // Validation should fail with invalid url.
    $assert_session->fieldExists('field_linky[4][linky][linky_title]')->setValue('This invalid site');
    $page->findButton('Save')->click();
    $assert_session->elementTextContains('css', '.messages', 'You have entered an invalid URL. Please enter an external URL.');
    // Validation should fail with invalid entity.
    $page->findField('field_linky[4][target_id]')->setValue('I do not exist (123)');
    $page->findButton('Save')->click();
    $assert_session->elementTextContains('css', '.messages', 'The referenced entity (linky: 123) does not exist.');

    // Test field formatter, default is to use the link title as the link text.
    $this->drupalGet($test_entity->toUrl());
    $assert_session->linkExists('This amazing site');
    $assert_session->linkByHrefExists('http://example.com');
    $assert_session->linkExists('Who likes ham');
    $assert_session->linkByHrefExists('http://exhample.com');
    // Set the parent_entity_label_link_text option.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $assert_session->buttonExists('field_linky_settings_edit')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $page->checkField('fields[field_linky][settings_edit_form][settings][parent_entity_label_link_text]');
    $assert_session->buttonExists('field_linky_plugin_settings_update')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $page->findButton('Save')->click();
    Cache::invalidateTags(['entity_test:' . $id]);
    $this->drupalGet($test_entity->toUrl());
    $assert_session->linkExists($test_entity->label());
    // Disable the output as link option.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $assert_session->buttonExists('field_linky_settings_edit')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $page->uncheckField('fields[field_linky][settings_edit_form][settings][link]');
    $assert_session->buttonExists('field_linky_plugin_settings_update')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $page->findButton('Save')->click();
    Cache::invalidateTags(['entity_test:' . $id]);
    $this->drupalGet($test_entity->toUrl());
    $assert_session->pageTextContains('This amazing site');
    $assert_session->linkNotExists('This amazing site');
  }

  /**
   * Peforms an autocomplete query on an element.
   *
   * @param string $autocomplete_query
   *   String to search for.
   * @param \Behat\Mink\Element\NodeElement $autocomplete_field
   *   Field to search in.
   */
  protected function performAutocompleteQuery($autocomplete_query, NodeElement $autocomplete_field) {
    foreach (str_split($autocomplete_query) as $char) {
      // Autocomplete uses keydown/up directly.
      $autocomplete_field->keyDown($char);
      $autocomplete_field->keyUp($char);
    }
    // Wait for ajax.
    $this->assertJsCondition('(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\'.ui-autocomplete-loading\').length))', 20000);
    // And autocomplete selection.
    $this->assertJsCondition('jQuery(".ui-autocomplete.ui-menu li.ui-menu-item:visible").length > 0', 5000);
    $this->screenshotOutput();
  }

  /**
   * Clears an autocomplete field.
   *
   * @param \Behat\Mink\Element\NodeElement $autocomplete_field
   *   Field to clear.
   */
  protected function clearAutocomplete(NodeElement $autocomplete_field) {
    // Clear previous autocomplete.
    $autocomplete_field->setValue('');
    $autocomplete_field->keyDown(self::ESCAPE_KEY);
  }

  /**
   * Selects the autocomplete result with the given delta.
   *
   * @param int $delta
   *   Delta of item to select. Starts from 0.
   */
  protected function selectAutocompleteOption($delta = 0) {
    // Press the down arrow to select the nth option.
    /** @var \Behat\Mink\Element\NodeElement $element */
    $element = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete.ui-menu li.ui-menu-item')[$delta];
    $element->click();
    sleep(1);
    $this->screenshotOutput();
  }

  /**
   * Creates a screenshot.
   *
   * @return string
   *   Filename.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *   When operation not supported by the driver.
   * @throws \Behat\Mink\Exception\DriverException
   *   When the operation cannot be done.
   */
  protected function createScreenshot($filename, $set_background_color = TRUE) {
    $session = $this->getSession();
    $filename = $this->htmlOutputDirectory . '/screenshot-' . $this->htmlOutputTestId . '-' . $this->screenshotCounter . '.jpg';
    $this->screenshotCounter++;
    $session->executeScript("document.body.style.backgroundColor = 'white';");
    $image = $session->getScreenshot();
    file_put_contents($filename, $image);
    return $filename;
  }

  /**
   * Embed and create a screenshot.
   */
  protected function screenshotOutput() {
    $filename = $this->createScreenshot('');
    $this->htmlOutput('<html><title>Screenshot</title><body><img src="/sites/simpletest/browser_output/' . basename($filename) . '" /></body></html>');
  }

  /**
   * Asserts the title field is visible.
   *
   * @param int $delta
   *   The field delta to check.
   */
  protected function assertTitleFieldVisible($delta = 0) {
    $this->assertJsCondition("jQuery('.linky__title:not(\".invisible\") input[name=\"field_linky[$delta][linky][linky_title]\"]').length", 1000);
  }

  /**
   * Asserts the title field is invisible.
   *
   * @param int $delta
   *   The field delta to check.
   */
  protected function assertTitleFieldInvisible($delta = 0) {
    $this->assertJsCondition("jQuery('.linky__title.invisible input[name=\"field_linky[$delta][linky][linky_title]\"]').length", 1000);
  }

}
