<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

// Updated by yas 2016/09/07
// Updated by yas 2016/06/24
// Updated by yas 2016/06/23
// Updated by yas 2016/06/02
// Updated by yas 2016/05/31
// Updated by yas 2016/05/29
// Updated by yas 2016/05/25
// Updated by yas 2016/05/24
// Updated by yas 2016/05/23
// Updated by yas 2016/05/22
// Created by yas 2016/05/21.
use Drupal\Component\Serialization\Yaml;

use Drupal\Tests\aws_cloud\Functional\Utils;

/**
 * Tests AWS Cloud Instance.
 *
 * @group AWS Cloud
 */
class InstanceTest extends AwsCloudTestCase {

  const AWS_CLOUD_INSTANCE_REPEAT_COUNT = 3;

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'add aws cloud instance',
      'list aws cloud instance',
      'edit own aws cloud instance',
      'delete own aws cloud instance',
      'view own aws cloud instance',

      'list cloud server template',
      'launch server template',

      'add aws cloud image',
      'list aws cloud image',
      'view any aws cloud image',
      'edit any aws cloud image',
      'delete any aws cloud image',

      'administer aws_cloud',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMockDataTemplateVars() {
    $public_ip = Utils::getRandomPublicIp();
    $private_ip = Utils::getRandomPrivateIp();
    $regions = ['us-west-1', 'us-west-2'];
    $region = $regions[array_rand($regions)];

    return [
      // 12 digits.
      'account_id' => rand(100000000000, 999999999999),
      'reservation_id' => 'r-' . $this->getRandomAwsId(),
      'group_name' => $this->random->name(8, TRUE),
      'host_id' => $this->random->name(8, TRUE),
      'affinity' => $this->random->name(8, TRUE),
      'launch_time' => date('c'),
      'security_group_id' => 'sg-' . $this->getRandomAwsId(),
      'security_group_name' => $this->random->name(10, TRUE),
      'public_dns_name' => Utils::getPublicDns($region, $public_ip),
      'public_ip_address' => $public_ip,
      'private_dns_name' => Utils::getPrivateDns($region, $private_ip),
      'private_ip_address' => $private_ip,
      'vpc_id' => 'vpc-' . $this->getRandomAwsId(),
      'subnet_id' => 'subnet-' . $this->getRandomAwsId(),
      'image_id' => 'ami-' . $this->getRandomAwsId(),
      'reason' => $this->random->string(16, TRUE),
      'instance_id' => 'i-' . $this->getRandomAwsId(),
      'state' => 'running',
    ];
  }

  /**
   * Tests CRUD for instance information.
   */
  public function testInstance() {
    $cloud_context = $this->cloudContext;

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Add an empty value to IAM roles.
    $iam_roles = array_merge([[]], $iam_roles);

    $this->createServerTemplate($iam_roles);

    // List Instance for Amazon EC2.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance");
    $this->assertResponse(200, t('HTTP 200: List | Instance'));
    $this->assertNoText(t('Notice'), t('List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('List | Make sure w/o Warnings'));

    // Launch a new Instance.
    $add = $this->createInstanceTestData();
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->addInstanceMockData($add[$i]['name'], $add[$i]['key_pair_name']);
      $this->drupalPostForm("/clouds/design/server_template/$cloud_context/1/launch",
                            [],
                            t('Launch'));
      $this->assertResponse(200, t('HTTP 200: Launch | A New Cloud Instance #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Launch | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Launch | Make sure w/o Warnings'));

      // Make sure listing.
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($add[$j]['name'], t('Make sure w/ Listing: @name', [
          '@name' => $add[$j]['name'],
        ]));
      }
    }

    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      // Make sure the all instance listing exists.
      $this->drupalGet("/clouds/aws_cloud");
      $this->assertResponse(200, t('Edit | List | HTTP 200: Instance #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($add[$j]['name'], t('Make sure w/ Listing: @name', [
          '@name' => $add[$j]['name'],
        ]));
      }
    }

    // Edit an Instance information.
    $edit = $this->createInstanceTestData();
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      unset($edit[$i]['cloud_type']);
      unset($edit[$i]['image_id']);
      unset($edit[$i]['min_count']);
      unset($edit[$i]['max_count']);
      unset($edit[$i]['key_pair_name']);
      unset($edit[$i]['is_monitoring']);
      unset($edit[$i]['availability_zone']);
      unset($edit[$i]['instance_type']);
      unset($edit[$i]['kernel_id']);
      unset($edit[$i]['ramdisk_id']);
      unset($edit[$i]['user_data']);

      // Change security groups.
      $security_groups = $this->createRandomSecurityGroups();
      $this->updateDescribeSecurityGroupsInMockData($security_groups);
      $edit[$i]['security_groups[]'] = [array_column($security_groups, 'GroupName')[0]];

      // Termination.
      $edit[$i]['termination_timestamp[0][value][date]'] = date('Y-m-d', time() + 365.25 * 3);
      $edit[$i]['termination_timestamp[0][value][time]'] = '00:00:00';
      $edit[$i]['termination_protection'] = '1';

      // IAM role.
      $iam_role_index = array_rand($iam_roles);
      if ($iam_role_index == 0) {
        $iam_role_name = '';
        $edit[$i]['iam_role'] = '';
      }
      else {
        $iam_role = $iam_roles[$iam_role_index]['Arn'];
        $iam_role_name = $iam_roles[$iam_role_index]['InstanceProfileName'];
        $edit[$i]['iam_role'] = $iam_role;
      }

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/instance/$num/edit",
                            $edit[$i],
                            t('Save'));

      // Termination validation.
      $this->assertText(
        t(
          '"@name1" should be left blank if "@name2" is selected. Please leave "@name1" blank or unselect "@name2".',
          ['@name1' => t('Termination Date'), '@name2' => t('Termination Protection')]
        ), 'Termination validation'
      );
      unset($edit[$i]['termination_timestamp[0][value][date]']);
      unset($edit[$i]['termination_timestamp[0][value][time]']);
      unset($edit[$i]['termination_protection']);

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/instance/$num/edit",
                            $edit[$i],
                            t('Save'));

      $this->updateInstanceInMockData($num - 1, $edit[$i]['name']);

      $this->assertResponse(200, t('Edit | HTTP 200:  A New Instance #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Instance "@name" has been saved.', ['@name' => $edit[$i]['name']]),
        t('Confirm Message: Edit | The AWS Cloud Instance "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ])
      );
      if ($iam_role_name != '') {
        $this->assertText($iam_role_name);
      }

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance");
      $this->assertResponse(200, t('Edit | List | HTTP 200: Instance #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText(
          $edit[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $edit[$i]['name'],
          ])
        );
      }
    }

    // Terminate Instance.
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/$num/terminate");
      $this->assertResponse(200, t('Terminate: HTTP 200: Instance #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Terminate | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Terminate | Make sure w/o Warnings'));

      $this->deleteFirstInstanceInMockData();

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/instance/$num/terminate",
                            [],
                            t('Delete | Terminate'));

      $this->assertResponse(200, t('Terminate | HTTP 200: The Cloud Instance #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Terminate | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Terminate | Make sure w/o Warnings'));
      $this->assertText($edit[$i]['name'], t('Instance Name: @name', ['@name' => $edit[$i]['name']]));
      $this->assertText(
        t('The AWS Cloud Instance "@name" has been terminated.', ['@name' => $edit[$i]['name']]),
        t('Confirm Message: Terminate | The AWS Cloud Instance "@name" has been terminated.', [
          '@name' => $edit[$i]['name'],
        ]));

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance");
      $this->assertResponse(200, t('Terminate | HTTP 200: Instance #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Terminate | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Terminate | List | Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertNoText($edit[$i]['name'],
          t('Terminate | List | Make sure w/ Listing: @name', [
            '@name' => $edit[$i]['name'],
          ]));
      }
    }
  }

  /**
   * Tests updating instances.
   */
  public function testUpdateInstances() {
    $cloud_context = $this->cloudContext;

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Add an empty value to IAM roles.
    $iam_roles = array_merge([[]], $iam_roles);

    $this->createServerTemplate($iam_roles);

    // Launch a new Instance.
    $add = $this->createInstanceTestData();
    $this->addInstanceMockData($add[0]['name'], $add[0]['key_pair_name']);
    $this->drupalPostForm("/clouds/design/server_template/$cloud_context/1/launch",
                          [],
                          t('Launch'));
    $this->assertResponse(200, t('HTTP 200: Launch | A New Cloud Instance 1'));
    $this->assertNoText(t('Notice'), t('Launch | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Launch | Make sure w/o Warnings'));

    // Change security groups.
    $security_group_name1 = $this->random->name(8, TRUE);
    $security_group_name2 = $this->random->name(8, TRUE);
    $this->updateSecurityGroupsInMockData($security_group_name1, $security_group_name2);

    // Change instance type.
    $instance_type = $this->random->name(6, TRUE);
    $this->updateInstanceTypeInMockData($instance_type);

    // Update the schedule tag.
    $schedule_value = $this->random->name(8, TRUE);
    $this->updateScheduleTagInMockData($schedule_value);

    // Run cron job to update instances.
    $key = \Drupal::state()->get('system.cron_key');
    $this->drupalGet('/cron/' . $key);
    $this->assertResponse(204);

    // Verify schedule tag.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/1");
    $this->assertResponse(200, t('View | HTTP 200: The Cloud Instance 1'));
    $this->assertNoText(t('Notice'), t('View | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('View | Make sure w/o Warnings'));
    $this->assertText("$schedule_value", t('Schedule'));

    // Verify security group.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/1");
    $this->assertResponse(200, t('View | HTTP 200: The Cloud Instance 1'));
    $this->assertNoText(t('Notice'), t('View | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('View | Make sure w/o Warnings'));
    $this->assertText("$security_group_name1, $security_group_name2", t('Security Group'));
    $this->assertText($instance_type, t('Instance Type'));
  }

  /**
   * Tests updating instance attributes.
   */
  public function testUpdateInstanceAttributes() {
    $this->repeatTestUpdateInstanceAttributes(self::AWS_CLOUD_INSTANCE_REPEAT_COUNT);
  }

  /**
   * Repeating test update instance attributes.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestUpdateInstanceAttributes($max_test_repeat_count = 1) {
    $cloud_context = $this->cloudContext;

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Add an empty value to IAM roles.
    $iam_roles = array_merge([[]], $iam_roles);

    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      $num = $i + 1;

      $this->createServerTemplate($iam_roles);

      // Launch a stopped Instance.
      $add = $this->createInstanceTestData();
      $this->addInstanceMockData($add[$i]['name'], $add[$i]['key_pair_name'], 'stopped');
      $this->drupalPostForm("/clouds/design/server_template/$cloud_context/$num/launch",
                            [],
                            t('Launch'));
      $this->assertResponse(200, t('HTTP 200: Launch | A New Cloud Instance @num', [
        '@num' => $num,
      ]));
      $this->assertNoText(t('Notice'), t('Launch | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Launch | Make sure w/o Warnings'));
      $this->assertText('stopped', t('Instance Type: stopped'));

      // Edit instance.
      $edit = $this->createInstanceTestData();
      unset($edit[$i]['cloud_type']);
      unset($edit[$i]['image_id']);
      unset($edit[$i]['min_count']);
      unset($edit[$i]['max_count']);
      unset($edit[$i]['key_pair_name']);
      unset($edit[$i]['is_monitoring']);
      unset($edit[$i]['availability_zone']);
      unset($edit[$i]['kernel_id']);
      unset($edit[$i]['ramdisk_id']);
      unset($edit[$i]['user_data']);

      $instance_type = 'm1.small';
      $edit[$i]['instance_type'] = $instance_type;

      // Change security groups.
      $security_groups = $this->createRandomSecurityGroups();
      $this->updateDescribeSecurityGroupsInMockData($security_groups);
      $edit[$i]['security_groups[]'] = array_column($security_groups, 'GroupName');

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/instance/$num/edit",
                            $edit[$i],
                            t('Save'));

      $this->updateInstanceInMockData($i, $edit[$i]['name']);

      $this->assertResponse(200, t('Edit | HTTP 200:  A New Instance #@num', [
        '@num' => $num,
      ]));
      $this->assertNoText(t('Notice'), t('Edit | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Instance "@name" has been saved.', ['@name' => $edit[$i]['name']]),
        t('Confirm Message: Edit | The AWS Cloud Instance "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ]));

      // Verify instance attributes.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/$num");
      $this->assertResponse(200, t('View | HTTP 200: The Cloud Instance @num', [
        '@num' => $num,
      ]));
      $this->assertNoText(t('Notice'), t('View | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('View | Make sure w/o Warnings'));
      $this->assertText($instance_type, t('Instance Type: @instance_types', ['@instance_types' => $instance_type]));
      $groups = implode(', ', $edit[$i]['security_groups[]']);
      $this->assertText(
        $groups,
        t('Security Groups: @groups', ['@groups' => $groups])
      );
    }
  }

  /**
   * Tests setting the configuration of instance terminating.
   */
  public function testInstanceTerminateConfiguration() {
    $this->repeatTestInstanceTerminateConfiguration(self::AWS_CLOUD_INSTANCE_REPEAT_COUNT);
  }

  /**
   * Repeating test instance terminate configuration.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestInstanceTerminateConfiguration($max_test_repeat_count = 1) {
    $cloud_context = $this->cloudContext;

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Add an empty value to IAM roles.
    $iam_roles = array_merge([[]], $iam_roles);

    $terminate_allowed_values = [TRUE, FALSE];
    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      $num = $i + 1;

      $terminate_value = $terminate_allowed_values[array_rand($terminate_allowed_values)];
      $this->drupalPostForm("admin/config/services/cloud/aws_cloud/settings",
        [
          'aws_cloud_instance_terminate' => $terminate_value,
          'google_credential' => json_encode([]),
        ],
        t('Save configuration')
      );
      $this->assertResponse(200);

      $this->createServerTemplate($iam_roles);
      $this->drupalGet("/clouds/design/server_template/$cloud_context/$num/launch");
      if ($terminate_value) {
        $this->assertFieldChecked('edit-terminate');
      }
      else {
        $this->assertNoFieldChecked('edit-terminate');
      }
    }
  }

  /**
   * Test creating an image from an instance.
   */
  public function testImageCreationFromInstance() {
    $this->repeatTestImageCreationFromInstance(self::AWS_CLOUD_INSTANCE_REPEAT_COUNT);
  }

  /**
   * Repeating test image creation from instance.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestImageCreationFromInstance($max_test_repeat_count = 1) {
    $cloud_context = $this->cloudContext;

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Add an empty value to IAM roles.
    $iam_roles = array_merge([[]], $iam_roles);

    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      // Setup server template and instance.
      $num = $i + 1;

      $this->createServerTemplate($iam_roles);
      $add = $this->createInstanceTestData();
      $this->addInstanceMockData($add[$i]['name'], $add[$i]['key_pair_name']);
      $this->drupalPostForm("/clouds/design/server_template/$cloud_context/$num/launch",
        [],
        t('Launch'));

      $this->assertResponse(200, t('HTTP 200: Launch | A New Cloud Instance @num', [
        '@num' => $i,
      ]));
      $this->assertNoText(t('Notice'), t('Launch | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Launch | Make sure w/o Warnings'));

      // Make sure instances are available.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/$num");
      $this->assertResponse(200, t('HTTP 200: Instance created.'));

      // Test image creation.
      $image_id = 'ami-' . $this->getRandomAwsId();
      $image_name = $this->random->name(8, TRUE);

      $image_params = [
        'image_name' => $image_name,
        'no_reboot' => 0,
      ];

      // Update the mock data then create the image.
      $this->updateImageCreationInMockData($image_id, $image_name, 'pending');
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/instance/$num/create_image",
        $image_params,
        t('Create Image'));

      $this->assertText(t("The AWS Cloud Instance @label (@image_id) has been created.", [
        '@image_id' => $image_id,
        '@label' => $add[$i]['name'],
      ]));

      // Make sure the image was created.  Status should be pending.
      // Click on the Image link from the image listing page.
      $this->clickLink($image_name);
      $this->assertResponse(200, t('HTTP 200: Image Entity.'));
      $this->assertText($image_id, t('Image Id is present'));
      $this->assertText('pending', t('Image created in pending state'));

      // Go back to listing page. Make sure there is no delete link.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
      $this->assertNoText('Delete', t('Cannot delete image in pending state'));

      // Update the image to 'available'.  Then delete the image.
      $this->updateImageCreationInMockData($image_id, $image_name, 'available');

      // Run cron job to update images state.
      $key = \Drupal::state()->get('system.cron_key');
      $this->drupalGet('/cron/' . $key);
      $this->assertResponse(204);

      // Go back into the main image.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
      // Click into the image. Make sure the status is now available.
      $this->clickLink($image_name);
      $this->assertText('available', t('Image status is available.'));

      // Go back to main listing page.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");

      // Delete the image.
      $this->clickLink('Delete');
      $this->drupalPostForm($this->getUrl(),
        [],
        t('Delete'));
      $this->assertResponse(200, t('HTTP 200: Delete', [
        '@num' => $i,
      ]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Wanings'));

      // Make sure image is deleted.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
      $this->assertNoText($image_id, t('Image deleted'));

      // Test "Failed" Image.  Failed Images should be allowed to be deleted.
      // Reset the image_id and image_name variables.
      $image_id = 'ami-' . $this->getRandomAwsId();
      $image_name = $this->random->name(8, TRUE);

      $image_params = [
        'image_name' => $image_name,
        'no_reboot' => 0,
      ];

      // Update the image so it is in failed state.
      $this->updateImageCreationInMockData($image_id, $image_name, 'failed');
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/instance/$num/create_image",
        $image_params,
        t('Create Image'));

      $this->assertText(t("The AWS Cloud Instance @label (@image_id) has been created.", [
        '@image_id' => $image_id,
        '@label' => $add[$i]['name'],
      ]));

      // Go to the main image page.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
      // Make sure the status is now failed.
      $this->clickLink($image_name);
      $this->assertText('failed', t('Image status is failed'));

      // Go to the main image page.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");

      // Delete the Failed image.
      $this->clickLink('Delete');
      $this->drupalPostForm($this->getUrl(),
        [],
        t('Delete'));
      $this->assertResponse(200, t('HTTP 200: Delete', [
        '@num' => $i,
      ]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Wanings'));

      // Make sure image is deleted.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
      $this->assertNoText($image_id, t('Image deleted'));

    }
  }

  /**
   * Test launching instances with schedule tags.
   */
  public function testCreateInstanceWithScheduleTag() {
    $this->repeatTestCreateInstanceWithScheduleTag(self::AWS_CLOUD_INSTANCE_REPEAT_COUNT);
  }

  /**
   * Repeat testing create instance with schedule tag.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestCreateInstanceWithScheduleTag($max_test_repeat_count = 1) {
    $cloud_context = $this->cloudContext;

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Add an empty value to IAM roles.
    $iam_roles = array_merge([[]], $iam_roles);

    // Setup an arbitrary schedule in the configuration.
    // This is needed in the server template launch confirmation form.
    $schedule_value = $this->random->name(8, TRUE);

    $config = \Drupal::configFactory()->getEditable('aws_cloud.settings');
    $config->set('aws_cloud_scheduler_periods', $schedule_value)
      ->save();

    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      $num = $i + 1;
      $this->createServerTemplate($iam_roles);

      // Launch a new Instance, with schedule information.
      $add = $this->createInstanceTestData();
      $this->addInstanceMockData($add[$i]['name'], $add[$i]['key_pair_name'], 'running', $schedule_value);

      $this->drupalPostForm("/clouds/design/server_template/$cloud_context/$num/launch",
        ['schedule' => $schedule_value],
        t('Launch'));

      $this->assertResponse(200, t('HTTP 200: Launch | A New Cloud Instance @num', [
        '@num' => $num,
      ]));
      $this->assertNoText(t('Notice'), t('Launch | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Launch | Make sure w/o Warnings'));

      // Go to the instance page.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/$num");
      $this->assertText($schedule_value, t('Schedule'));
    }
  }

  /**
   * Test validation of launching instances.
   */
  public function testLaunchValidation() {
    $this->repeatTestLaunchValidation(self::AWS_CLOUD_INSTANCE_REPEAT_COUNT);
  }

  /**
   * Repeat testing validation of launching instances.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestLaunchValidation($max_test_repeat_count = 1) {
    $cloud_context = $this->cloudContext;

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Add an empty value to IAM roles.
    $iam_roles = array_merge([[]], $iam_roles);

    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      $num = $i + 1;
      $this->createServerTemplate($iam_roles);

      $this->drupalPostForm("/clouds/design/server_template/$cloud_context/$num/launch",
        [
          'terminate' => '1',
          'termination_protection' => '1',
        ],
        t('Launch')
      );

      $this->assertText(
        t('"@name1" and "@name2" can\'t be selected both. Please unselect one of them.',
          [
            '@name1' => t('Termination Protection'),
            '@name2' => t('Automatically terminate instance'),
          ]
        ),
        'Launch validation'
      );
    }
  }

  /**
   * Create instance test data.
   *
   * @return string[][]|number[][]
   *   test data array.
   */
  private function createInstanceTestData() {
    $data = [];

    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $name = "Instance #$num - " . date('Y/m/d - ') . $this->random->name(8, TRUE);

      // Input Fields.
      $data[$i] = [
        'name'                => $name,
        'image_id'            => 'ami-' . $this->getRandomAwsId(),
        'min_count'           => $num,
        'max_count'           => $num * 2,
        'key_pair_name'       => "key_pair-$num-" . $this->random->name(8, TRUE),
        'is_monitoring'       => 0,
        'availability_zone'   => "us-west-$num",
        'security_groups[]'   => "security_group-$num-" . $this->random->name(8, TRUE),
        'instance_type'       => "t$num.micro",
        'kernel_id'           => 'aki-' . $this->getRandomAwsId(),
        'ramdisk_id'          => 'ari-' . $this->getRandomAwsId(),
        'user_data'           => "User Data #$num: " . $this->random->string(64, TRUE),
        'tags[0][tag_key]'    => 'Name',
        'tags[0][tag_value]'  => $name,
      ];
    }
    return $data;
  }

  /**
   * Create server template.
   */
  private function createServerTemplate($iam_roles) {
    // Create image.
    $data = [];
    $image = entity_create('aws_cloud_image', $data);
    $image->set('ami_name', 'image1');
    $image->setImageId('ami-abcdef');
    $image->save();

    $data = [
      'type' => 'aws_cloud',
      'name' => 'test_template1',
    ];

    // Create template.
    $template = entity_create('cloud_server_template', $data);
    $template->setCloudContext($this->cloudContext);
    $template->field_test_only->value = '1';
    $template->field_max_count->value = 1;
    $template->field_min_count->value = 1;
    $template->field_monitoring->value = '0';
    $template->field_instance_type->value = 't1.micro';
    $template->field_image_id->target_id = 1;

    // Set the IAM role which can be NULL or something.
    $iam_role_index = array_rand($iam_roles);
    if ($iam_role_index == 0) {
      $template->field_iam_role = NULL;
    }
    else {
      $template->field_iam_role = str_replace(
        'role/',
        'instance-profile/',
        $iam_roles[$iam_role_index]['Arn']
      );
    }

    $template->save();
  }

  /**
   * Add instance mock data.
   *
   * @param string $name
   *   Instance name.
   * @param string $key_pair_name
   *   Keypair name.
   * @param string $state
   *   Instance state.
   * @param string $schedule_value
   *   Schedule value.
   *
   * @return string
   *   The instance id.
   */
  private function addInstanceMockData($name, $key_pair_name, $state = 'running', $schedule_value = '') {
    $mock_data = $this->getMockDataFromConfig();
    $vars = $this->getMockDataTemplateVars();
    $vars['name'] = $name;
    $vars['key_name'] = $key_pair_name;
    $vars['state'] = $state;
    $vars['uid'] = $this->loggedInUser->id();
    $instance_mock_data_content = $this->getMockDataFileContent(get_class($this), $vars, '_instance');

    $instance_mock_data = Yaml::decode($instance_mock_data_content);
    // OwnerId and ReservationId need to be set.
    $mock_data['DescribeInstances']['Reservations'][0]['OwnerId'] = $this->random->name(8, TRUE);
    $mock_data['DescribeInstances']['Reservations'][0]['ReservationId'] = $this->random->name(8, TRUE);

    // Add Schedule information if available.
    if (!empty($schedule_value)) {
      $instance_mock_data['Tags'][] = [
        'Key' => 'Schedule',
        'Value' => $schedule_value,
      ];
    }

    $mock_data['DescribeInstances']['Reservations'][0]['Instances'][] = $instance_mock_data;
    $this->updateMockDataToConfig($mock_data);
    return $vars['instance_id'];
  }

  /**
   * Delete first instance in mock data.
   */
  private function deleteFirstInstanceInMockData() {
    $mock_data = $this->getMockDataFromConfig();
    $instances = $mock_data['DescribeInstances']['Reservations'][0]['Instances'];
    array_shift($instances);
    $mock_data['DescribeInstances']['Reservations'][0]['Instances'] = $instances;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update security group in mock data.
   *
   * @param string $security_group_name1
   *   Security group name1.
   * @param string $security_group_name2
   *   Security group name2.
   */
  private function updateSecurityGroupsInMockData($security_group_name1, $security_group_name2) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeInstances']['Reservations'][0]['Instances'][0]['SecurityGroups']
      = [['GroupName' => $security_group_name1], ['GroupName' => $security_group_name2]];
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update describe security groups in mock data.
   *
   * @param array $security_groups
   *   Security groups array.
   */
  private function updateDescribeSecurityGroupsInMockData(array $security_groups) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeSecurityGroups']['SecurityGroups'] = $security_groups;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update instance type in mock data.
   *
   * @param string $instance_type
   *   Instance type.
   */
  private function updateInstanceTypeInMockData($instance_type) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeInstances']['Reservations'][0]['Instances'][0]['InstanceType']
      = $instance_type;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update instance in mock data.
   *
   * @param int $instance_index
   *   Instance index.
   * @param string $name
   *   Instance name.
   */
  private function updateInstanceInMockData($instance_index, $name) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeInstances']['Reservations'][0]['Instances'][$instance_index]['Tags'][0]['Value'] = $name;
    $mock_data['DescribeInstances']['Reservations'][0]['Instances'][$instance_index]['State']['Name'] = 'stopped';
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update image creation in mock data.
   *
   * @param string $image_id
   *   Image id.
   * @param string $image_name
   *   Image name.
   * @param string $image_state
   *   Image state.
   */
  private function updateImageCreationInMockData($image_id, $image_name, $image_state) {
    $mock_data = $this->getMockDataFromConfig();
    $vars = [
      'image_id' => $image_id,
      'name' => $image_name,
      'state' => $image_state,
    ];
    // Unset DescribeImages so that the state can be updated.
    unset($mock_data['DescribeImages']);
    unset($mock_data['CreateImage']);
    $image_mock_data_content = $this->getMockDataFileContent('Drupal\Tests\aws_cloud\Functional\Ec2\ImageTest', $vars);
    $image_mock_data = Yaml::decode($image_mock_data_content);
    $image_mock_data['CreateImage']['ImageId'] = $image_id;
    $image_mock_data['DescribeImages']['Images'][0]['ImageId'] = $image_id;
    $this->updateMockDataToConfig(array_merge($image_mock_data, $mock_data));
  }

  /**
   * Create random security groups.
   *
   * @return string[][]
   *   random security groups array.
   */
  private function createRandomSecurityGroups() {
    $random = $this->random;

    $security_groups = [];
    $count = rand(1, 10);
    for ($i = 0; $i < $count; $i++) {
      $security_groups[] = [
        'GroupId' => 'sg-' . $this->getRandomAwsId(),
        'GroupName' => $random->name(10, TRUE),
      ];
    }

    return $security_groups;
  }

  /**
   * Update schedule tag in mock data.
   *
   * @param string $schedule_value
   *   Schedule value.
   */
  private function updateScheduleTagInMockData($schedule_value) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeInstances']['Reservations'][0]['Instances'][0]['Tags'][0]['Name'] = 'Schedule';
    $mock_data['DescribeInstances']['Reservations'][0]['Instances'][0]['Tags'][0]['Value'] = $schedule_value;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Tests updating instances.
   */
  public function testUpdateInstanceList() {
    $cloud_context = $this->cloudContext;

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Add an empty value to IAM roles.
    $iam_roles = array_merge([[]], $iam_roles);

    $this->createServerTemplate($iam_roles);

    // Create Instances in mock data.
    $add = $this->createInstanceTestData();
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $instance_id = $this->addInstanceMockData($add[$i]['name'], $add[$i]['key_pair_name']);
      $add[$i]['instance_id'] = $instance_id;
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Instance #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Instances.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Confirm the detailed view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/edit");
      $this->assertNoLink(t('Start'));
      $this->assertLink(t('Stop'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/stop");
      $this->assertLink(t('Reboot'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/reboot");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/terminate");
      $this->assertNoLink(t('Associate Elastic IP'));
      $this->assertLink(t('List AWS Cloud Instances'));
      // Click 'Refresh'.
      $this->clickLink(t('List AWS Cloud Instances'));
      $this->assertResponse(200, t('Edit | List | HTTP 200: Instance #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      // Confirm the edit view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/$num/edit");
      $this->assertNoLink(t('Edit'));
      $this->assertNoLink(t('Start'));
      $this->assertLink(t('Stop'));
      $this->assertLink(t('Reboot'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/reboot");
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/stop");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/terminate");
      $this->assertNoLink(t('Associate Elastic IP'));
    }

    // Edit Instance information.
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      $this->createTestNetworkInterface($i, $add[$i]['instance_id']);
      $this->createTestElasticIp($i);

      // Change Instance Name in mock data.
      $add[$i]['name'] = "Instance #$num - " . date('Y/m/d - ') . $this->random->name(8, TRUE);
      $this->updateInstanceInMockData($num - 1, $add[$i]['name']);

    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Instance #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Instances.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Confirm the detailed view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/edit");
      $this->assertLink(t('Start'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/start");
      $this->assertNoLink(t('Stop'));
      $this->assertNoLink(t('Reboot'));
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/terminate");
      $this->assertLink(t('Associate Elastic IP'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/associate_elastic_ip");

      // Confirm the edit view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance/$num/edit");
      $this->assertNoLink(t('Edit'));
      $this->assertLink(t('Start'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/start");
      $this->assertNoLink(t('Stop'));
      $this->assertNoLink(t('Reboot'));
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/terminate");
      $this->assertLink(t('Associate Elastic IP'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/instance/$num/associate_elastic_ip");
    }

    // Delete Instances in mock data.
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $this->deleteFirstInstanceInMockData();
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Instance #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Instances.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_INSTANCE_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }
  }

}
