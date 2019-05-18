<?php

namespace Drupal\Tests\gender\Functional;

use Drupal\Core\Url;

/**
 * Tests the default gender field widget.
 *
 * @group gender
 */
class GenderDefaultWidgetTest extends GenderTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'gender',
    'gender_test',
    'node',
    'user',
    'field_ui',
    'help',
  ];

  /**
   * Tests the checkbox widget.
   */
  public function testCheckboxWidget() {
    // Create the URL to the node edit page.
    $node_edit_url = Url::fromRoute('entity.node.edit_form', [
      'node' => $this->node->id(),
    ]);
    // Load the node edit page.
    $this->drupalGet($node_edit_url);
    $this->assertGenderCheckboxes();
    // Add a fourth option.
    $fourth_option = 'aporagender';
    // Select the fourth option.
    $page = $this->getSession()->getPage();
    $page->find('css', 'input[name="field_gender[' . $fourth_option . ']"]')->check();
    $this->genderList[] = $fourth_option;
    // Save the form.
    $page->findButton('Save')->click();
    $this->drupalGet($node_edit_url);
    $this->assertGenderCheckboxes();
  }

  /**
   * Repeated assertations for the field checkboxes.
   */
  protected function assertGenderCheckboxes() {
    $page = $this->getSession()->getPage();
    $gender_options = array_keys(gender_options());
    $checkbox_list = $page->findAll('css', 'input[name^=field_gender]');
    // Assert that the correct number of checkboxes are present.
    $this->assertEquals(count($gender_options), count($checkbox_list));
    /** @var \Behat\Mink\Element\NodeElement $checkbox */
    foreach ($checkbox_list as $checkbox) {
      // Assert that each checkbox has a value from the options list.
      $value = $checkbox->getAttribute('value');
      $this->assertTrue(in_array($value, $gender_options));
      // If the checkbox should be checked, assert that it is.
      if (in_array($value, $this->genderList)) {
        $this->assertTrue($checkbox->getValue());
      }
      // If the checkbox should not be checked, assert that it is not.
      else {
        $this->assertFalse($checkbox->getValue());
      }
    }
  }

}
