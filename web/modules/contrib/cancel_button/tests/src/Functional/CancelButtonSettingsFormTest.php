<?php

namespace Drupal\Tests\cancel_button\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the CancelButtonSettingsForm.
 *
 * @group cancel_button
 */
class CancelButtonSettingsFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'cancel_button',
    'node',
  ];

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Format a string for HTML display by replacing variable placeholders.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->createUser([
      'access administration pages',
      'administer site configuration',
      'administer cancel button configuration',
    ]);
    $this->translationManager = \Drupal::translation();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test setting the default cancel button destinations on the config form.
   */
  public function testCancelButtonDefaultDestinations() {

    $this->drupalGet('admin/config/content/cancel-button');
    // Check that the page loads.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('default_cancel_destination');

    // Test submission of the form with invalid paths.
    $edit = [
      'default_cancel_destination' => 'y&^blP3',
    ];
    $this->drupalPostForm('admin/config/content/cancel-button', $edit, t('Save configuration'));
    $this->assertSession()->responseContains(
      $this->translationManager->translate(
        "The path '%path' is either invalid or you do not have access to it.",
        ['%path' => $edit['default_cancel_destination']]
      )
    );

    // The form has additional fields for entity types other than default.
    // Though the default value for these fields is '/', apparently
    // '/' is not a valid path for testing. Generate the field name for all
    // fields, supply a default valid value and test successful save of
    // configuration.
    $entity_types = $this->container->get('entity_type.manager')->getDefinitions();
    $bundles_by_entity = [];
    $final_edit = [];
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Do not consider entities with wizard forms.
      if (array_key_exists('wizard', $entity_type->getHandlerClasses())) {
        continue;
      }
      if ($entity_type->hasKey('bundle')) {
        $bundle_entity_type = $entity_type->getBundleEntityType();
        if (!empty($bundle_entity_type)) {
          $bundles_by_entity[$entity_type_id] = $this->container->get('entity_type.manager')
            ->getStorage($bundle_entity_type)
            ->loadMultiple();
        }
      }
      if (!isset($bundles_by_entity[$entity_type_id]) || (count($bundles_by_entity[$entity_type_id]) == 0)) {
        $final_edit = array_merge($final_edit, [
          $entity_type_id . '_cancel_destination' => '/admin',
        ]);
      }
    }
    foreach ($bundles_by_entity as $entity_type_id => $bundles) {
      /** @var \Drupal\Core\Entity\EntityInterface $bundle */
      foreach ($bundles as $bundle) {
        $final_edit = array_merge($final_edit, [
          $entity_type_id . '_' . $bundle->id() . '_cancel_destination' => '/admin',
        ]);
      }
    }
    $final_edit = array_merge($final_edit, [
      'default_cancel_destination' => '/admin',
    ]);
    $this->drupalPostForm('admin/config/content/cancel-button', $final_edit, t('Save configuration'));
    $this->assertSession()->pageTextContains(t('The configuration options have been saved.'));
  }

}
