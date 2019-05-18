<?php

namespace Drupal\Tests\aws_cloud\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Utility\Random;
use Drupal\Tests\aws_cloud\Traits\AwsCloudTestTrait;

// Updated by yas 2016/06/02
// Updated by yas 2016/06/01
// Updated by yas 2016/05/29
// updated by yas 2016/05/26
// updated by yas 2016/05/25
// updated by yas 2016/05/24
// updated by yas 2016/05/23
// updated by yas 2016/05/20
// updated by yas 2016/05/19
// updated by yas 2015/06/14
// updated by yas 2015/06/09
// created by yas 2015/06/08.
/**
 * Tests Cloud Config.
 *
 * @group Cloud
 */
class AwsCloudConfigTest extends BrowserTestBase {

  use AwsCloudTestTrait;

  const AWS_CLOUD_CONFIG_REPEAT_COUNT = 1;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'cloud',
    'aws_cloud',
    'cloud_server_template',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'minimal';

  protected $random;

  /**
   * Set up test.
   */
  protected function setUp() {
    parent::setUp();

    if (!$this->random) {
      $this->random = new Random();
    }

    $config = \Drupal::configFactory()->getEditable('aws_cloud.settings');
    $config->set('aws_cloud_test_mode', TRUE)
      ->save();

    $this->initMockInstanceTypes();

    $web_user = $this->drupalCreateUser([
      'administer cloud config entities',
      'add cloud config entities',
      'edit cloud config entities',
      'edit own cloud config entities',
      'delete cloud config entities',
      'delete own cloud config entities',
      'view unpublished cloud config entities',
      'view own unpublished cloud config entities',
      'view published cloud config entities',
      'view own published cloud config entities',
      'access dashboard',
      'list cloud server template',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests CRUD for Cloud config information.
   */
  public function testCloudConfig() {

    // List AWS Cloud for Amazon EC2.
    $this->drupalGet('/admin/structure/cloud_config');
    $this->assertResponse(200, t('HTTP 200: List | AWS Cloud'));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));

    // Add a new Config information.
    $add = $this->createConfigTestData();
    for ($i = 0; $i < self::AWS_CLOUD_CONFIG_REPEAT_COUNT; $i++) {

      unset($add[$i]['field_region']);
      unset($add[$i]['field_api_endpoint_uri']);
      unset($add[$i]['field_image_upload_url']);

      $num = $i + 1;
      $label[$i] = $add[$i]['name[0][value]'];

      $this->drupalGet('/admin/structure/cloud_config/add');
      $this->assertResponse(200, t('HTTP 200: Add | AWS Cloud Config Form #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));

      $this->drupalPostForm('/admin/structure/cloud_config/add',
                            $add[$i],
                            t('Save'));

      $this->assertResponse(200, t('HTTP 200: Saved | Aws Cloud Config Form #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
      $this->assertText(t('Creating Cloud config was performed successfully.'), t('Create CloudConfig entities'));

      $this->assertText($label[$i],
                        t('Name: @label', [
                          '@label' => $label[$i],
                        ]));

      // Make sure listing.
      $this->drupalGet('/admin/structure/cloud_config');
      $this->assertResponse(200, t('HTTP 200: List | AWS Cloud #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($label[$j],
                        t('Cloud config @num: @label', [
                          '@num' => $j + 1,
                          '@label' => $label[$j],
                        ]));
      }
    }

    // Edit Config case.
    $edit = $this->createConfigTestData();
    for ($i = 0; $i < self::AWS_CLOUD_CONFIG_REPEAT_COUNT; $i++) {

      $num = $i + 1;
      $label[$i] = $edit[$i]['name[0][value]'];

      unset($edit[$i]['field_cloud_type']);
      unset($edit[$i]['regions[us-east-1]']);

      $this->drupalPostForm('/admin/structure/cloud_config/' . $num . '/edit',
                            $edit[$i],
                            t('Save'));
      $this->assertResponse(200, t('HTTP 200: Edit | AWS Cloud Form #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));

      $this->assertText($label[$i],
                      t('Cloud config Name: @label', [
                        '@label' => $label[$i],
                      ]));

      // Make sure listing.
      $this->drupalGet('/admin/structure/cloud_config');
      $this->assertResponse(200, t('HTTP 200: List | AWS Cloud #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($label[$j],
                        t('Cloud config @num: @label', [
                          '@num' => $j + 1,
                          '@label' => $label[$j],
                        ]));
      }
    }

    // Delete Config Items.
    $delete = $edit;
    for ($i = 0; $i < self::AWS_CLOUD_CONFIG_REPEAT_COUNT; $i++) {

      $num = $i + 1;
      $label[$i] = $delete[$i]['name[0][value]'];

      $this->drupalGet('/admin/structure/cloud_config/' . $num . '/delete');
      $this->drupalPostForm('/admin/structure/cloud_config/' . $num . '/delete',
                            [],
                            t('Delete'));
      $this->assertResponse(200, t('HTTP 200: Delete | AWS Cloud'));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
      $this->assertText(t('The cloud config @label has been deleted.', ['@label' => $label[$i]]),
      t('Deleted Message:') . ' '
      . t('The cloud config @label has been deleted.', ['@label' => $label[$i]]));

      // Because $cloud_context has been deleted.
      // Make sure listing.
      $this->drupalGet('/admin/structure/cloud_config');
      $this->assertResponse(200, t('HTTP 200: List | AWS Cloud #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertNoText($label[$j],
                          t('Cloud config Name @num: @label', [
                            '@num' => $j + 1,
                            '@label' => $label[$j],
                          ]));
      }
    }
  }

  /**
   * Tests Redirect for Cloud config information.
   */
  public function testCloudConfigRedirect() {
    $this->repeatTestCloudConfigRedirect(self::AWS_CLOUD_CONFIG_REPEAT_COUNT);
  }

  /**
   * Repeating test cloud config redirect.
   *
   * @param int $max_count
   *   Max test repeating count.
   */
  private function repeatTestCloudConfigRedirect($max_count) {
    $paths = [
      '/clouds',
      '/clouds/design',
    ];

    foreach ($paths as $path) {
      for ($i = 0; $i < $max_count; $i++) {
        $this->drupalGet($path);
        $this->assertResponse(200, t('HTTP 200: clouds | AWS Cloud'));
        $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
        $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));

        $this->assertText(t('Add cloud config'), t('Add cloud config'));
        $this->assertText(
          t('There is no cloud service provider. Please create a new one.'),
          t('Guid message')
        );
      }
    }
  }

  /**
   * Create test data for cloud config.
   *
   * @return array
   *   Test data.
   */
  private function createConfigTestData() {
    $this->random = new Random();

    // Input Fields
    // 3 times.
    for ($i = 0; $i < self::AWS_CLOUD_CONFIG_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      $data[] = [
        'field_cloud_type'            => 'amazon_ec2',
        'name[0][value]'              => "$num - " . $this->random->name(8, TRUE),
        'field_description[0][value]' => "#$num: " . date('Y/m/d H:i:s - D M j G:i:s T Y') . $this->random->string(64, TRUE),
        'field_api_version[0][value]' => 'latest',
        'field_region'                => "us-west-$num",
        'field_secret_key[0][value]'  => $this->random->name(20, TRUE),
        'field_access_key[0][value]'  => $this->random->name(40, TRUE),
        'field_account_id[0][value]'  => $this->random->name(16, TRUE),
        'regions[us-east-1]'          => 'us-east-1',
      ];

    }

    return $data;
  }

}
