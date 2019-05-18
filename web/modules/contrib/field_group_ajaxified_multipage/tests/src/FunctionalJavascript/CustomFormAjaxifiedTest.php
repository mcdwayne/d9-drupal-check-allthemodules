<?php

namespace Drupal\Tests\field_group_ajaxified_multipage\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;
use Drupal\Tests\field_group_ajaxified_multipage\Functional\FieldGroupAjaxifiedTrait;

/**
 * JavaScript tests.
 *
 * @ingroup field_group_ajaxified_multipage
 *
 * @group field_group_ajaxified_multipage
 */
class CustomFormAjaxifiedTest extends JavascriptTestBase {

  use FieldGroupTestTrait;
  use FieldGroupAjaxifiedTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field_test',
    'field_group',
    'field_group_ajaxified_multipage',
    'fgam_example',
  ];

  /**
   * Fields created.
   *
   * @var array
   */
  public $fields = [];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Node type.
   *
   * @var string
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Move this to a trait.
    $this->user = $this->drupalCreateUser([
      'administer content types',
      'bypass node access',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Test custom form.
   *
   * @TODO: Al retroceder no se guardan los campos.
   */
  public function testCustomForm() {
    $this->drupalGet('examples/field_group_ajaxified_multipage/custom_form');
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $assert->pageTextContains('Example Form');
    $assert->pageTextContains('Step 1 of 3 Identity');
    $assert->pageTextContains('Full Name');

    $page->fillField('First name', 'Name test');
    $page->fillField('Last name', 'Surname test');

    $page->pressButton('Next button label');

    $assert->assertWaitOnAjaxRequest();

    $assert->pageTextContains('Step 2 of 3 Contact');
    $assert->pageTextContains('Address');

    $page->fillField('Address', 'Direction test');
    $page->pressButton('Next button label');

    // Wait for ajax.
    $assert->assertWaitOnAjaxRequest();

    $assert->pageTextContains('Step 3 of 3 Description');
    $assert->pageTextContains('Description');

    $page->fillField('Description', 'Description test');
    $this->submitForm([], 'Submit');
    $assert->pageTextContains('The form has been submitted. name="Name test Surname test", address="Direction test", description="Description test"');
  }

}
