<?php

namespace Drupal\Tests\bynder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Bynder Entity Browser widgets.
 *
 * @group bynder
 */
class BynderWidgetsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bynder',
    'media',
    'node',
    'bynder_test_module',
    'entity_browser_bynder_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $account = $this->drupalCreateUser(['access bynder entity browser pages', 'dropzone upload files']);
    $this->drupalLogin($account);
    \Drupal::service('config.factory')->getEditable('bynder.settings')
      ->set('consumer_key', 'key')
      ->set('consumer_secret', 'secret')
      ->set('token', 'token')
      ->set('token_secret', 'secret')
      ->set('account_domain', 'https://dam.bynder.com')
      ->save();
  }

  /**
   * Tests redirect from Bynder to Entity browser widgets.
   */
  public function testRedirectToEntityBrowserWidgets() {
    \Drupal::state()->set('bynder.bynder_test_brands', [
      [
        'id' => 'brand_id',
        'name' => 'Brand Name',
        'subBrands' => [],
      ],
    ]);

    $entity_browser = $this->container->get('entity_type.manager')
      ->getStorage('entity_browser')
      ->load('bynder');
    $entity_browser->setWidgetSelector('tabs');
    $widget_configuration = [
      'id' => 'bynder_upload',
      'label' => 'Bynder upload',
      'weight' => 5,
      'settings' => [
        'media_type' => 'media_type',
        'brand' => 'brand_id',
      ],
    ];
    $widget_id = $entity_browser->addWidget($widget_configuration);
    $entity_browser->save();

    $this->drupalGet('/entity-browser/modal/bynder');
    // Tests that we redirect to the search widget after we press the "Reload
    // after submit" button.
    $this->assertSession()->elementAttributeContains('css', '#edit-tab-selector-065aa618-5851-4744-b51c-02e57f5f0cc3', 'class', 'is-disabled');
    $this->assertSession()->elementAttributeNotContains('css', '#edit-tab-selector-' . $widget_id, 'class', 'is-disabled');

    \Drupal::state()->set('bynder.bynder_test_access_token', TRUE);
    $this->getSession()->getPage()->pressButton('Reload after submit');

    $this->assertSession()->elementAttributeContains('css', '#edit-tab-selector-065aa618-5851-4744-b51c-02e57f5f0cc3', 'class', 'is-disabled');
    $this->assertSession()->elementAttributeNotContains('css', '#edit-tab-selector-' . $widget_id, 'class', 'is-disabled');

    // Tests that we redirect to the upload widget after we press the "Reload
    // after submit" button.
    \Drupal::state()->set('bynder.bynder_test_access_token', FALSE);
    $this->getSession()->getPage()->pressButton('Bynder upload');

    $this->assertSession()->elementAttributeNotContains('css', '#edit-tab-selector-065aa618-5851-4744-b51c-02e57f5f0cc3', 'class', 'is-disabled');
    $this->assertSession()->elementAttributeContains('css', '#edit-tab-selector-' . $widget_id, 'class', 'is-disabled');

    \Drupal::state()->set('bynder.bynder_test_access_token', TRUE);
    $this->getSession()->getPage()->pressButton('Reload after submit');

    $this->assertSession()->elementAttributeNotContains('css', '#edit-tab-selector-065aa618-5851-4744-b51c-02e57f5f0cc3', 'class', 'is-disabled');
    $this->assertSession()->elementAttributeContains('css', '#edit-tab-selector-' . $widget_id, 'class', 'is-disabled');
  }

}
