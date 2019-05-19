<?php

namespace Drupal\Tests\whitelabel\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\user\Entity\User;

/**
 * Tests field widgets and formatters.
 *
 * @group whitelabel
 */
class WhiteLabelFieldTest extends WhiteLabelJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'color',
    'entity_reference_revisions',
    'whitelabel_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Make sure everything is disabled by default.
    $this->config('whitelabel.settings')
      ->setData([
        'site_name' => TRUE,
        'site_name_display' => TRUE,
        'site_slogan' => TRUE,
        'site_logo' => TRUE,
        'site_theme' => TRUE,
        'site_colors' => TRUE,
      ])
      ->save();
  }

  /**
   * Test for the field widget.
   */
  public function testFieldWidget() {
    // Install Bartik.
    $this->container->get('theme_installer')->install(['bartik']);

    // Get Bartik's color fields.
    $theme_color_fields = color_get_palette('bartik', TRUE);

    // Create a white label field and attach it to a user.
    $field_name = 'field_whitelabel';
    $this->attachFieldToEntity('user', 'user', $field_name);

    // Create a new test file.
    $image_files = $this->drupalGetTestFiles('image');
    $file = File::create([
      'uri' => \Drupal::service('file_system')->realpath(current($image_files)->uri),
    ]);
    $file->setPermanent();
    $file->save();

    // Prepare array with new form values.
    $new_values = [
      'token' => $this->randomMachineName(),
      'name' => $this->randomString(),
      'slogan' => $this->randomString(),
    ];

    // Ensure there is no white label yet.
    $this->assertEmpty($this->whiteLabelOwner->{$field_name});

    // Visit the edit profile page.
    $this->drupalGet($this->whiteLabelOwner->url('edit-form'));
    $this->assertSession()->statusCodeEquals(200, "User page loads without errors.");
    $page = $this->getSession()->getPage();

    // Set new values for form fields.
    $page->fillField("{$field_name}[0][token][0][value]", $new_values['token']);
    $page->fillField("{$field_name}[0][name][0][value]", $new_values['name']);
    $page->fillField("{$field_name}[0][slogan][0][value]", $new_values['slogan']);
    $page->attachFileToField("files[{$field_name}_0_logo_0]", $file->getFileUri());
    // Allow the file to upload.
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Make the theme change with AJAX to have the color fields show up.
    $page->selectFieldOption("{$field_name}[0][theme]", 'bartik');
    // Allow the theme to change.
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Check if all color fields exist.
    foreach ($theme_color_fields as $color_field => $value) {
      $this->assertSession()->fieldExists("palette[{$color_field}]");
    }

    // Fill each color field with a random hex value.
    $new_palette = [];
    foreach ($theme_color_fields as $color_field => $value) {
      $color = sprintf("#%06x", rand(0, 16777215));
      // Store color, so we can compare later.
      $new_palette[$color_field] = $color;
      $page->fillField("palette[{$color_field}]", $color);
    }

    // Submit the form.
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200, "User saved without errors.");

    // Reload the user to fetch updated values.
    $user = User::load($this->whiteLabelOwner->id());

    // Todo: Fix the hard coded id.
    $new_file = File::load(3);

    // Test saved values.
    $this->assertEquals($new_values['token'], $user->{$field_name}->entity->getToken());
    $this->assertEquals($new_values['name'], $user->{$field_name}->entity->getName());
    $this->assertEquals($new_values['slogan'], $user->{$field_name}->entity->getSlogan());
    $this->assertEquals($new_file, $user->{$field_name}->entity->getLogo());
    foreach ($theme_color_fields as $color_field => $value) {
      $this->assertEquals($new_palette[$color_field], $user->field_whitelabel->entity->getPalette()[$color_field]);
    }

    // Assert that the values persisted in the form.
    $this->assertSession()->fieldValueEquals("{$field_name}[0][token][0][value]", $new_values['token']);
    $this->assertSession()->fieldValueEquals("{$field_name}[0][name][0][value]", $new_values['name']);
    $this->assertSession()->fieldValueEquals("{$field_name}[0][slogan][0][value]", $new_values['slogan']);
    $this->assertSession()->hiddenFieldValueEquals("{$field_name}[0][logo][0][fids]", $new_file->id());
    $this->assertSession()->fieldValueEquals("{$field_name}[0][theme]", 'bartik');
    foreach ($theme_color_fields as $color_field => $value) {
      $this->assertSession()->fieldValueEquals("palette[{$color_field}]", $new_palette[$color_field]);
    }
  }

}
