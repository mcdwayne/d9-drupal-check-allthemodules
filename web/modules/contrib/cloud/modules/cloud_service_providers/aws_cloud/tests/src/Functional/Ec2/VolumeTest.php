<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

use Drupal\Tests\aws_cloud\Functional\Utils;

// Updated by yas 2016/06/23
// Updated by yas 2016/06/05
// Updated by yas 2016/06/02
// Updated by yas 2016/05/31
// Updated by yas 2016/05/29
// Updated by yas 2016/05/25
// Created by yas 2016/05/23.
/**
 * Tests AWS Cloud Volume.
 *
 * @group AWS Cloud
 */
class VolumeTest extends AwsCloudTestCase {

  const AWS_CLOUD_VOLUME_REPEAT_COUNT = 3;

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'list aws cloud volume',
      'add aws cloud volume',
      'view any aws cloud volume',
      'edit any aws cloud volume',
      'delete any aws cloud volume',

      'add aws cloud snapshot',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMockDataTemplateVars() {
    return [
      'volume_id' => 'vol-' . $this->getRandomAwsId(),
      'create_time' => date('c'),
      'uid' => Utils::getRandomUid(),
    ];
  }

  /**
   * Tests CRUD for Volume information.
   */
  public function testVolume() {
    $cloud_context = $this->cloudContext;

    // List Volume for Amazon EC2.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume");
    $this->assertResponse(200, t('HTTP 200: List'));
    $this->assertNoText(t('Notice'), t('List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('List | Make sure w/o Warnings'));

    // Add a new Volume.
    $delete_count = 0;
    $add = $this->createVolumeTestData();
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {
      $this->reloadMockData();

      $state = $this->createRandomState();
      $volume_id = 'vol-' . $this->getRandomAwsId();
      $snapshot_name = 'snapshot-name' . $this->random->name(10, TRUE);
      $this->updateCreateVolumeInMockData($state, $volume_id);
      $this->createTestSnapshot($add[$i]['snapshot_id'], $snapshot_name, $cloud_context);
      $this->updateDescribeSnapshotsInMockData($add[$i]['snapshot_id'], $snapshot_name);
      if ($state != 'in-use') {
        $delete_count++;
      }

      $num = $i + 1;

      // Make sure checkbox Encrypted is checked.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume/add");
      $this->assertSession()->checkboxChecked('encrypted');

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/volume/add",
                            $add[$i],
                            t('Save'));

      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Volume #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText($add[$i]['name'], t('Add | Volume: @name', ['@name' => $add[$i]['name']]));
      $this->assertText(
        t('The AWS Cloud Volume "@name', ['@name' => $add[$i]['name']]),
        t('Confirm Message: Add | The AWS Cloud Volume "@name" has been created.', [
          '@name' => $add[$i]['name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume");
      $this->assertResponse(200, t('HTTP 200: Add | List | Volume #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | List | Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($add[$i]['name'],
                        t('Add | List | Make sure w/ Listing: @name', [
                          '@name' => $add[$i]['name'],
                        ]));
      }

      // Assert delete link count.
      if ($delete_count > 0) {
        $this->assertLink(t('Delete'), $delete_count - 1);
      }

      // Make sure view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume/$num");
      $this->assertResponse(200, t('HTTP 200: Add | View | Volume #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | View | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | View | Make sure w/o Warnings'));
      $this->assertText($volume_id,
                        t('Add | View | Make sure volume id: @volume_id', [
                          '@volume_id' => $volume_id,
                        ]));
      $this->assertText($add[$i]['snapshot_id'],
                        t('Add | View | Make sure snapshot id: @snapshot_id', [
                          '@snapshot_id' => $add[$i]['snapshot_id'],
                        ]));
      $this->assertText($snapshot_name,
                        t('Add | View | Make sure snapshot name: @snapshot_name', [
                          '@snapshot_name' => $snapshot_name,
                        ]));
    }

    // Edit an Volume information.
    $edit = $this->createVolumeTestData();
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      unset($edit[$i]['snapshot_id']);
      unset($edit[$i]['size']);
      unset($edit[$i]['availability_zone']);
      unset($edit[$i]['iops']);
      unset($edit[$i]['encrypted']);
      unset($edit[$i]['volume_type']);

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/volume/$num/edit",
                            $edit[$i],
                            t('Save'));

      $this->assertResponse(200, t('HTTP 200: Edit | A New AWS Cloud Volume #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Volume "@name" has been saved.', ['@name' => $edit[$i]['name']]),
        t('Confirm Message: Edit | The AWS Cloud Volume "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume");
      $this->assertResponse(200, t('Edit | List | HTTP 200: Volume #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($edit[$i]['name'],
                        t('Edit | List | Make sure w/ Listing: @name', [
                          '@name' => $edit[$i]['name'],
                        ]));
      }
    }

    // Delete Volume.
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume/$num/delete");
      $this->assertResponse(200, t('Delete | HTTP 200: Volume #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/volume/$num/delete",
                            [],
                            t('Delete'));

      $this->assertResponse(200, t('Delete | HTTP 200: The Cloud Volume #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->assertText($edit[$i]['name'], t('Delete | Name: @name', ['@name' => $edit[$i]['name']]));
      $this->assertText(
        t('The AWS Cloud Volume "@name" has been deleted.', ['@name' => $edit[$i]['name']]),
        t('Confirm Message: Delete | The AWS Cloud Volume "@name" has been deleted.', [
          '@name' => $edit[$i]['name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume");
      $this->assertResponse(200, t('Delete | HTTP 200: Volume #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
    }
  }

  /**
   * Create volume test data.
   *
   * @return string[][]|number[][]
   *   test data array.
   */
  private function createVolumeTestData() {
    $data = [];

    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Input Fields.
      $data[$i] = [
        'name'              => "volume-name #$num - " . $this->random->name(32, TRUE),
        'snapshot_id'       => "snap-" . $this->getRandomAwsId(),
        'size'              => $num * 10,
        'availability_zone' => "us-west-$num",
        'iops'              => $num * 1000,
        'encrypted'         => $num % 2,
        'volume_type'       => 'io1',
      ];
    }
    return $data;
  }

  /**
   * Create random state.
   *
   * @return string
   *   random state.
   */
  private function createRandomState() {
    $states = ['creating', 'in-use'];
    return $states[array_rand($states)];
  }

  /**
   * Update create volume in mock data.
   *
   * @param string $state
   *   Volume state.
   * @param string $volume_id
   *   Volume id.
   */
  private function updateCreateVolumeInMockData($state, $volume_id) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['CreateVolume']['State'] = $state;
    $mock_data['CreateVolume']['VolumeId'] = $volume_id;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update describe snapshots in mock data.
   *
   * @param string $snapshot_id
   *   Snapshot id.
   * @param string $snapshot_name
   *   Snapshot name.
   */
  private function updateDescribeSnapshotsInMockData($snapshot_id, $snapshot_name) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeSnapshots'] = [
      'Snapshots' => [
        [
          'SnapshotId' => $snapshot_id,
          'Tags' => [['Key' => 'Name', 'Value' => $snapshot_name]],
        ],
      ],
    ];
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Test updating volume list.
   */
  public function testUpdatingVolumeList() {

    $cloud_context = $this->cloudContext;

    // Add a new Volume.
    $add = $this->createVolumeTestData();
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->addVolumeMockData($add[$i]);
      $snapshot_name = 'snapshot-name' . $this->random->name(10, TRUE);
      $this->createTestSnapshot($add[$i]['snapshot_id'], $snapshot_name, $cloud_context);
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Volume #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Volumes.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/$num/edit");
      $this->assertLink(t('Attach'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/$num/attach");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/$num/delete");
      $this->assertLink(t('List AWS Cloud Volumes'));
      // Click 'Refresh'.
      $this->clickLink(t('List AWS Cloud Volumes'));
      $this->assertResponse(200, t('Edit | List | HTTP 200: Volume #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume/$num/edit");
      $this->assertNoLink('Edit');
      $this->assertLink(t('Attach'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/$num/attach");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/$num/delete");
    }

    // Edit Volume information.
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Setup a test instance.
      $instance = $this->createTestInstance($i);
      $instance_id = $instance->getInstanceId();

      // Change Volume Name in mock data.
      $add[$i]['name'] = "volume-name #$num - " . $this->random->name(32, TRUE);

      $this->updateVolumeInMockData($num - 1, $add[$i]['name'], $instance_id);

    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Volume #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Volumes.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {

      $num = $i + self::AWS_CLOUD_VOLUME_REPEAT_COUNT + 1;

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/$num/edit");
      $this->assertLink(t('Detach'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/$num/detach");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/$num/delete");
    }

    // Delete Volume in mock data.
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {
      $this->deleteFirstVolumeInMockData();
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Volume #@num', ['@num' => $num + 1]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Volumes.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_VOLUME_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }
  }

  /**
   * Test the operation of creating snapshot.
   */
  public function testCreateSnapshotOperation() {
    $this->repeatTestCreateSnapshotOperation(
      self::AWS_CLOUD_VOLUME_REPEAT_COUNT
    );
  }

  /**
   * Repeat testing the operation of creating snapshot.
   *
   * @param int $max_count
   *   Max test repeating count.
   */
  private function repeatTestCreateSnapshotOperation($max_count) {
    $cloud_context = $this->cloudContext;

    $add = $this->createVolumeTestData();
    for ($i = 0; $i < $max_count; $i++) {
      $this->reloadMockData();

      $num = $i + 1;

      $snapshot_name = 'snapshot-name' . $this->random->name(10, TRUE);
      $this->updateDescribeSnapshotsInMockData($add[$i]['snapshot_id'], $snapshot_name);
      $this->createTestSnapshot($add[$i]['snapshot_id'], $snapshot_name, $cloud_context);

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/volume/add",
                            $add[$i],
                            t('Save'));
      $this->assertResponse(200);

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/volume");
      $this->assertResponse(200, t('HTTP 200: List | Volume #@num', ['@num' => $num]));
      $this->assertText(t('Create Snapshot'));

      // Add a volume to DescribeVolumes.
      $volume_id = $this->latestTemplateVars['volume_id'];
      $add[$i]['name'] = $volume_id;
      $this->addVolumeMockData($add[$i]);

      // Click "Create Snapshot" link.
      $this->clickLink(t('Create Snapshot'), $i);

      // Make sure creating page.
      $this->assertResponse(200);
      $this->assertText(t('Add AWS Cloud Snapshot'));

      // Make sure the default value of field volume_id.
      $this->assertSession()->fieldValueEquals('volume_id', $volume_id);
    }
  }

  /**
   * Add Volume mock data.
   *
   * @param array $data
   *   Array of volume data.
   */
  private function addVolumeMockData(array $data) {
    $mock_data = $this->getMockDataFromConfig();
    $vars = $this->getMockDataTemplateVars();

    $volume = [
      'VolumeId' => $data['name'],
      'Attachments' => ['InstanceId' => NULL],
      'State' => 'available',
      'SnapshotId' => $data['snapshot_id'],
      'Size' => $data['size'],
      'VirtualizationType' => NULL,
      'VolumeType' => $data['volume_type'],
      'Iops' => $data['iops'],
      'AvailabilityZone' => $data['availability_zone'],
      'Encrypted' => $data['encrypted'],
      'KmsKeyId' => NULL,
      'CreateTime' => $vars['create_time'],
      'Tags' => [
        [
          'Key' => 'volume_created_by_uid',
          'Value' => Utils::getRandomUid(),
        ],
      ],
    ];

    $mock_data['DescribeVolumes']['Volumes'][] = $volume;
    $this->updateMockDataToConfig($mock_data);

    $snapshot_name = 'snapshot-name' . $this->random->name(10, TRUE);
    $this->updateDescribeSnapshotsInMockData($data['snapshot_id'], $snapshot_name);
  }

  /**
   * Update Volume mock data.
   *
   * @param int $volume_index
   *   The index of Volume.
   * @param string $name
   *   The volume name.
   * @param string $instance_id
   *   The instance id.
   */
  private function updateVolumeInMockData($volume_index, $name, $instance_id) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeVolumes']['Volumes'][$volume_index]['VolumeId'] = $name;
    $mock_data['DescribeVolumes']['Volumes'][$volume_index]['InstanceId'] = $instance_id;
    $mock_data['DescribeVolumes']['Volumes'][$volume_index]['State'] = 'in-use';
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Delete first Volume in mock data.
   */
  private function deleteFirstVolumeInMockData() {
    $mock_data = $this->getMockDataFromConfig();
    $addresses = $mock_data['DescribeVolumes']['Volumes'];
    array_shift($addresses);
    $mock_data['DescribeVolumes']['Volumes'] = $addresses;
    $this->updateMockDataToConfig($mock_data);
  }

}
