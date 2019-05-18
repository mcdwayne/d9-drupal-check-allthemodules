<?php

namespace Drupal\Tests\search_365\Functional;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\ConfigFormTestBase;
use Drupal\search_365\Form\AdministrationForm;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a base class for search 365 tests.
 *
 * @group search_365
 */
class Search365AdminFormTest extends ConfigFormTestBase {

  use UserCreationTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'search_365',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['system']);
    $this->adminUser = $this->createUser([
      'administer search 365',
      'access search 365 content',
    ]);

    $this->setCurrentUser($this->adminUser);

    $this->form = AdministrationForm::create($this->container);
    $this->values = [
      'baseurl' => [
        '#value' => 'http://example.com',
        '#config_name' => 'search_365.settings',
        '#config_key' => 'connection_info.baseurl',
      ],
      'collection' => [
        '#value' => $this->randomString(10),
        '#config_name' => 'search_365.settings',
        '#config_key' => 'connection_info.collection',
      ],
      'drupal_path' => [
        '#value' => $this->randomString(10),
        '#config_name' => 'search_365.settings',
        '#config_key' => 'display_settings.drupal_path',
      ],
      'page_size' => [
        '#value' => rand(1, 100),
        '#config_name' => 'search_365.settings',
        '#config_key' => 'display_settings.page_size',
      ],
    ];
  }

  /**
   * Test base url field doesn't contain trailing slashes.
   */
  public function testBaseUrlValidation() {
    // Create a base url with an invalid trailing slash.
    $this->values['baseurl']['#value'] = 'http://example.com/';
    // Programmatically submit the given values.
    $values = [];
    foreach ($this->values as $form_key => $data) {
      $values[$form_key] = $data['#value'];
    }
    $form_state = (new FormState())->setValues($values);
    \Drupal::formBuilder()->submitForm($this->form, $form_state);

    // Check that the form returns an error when expected.
    $errors = $form_state->getErrors();

    $this->assertCount(1, $errors);

    $error = $errors['connection_info][baseurl'];

    $this->assertEquals("Base URL must not end with a slash", (string) $error);
  }

}
