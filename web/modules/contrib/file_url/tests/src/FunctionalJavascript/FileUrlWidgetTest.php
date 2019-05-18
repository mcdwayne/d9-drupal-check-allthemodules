<?php

namespace Drupal\Tests\file_url\FunctionalJavascript;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the file URL widget.
 *
 * @group file_url
 */
class FileUrlWidgetTest extends JavascriptTestBase {

  use TestFileCreationTrait;

  /**
   * Test files.
   *
   * @var array[]
   */
  protected $files;

  /**
   * Field test cases.
   *
   * The key is the field name, the value is the field cardinality.
   *
   * @var array
   */
  protected static $fields = [
    'single' => 1,
    'multiple' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'file_url',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create two file URL fields.
    $display = EntityFormDisplay::load('entity_test.entity_test.default');
    foreach (static::$fields as $field_name => $cardinality) {
      FieldStorageConfig::create([
        'type' => 'file_url',
        'entity_type' => 'entity_test',
        'field_name' => $field_name,
        'cardinality' => $cardinality,
      ])->save();
      FieldConfig::create([
        'entity_type' => 'entity_test',
        'bundle' => 'entity_test',
        'field_type' => 'file_url',
        'field_name' => $field_name,
        'label' => $field_name,
      ])->save();
      $display->setComponent($field_name, [
        'type' => 'file_url_generic',
        'region' => 'content',
      ]);
    }
    $display->save();

    // Generate some files for upload test.
    $this->files = $this->getTestFiles('text');

    $this->drupalLogin($this->createUser(['administer entity_test content']));
  }

  /**
   * Tests the file URL widget.
   */
  public function testFileUrlWidget() {
    // Add a new entity.
    $this->drupalGet('/entity_test/add');
    $page = $this->getSession()->getPage();

    // Upload a file to the 'single' file URL field and save.
    $this->addFileUrlItem('single', 'upload', 0);
    $page->pressButton('Save');
    // Remove the file.
    $this->removeFileUrlItem('single');
    // Check that the file mode selector is shown.
    $this->assertSession()->fieldExists('single[0][file_url_type]');
    // Add a remote URL.
    $url1 = $this->randomUrl();
    $this->addFileUrlItem('single', 'remote', $url1);
    $page->pressButton('Save');
    // Check that the link is still there after saving the form.
    $this->assertSession()->linkExists($url1);

    // Upload two files to the 'multiple' file URL.
    $this->addFileUrlItem('multiple', 'upload', 1);
    $this->addFileUrlItem('multiple', 'upload', 2);

    // Append a remote URL.
    $url2 = $this->randomUrl();
    $this->addFileUrlItem('multiple', 'remote', $url2);
    // Check that file URL items are in the correct order.
    $this->assertOrderInPageText([$this->files[1]->filename, $this->files[2]->filename, $url2]);

    // Swap the two items from top.
    $dragged = $this->xpath("//details[@data-drupal-selector='edit-multiple']//table//tr[1]//a[@class='tabledrag-handle']")[0];
    $target = $this->xpath("//details[@data-drupal-selector='edit-multiple']//table//tr[2]//a[@class='tabledrag-handle']")[0];
    $dragged->dragTo($target);
    // Check that file URL items are in the correct order after reorder.
    $this->assertOrderInPageText([$this->files[2]->filename, $this->files[1]->filename, $url2]);

    // Swap row 1 with row 2. The remote URL should be in the middle.
    $dragged = $this->xpath("//details[@data-drupal-selector='edit-multiple']//table//tr[2]//a[@class='tabledrag-handle']")[0];
    $target = $this->xpath("//details[@data-drupal-selector='edit-multiple']//table//tr[3]//a[@class='tabledrag-handle']")[0];
    $dragged->dragTo($target);
    // Check that file URL items are in the correct order after reorder.
    $this->assertOrderInPageText([$this->files[2]->filename, $url2, $this->files[1]->filename]);

    // Check that the order is preserved after save.
    $page->pressButton('Save');
    $this->assertOrderInPageText([$this->files[2]->filename, $url2, $this->files[1]->filename]);

    // Append an additional remote URL.
    $url3 = $this->randomUrl();
    $this->addFileUrlItem('multiple', 'remote', $url3);
    $this->assertOrderInPageText([$this->files[2]->filename, $url2, $this->files[1]->filename, $url3]);
    // Check that the order is preserved after save.
    $page->pressButton('Save');
    $this->assertOrderInPageText([$this->files[2]->filename, $url2, $this->files[1]->filename, $url3]);

    // Test handling of invalid remote URLs.
    $this->drupalGet('/entity_test/add');
    $url4 = $this->randomUrl();
    // Add a valid URL.
    $this->addFileUrlItem('multiple', 'remote', $url4);
    // Add an invalid URL.
    $url5 = 'invalid url';
    $this->addFileUrlItem('multiple', 'remote', $url5, FALSE);
    // Check that the proper validation error has been displayed.
    $this->assertSession()->pageTextContains("The URL $url5 is not valid.");
  }

  /**
   * Selects a radio button option from a file URL field.
   *
   * @param string $field_name
   *   The file URL field name.
   * @param string $file_mode
   *   The radio button option value with the file mode ('upload', 'remote').
   * @param int|string $value
   *   Either the index of the test file to upload or the remote URL.
   * @param bool $check_presence
   *   (optional) After creating the item, check also its presence. Defaults to
   *   TRUE.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If the field doesn't exist.
   */
  protected function addFileUrlItem($field_name, $file_mode, $value, $check_presence = TRUE) {
    $session = $this->getSession();
    $page = $session->getPage();

    // Narrow the search to the field's wrapper.
    $wrapper = $page->find('xpath', "//div[@data-drupal-selector='edit-{$field_name}-wrapper']");
    if (!$wrapper) {
      throw new ElementNotFoundException($session, $field_name);
    }

    /** @var \Behat\Mink\Element\NodeElement $radio */
    $radio = $wrapper->find('xpath', "//input[@type='radio' and @value='$file_mode']");
    if (!$radio || $radio->getTagName() !== 'input' || $radio->getAttribute('type') !== 'radio') {
      throw new ElementNotFoundException($session, $field_name);
    }

    // Select the file mode.
    $radio->click();

    if ($file_mode === 'upload') {
      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = $this->container->get('file_system');
      $wrapper->attachFileToField('Choose a file', $file_system->realpath($this->files[$value]->uri));
      // Wait for ajax to finish the upload.
      $this->assertSession()->assertWaitOnAjaxRequest();
      if ($check_presence) {
        // Check that the uploaded file has been added to the field item list.
        $this->assertSession()->linkExists($this->files[$value]->filename);
      }
    }
    elseif ($file_mode === 'remote') {
      $wrapper->fillField('Remote URL', $value);
      // Wait for ajax to finish the job.
      $this->assertSession()->assertWaitOnAjaxRequest();
      if ($check_presence) {
        // Check that the remote URL has been added to the field item list.
        $this->assertSession()->linkExists($value);
      }
    }
  }

  /**
   * Removes a file URL field.
   *
   * @param string $field_name
   *   The file URL field name.
   * @param int $delta
   *   (optional) The delta of the item being removed. Defaults to 0.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If the field doesn't exist.
   */
  protected function removeFileUrlItem($field_name, $delta = 0) {
    $session = $this->getSession();
    $page = $session->getPage();

    // Narrow the search to the items's wrapper.
    $wrapper = $page->find('css', "div.form-item-{$field_name}-{$delta}");
    if (!$wrapper) {
      throw new ElementNotFoundException($session, $field_name);
    }
    $wrapper->pressButton('Remove');
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Generates a testing remote URL.
   *
   * @return string
   *   A random remote URL.
   */
  protected function randomUrl() {
    return 'http://example.com/' . $this->randomMachineName();
  }

  /**
   * Asserts that several pieces of text are in a given order in the page.
   *
   * @param string[] $items
   *   An ordered list of strings.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When any of the given string is not found.
   *
   * @todo Remove this method when https://www.drupal.org/node/2817657 lands.
   */
  protected function assertOrderInPageText(array $items) {
    $session = $this->getSession();
    $text = $session->getPage()->getText();
    $strings = $not_found = [];
    foreach ($items as $item) {
      if (($pos = strpos($text, $item)) === FALSE) {
        if (!in_array($item, $not_found)) {
          $not_found[] = $item;
        }
      }
      else {
        $strings[$pos] = $item;
      }
    }

    $quote_string_list = function (array $list) {
      return implode(', ', array_map(function ($string) {
        return "'$string'";
      }, $list));
    };

    if ($not_found) {
      $not_found = $quote_string_list($not_found);
      throw new ElementNotFoundException($session->getDriver(), "Cannot find item(s): $not_found.");
    }

    ksort($strings);
    $ordered = $quote_string_list($items);

    $this->assertTrue($items === array_values($strings), "Strings correctly ordered as: $ordered.");
  }

}
