<?php

namespace Drupal\streamy_aws\Tests\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the AWS S3 form behaviors.
 *
 * @group streamy_aws
 */
class AwsS3Test extends BrowserTestBase {

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
    $this->drupalGet('/admin/config/media/file-system/streamy/streams/awsv3');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->fieldValueEquals("streamy[master][aws_key]", ''); // 'aws_key element contains the correct value.
    $this->assertSession()->fieldValueEquals("streamy[master][aws_secret]", ''); // 'aws_secret element contains the correct value.
    $this->assertSession()->fieldValueEquals("streamy[master][aws_region]", 'eu-central-1'); // 'aws_region element contains the correct value.
    $this->assertSession()->fieldValueEquals("streamy[master][aws_bucket]", ''); // 'aws_bucket element contains the correct value.
    $this->assertSession()->fieldValueEquals("streamy[master][aws_prefix]", ''); // 'aws_prefix element contains the correct value.
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy-master' . '-slow-stream', FALSE); // 'slow_stream checkbox has the correct value.

    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_key]", ''); // 'aws_key element contains the correct value.
    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_secret]", ''); // 'aws_secret element contains the correct value.
    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_region]", 'eu-central-1'); // 'aws_region element contains the correct value.
    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_bucket]", ''); // 'aws_bucket element contains the correct value.
    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_prefix]", ''); // 'aws_prefix element contains the correct value.
    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt-master' . '-slow-stream', FALSE); // 'slow_stream checkbox has the correct value.

    // Checking the config
    $config = \Drupal::configFactory()->get('streamy_aws.awsv3')->get('plugin_configuration');
    self::assertTrue(self::isEmpty($config), 'config is empty');

    $pluginConfig = [
      'streamy'    => [
        'master' => [
          'aws_key'     => 'abcde',
          'aws_secret'  => 'fghilm',
          'aws_region'  => 'ap-southeast-2',
          'aws_bucket'  => 'bbkktt',
          'aws_prefix'  => 'rree',
          'slow_stream' => TRUE,
        ],
      ],
      'streamypvt' => [
        'master' => [
          'aws_key'     => 'abcdea',
          'aws_secret'  => 'fghilms',
          'aws_region'  => 'ap-southeast-1',
          'aws_bucket'  => 'bbkkttd',
          'aws_prefix'  => 'rreed',
          'slow_stream' => FALSE,
        ],
      ],
    ];

    // Saving the configuration
    \Drupal::configFactory()->getEditable('streamy_aws.awsv3')
           ->set('plugin_configuration', $pluginConfig)
           ->save();

    $this->drupalGet('/admin/config/media/file-system/streamy/streams/awsv3');
    $this->assertSession()->statusCodeEquals(200);

    // Checking that the form actually displays the values previously set
    $this->assertSession()->fieldValueEquals("streamy[master][aws_key]", 'abcde'); // 'aws_key element contains the correct value
    $this->assertSession()->fieldValueEquals("streamy[master][aws_secret]", 'fghilm'); // 'aws_secret element contains the correct value
    $this->assertSession()->fieldValueEquals("streamy[master][aws_region]", 'ap-southeast-2'); // 'aws_region element contains the correct value
    $this->assertSession()->fieldValueEquals("streamy[master][aws_bucket]", 'bbkktt'); // 'aws_bucket element contains the correct value
    $this->assertSession()->fieldValueEquals("streamy[master][aws_prefix]", 'rree'); // 'aws_prefix element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy-master' . '-slow-stream', TRUE); // 'slow_stream checkbox has the correct value

    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_key]", 'abcdea'); // 'aws_key element contains the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_secret]", 'fghilms'); // 'aws_secret element contains the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_region]", 'ap-southeast-1'); // 'aws_region element contains the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_bucket]", 'bbkkttd'); // 'aws_bucket element contains the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[master][aws_prefix]", 'rreed'); // 'aws_prefix element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt-master' . '-slow-stream', FALSE); // 'slow_stream checkbox has the correct value
  }

}
