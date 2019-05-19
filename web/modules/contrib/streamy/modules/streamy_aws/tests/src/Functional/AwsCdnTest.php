<?php

namespace Drupal\Tests\streamy_aws\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the AWS CDN form behaviors.
 *
 * @group streamy_aws
 */
class AwsCdnTest extends BrowserTestBase {

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['streamy_aws'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser(['administer site configuration', 'administer streamy aws']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests if the form can display settings that have been
   * programmatically set through the configuration service.
   */
  public function testLocalInverseFormBehaviors() {
    // Going to the page first
    $this->drupalGet('/admin/config/media/file-system/streamy/cdn/awscdn');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-enabled', FALSE);
    $this->assertSession()->fieldValueEquals("streamy[url]", '');
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-https', FALSE); // 'https checkbox has the correct value.'

    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-enabled', FALSE); // enabled checkbox has the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[url]", '');
    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-https', FALSE); // https checkbox has the correct value

    // Checking the config
    $config = \Drupal::configFactory()->get('streamy_aws.awscdn')->get('plugin_configuration');
    self::assertTrue(empty($config), 'config is empty');

    $pluginConfig = [
      'streamy'    => [
        'enabled' => TRUE,
        'url'     => 'https://www.testlink.it',
        'https'   => TRUE,
      ],
      'streamypvt' => [
        'enabled' => TRUE,
        'url'     => 'https://www.testlink2.it',
        'https'   => TRUE,
      ],
    ];

    // Saving the configuration
    \Drupal::configFactory()->getEditable('streamy_aws.awscdn')
           ->set('plugin_configuration', $pluginConfig)
           ->save();

    $this->drupalGet('/admin/config/media/file-system/streamy/cdn/awscdn');
    $this->assertSession()->statusCodeEquals(200);

    // Checking that the form actually displays the values previously set
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-enabled', TRUE); // enabled checkbox has the correct value.
    $this->assertSession()->fieldValueEquals("streamy[url]", 'https://www.testlink.it'); // url element contains the correct value.
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-https', TRUE);// https checkbox has the correct value.

    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-enabled', TRUE); // enabled checkbox has the correct value.
    $this->assertSession()->fieldValueEquals("streamypvt[url]", 'https://www.testlink2.it'); // url element contains the correct value.
    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-https', TRUE); // https checkbox has the correct value.
  }


  /**
   * Tests if the form can save and display settings.
   */
  public function testLocalFormBehaviors() {
    // Checking the config
    $config = \Drupal::configFactory()->get('streamy_aws.awscdn')->get('plugin_configuration');
    self::assertTrue(empty($config), 'config is empty');

    // Going to the page first
    $this->drupalGet('/admin/config/media/file-system/streamy/cdn/awscdn');
    $this->assertSession()->statusCodeEquals(200);

    // Checking that the form is blank
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-enabled', FALSE); // enabled checkbox has the correct value
    $this->assertSession()->fieldValueEquals("streamy[url]", ''); // url element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-https', FALSE); // https checkbox has the correct value

    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-enabled', FALSE); // enabled checkbox has the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[url]", ''); // url element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-https', FALSE); // https checkbox has the correct value.

    // Edit
    $edit = [
      'streamy[enabled]'    => TRUE,
      'streamy[url]'        => 'testlink.it',
      'streamy[https]'      => TRUE,
      'streamypvt[enabled]' => TRUE,
      'streamypvt[url]'     => 'testlink2.it',
      'streamypvt[https]'   => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $this->assertText(t('The configuration options have been saved.'));

    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-enabled', TRUE); // enabled checkbox has the correct value
    $this->assertSession()->fieldValueEquals("streamy[url]", 'testlink.it');
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-https', TRUE); // https checkbox has the correct value

    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-enabled', TRUE); // enabled checkbox has the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[url]", 'testlink2.it'); // url element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-https', TRUE); // https checkbox has the correct value

    $pluginConfig = [
      'streamy'    => [
        'enabled' => TRUE,
        'url'     => 'https://www.testlink.ru',
        'https'   => TRUE,
      ],
      'streamypvt' => [
        'enabled' => FALSE,
        'url'     => 'https://testlink2.ch',
        'https'   => TRUE,
      ],
    ];

    // Saving the configuration
    \Drupal::configFactory()->getEditable('streamy_aws.awscdn')
           ->set('plugin_configuration', $pluginConfig)
           ->save();

    $this->drupalGet('/admin/config/media/file-system/streamy/cdn/awscdn');
    $this->assertSession()->statusCodeEquals(200);

    // Checking that the form actually displays the values previously set
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-enabled', TRUE); // enabled checkbox has the correct value
    $this->assertSession()->fieldValueEquals("streamy[url]", 'https://www.testlink.ru'); // url element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy' . '-https', TRUE); // https checkbox has the correct value

    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-enabled', FALSE); // enabled checkbox has the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[url]", 'https://testlink2.ch'); // url element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt' . '-https', TRUE); // https checkbox has the correct value
  }

}
