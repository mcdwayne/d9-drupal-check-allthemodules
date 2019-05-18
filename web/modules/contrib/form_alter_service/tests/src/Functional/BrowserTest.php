<?php

namespace Drupal\Tests\form_alter_service\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the modifications that were made by alters.
 *
 * @group form_alter_service
 */
class BrowserTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'form_alter_service_test',
    'views',
    'node',
  ];

  /**
   * An instance of the "string_translation" service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * A web-assert object.
   *
   * @var \Drupal\Tests\WebAssert
   */
  protected $assert;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    $this->translation = $this->container->get('string_translation');
    $this->assert = $this->assertSession();
  }

  /**
   * Tests that arguments from build info passed to the "alterForm()" method.
   *
   * @see \Drupal\form_alter_service_test\UsersListFormAlterTest::alterForm()
   */
  public function testArgumentsExpansion() {
    $this->drupalGet('admin/people');

    $this->assert->statusCodeEquals(200);
    $this->assert->responseContains($this->translation->translate('Test title 2'));
  }

  /**
   * Tests that ordering of handlers execution is appropriate.
   */
  public function testHandlersPrioritisation() {
    list(, $minor) = explode('.', \Drupal::VERSION, 3);

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    $this->drupalPostForm(
      'node/' . $this->drupalCreateNode()->id() . '/edit',
      ['title[0][value]' => 'Test'],
      // The name of the button has changed since Drupal 8.4.x.
      /* @link https://www.drupal.org/node/2068063 */
      $this->translation->translate($minor < 4 ? 'Save and keep published' : 'Save')
    );

    /* @see \Drupal\form_alter_service_test\NodeFormAlterTest::alterForm() */
    $this->assert->responseContains('validate1:validateFirst|validate2:validateSecond|validate3:validateThird');
    // The "hasMatch()" method of alter, producing this text, returns "FALSE"
    // so we must not see it.
    /* @see \Drupal\form_alter_service_test\NodeFormAlter2Test::alterForm() */
    $this->assert->responseNotContains('NOBODY CAN STOP ME!');
  }

}
