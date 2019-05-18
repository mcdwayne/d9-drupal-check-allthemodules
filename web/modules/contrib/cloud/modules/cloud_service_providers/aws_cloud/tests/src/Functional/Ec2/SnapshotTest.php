<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

use Drupal\Tests\aws_cloud\Functional\Utils;

// Updated by yas 2016/06/23
// Updated by yas 2016/06/02
// Updated by yas 2016/05/31
// Updated by yas 2016/05/29
// Updated by yas 2016/05/28
// Updated by yas 2016/05/25
// Updated by yas 2016/05/24
// Created by yas 2016/05/23.
/**
 * Tests AWS Cloud Snapshot.
 *
 * @group AWS Cloud
 */
class SnapshotTest extends AwsCloudTestCase {

  const AWS_CLOUD_SNAPSHOT_REPEAT_COUNT = 3;

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'list aws cloud snapshot',
      'add aws cloud snapshot',
      'view any aws cloud snapshot',
      'edit any aws cloud snapshot',
      'delete any aws cloud snapshot',

      'view any aws cloud volume',
      'add aws cloud volume',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMockDataTemplateVars() {
    return [
      'snapshot_id' => 'snap-' . $this->getRandomAwsId(),
      'cidr_block' => Utils::getRandomCidr(),
      'group_id' => 'sg-' . $this->getRandomAwsId(),
      'start_time' => date('c'),
    ];
  }

  /**
   * Tests CRUD for Snapshot information.
   */
  public function testSnapshot() {
    $cloud_context = $this->cloudContext;

    // List Snapshot for Amazon EC2.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot");
    $this->assertResponse(200, t('List | HTTP 200: Snapshot'));
    $this->assertNoText(t('Notice'), t('List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('List | Make sure w/o Warnings'));

    // Create random volumes.
    $volumes = $this->createRandomVolumes();
    $this->updateDescribeVolumesInMockData($volumes);

    // Create the volume entities.
    $v = 1;
    foreach ($volumes as $volume) {
      $this->createTestVolume(
        $v,
        $volume['VolumeId'],
        $volume['Name'],
        $cloud_context,
        Utils::getRandomUid()
      );
      $v++;
    }

    // Add a new Snapshot.
    $add = $this->createSnapshotTestData();
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {
      $this->reloadMockData();

      // Set volume ID.
      $add[$i]['volume_id'] = $volumes[array_rand($volumes)]['VolumeId'];

      $num = $i + 1;

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/snapshot/add",
                            $add[$i],
                            t('Save'));

      $this->assertResponse(200, t('HTTP 200: Add | A New AWS Cloud Snapshot #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Snapshot "@name', ['@name' => $add[$i]['name']]),
        t('Confirm Message: The AWS Cloud Snapshot "@name" has been created.', [
          '@name' => $add[$i]['name'],
        ]));
      $this->assertText($add[$i]['name'],
                     t('key_pair: @name', [
                       '@name' => $add[$i]['name'],
                     ]));

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot");
      $this->assertResponse(200, t('HTTP 200: List | Snapshot #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | List | Make sure w/o Warnings'));
    }

    // Edit an Snapshot information.
    $edit = $this->createSnapshotTestData();
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      unset($edit[$i]['volume_id']);
      unset($edit[$i]['description']);

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/snapshot/$num/edit",
                            $edit[$i],
                            t('Save'));

      $this->assertResponse(200, t('Edit | HTTP 200: A New AWS Cloud Snapshot #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Snapshot "@name" has been saved.', ['@name' => $edit[$i]['name']]),
        t('Confirm Message: The AWS Cloud Snapshot "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ]));

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot");
      $this->assertResponse(200, t('Edit | List | HTTP 200: Snapshot #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($edit[$i]['name'],
                        t('Edit | List | Make sure w/ Listing: @name', [
                          '@name' => $edit[$i]['name'],
                        ]));
      }
    }

    // Delete Snapshot.
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot/$num/delete");
      $this->assertResponse(200, t('Delete | HTTP 200: Snapshot #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/snapshot/$num/delete",
                            [],
                            t('Delete'));

      $this->assertResponse(200, t('Delete | HTTP 200: The Cloud Snapshot #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->assertText($edit[$i]['name'], t('Name: @name', ['@name' => $edit[$i]['name']]));
      $this->assertText(
        t('The AWS Cloud Snapshot "@name" has been deleted.', ['@name' => $edit[$i]['name']]),
        t('Confirm Message: Delete | The AWS Cloud Snapshot "@name" has been deleted.', ['@name' => $edit[$i]['name']])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot");
      $this->assertResponse(200, t('Delete | HTTP 200: Snapshot', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | List | Make sure w/o Warnings'));
    }
  }

  /**
   * Test updating snapshots.
   */
  public function testUpdateSnapshot() {
    $this->repeatTestUpdateSnapshot(self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT);
  }

  /**
   * Repeating test updating snapshot.
   *
   * @param int $max_count
   *   Max test repeating count.
   */
  private function repeatTestUpdateSnapshot($max_count) {
    $cloud_context = $this->cloudContext;

    for ($i = 0; $i < $max_count; $i++) {
      $num = $i + 1;
      $test_cases = $this->createUpdateSnapshotTestCases();
      $this->updateDescribeSnapshotsInMockData($test_cases);
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot/update");
      $this->assertResponse(200, t('Update | HTTP 200: Snapshot', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Update | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Update | List | Make sure w/o Warnings'));

      foreach ($test_cases as $test_case) {
        $this->assertLink(
          isset($test_case['name'])
          ? $test_case['name']
          : $test_case['id']
        );
      }
    }
  }

  /**
   * Test updating snapshot list.
   */
  public function testUpdateSnapshotList() {

    $cloud_context = $this->cloudContext;

    // Add a new Snapshot.
    $add = $this->createSnapshotTestData();
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->addSnapshotMockData($add[$i]['name'], $add[$i]['volume_id'], $add[$i]['description']);
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Snapshot #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Snapshots.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Confirm the detailed view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/snapshot/$num/edit");
      $this->assertLink(t('Create Volume'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/add?snapshot_id=" . $add[$i]['name']);
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/snapshot/$num/delete");
      $this->assertLink(t('List AWS Cloud Snapshots'));
      // Click 'Refresh'.
      $this->clickLink(t('List AWS Cloud Snapshots'));
      $this->assertResponse(200, t('Edit | List | HTTP 200: Snapshot #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      // Confirm the edit view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot/$num/edit");
      $this->assertNoLink(t('Edit'));
      $this->assertLink(t('Create Volume'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/volume/add?snapshot_id=" . $add[$i]['name']);
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/snapshot/$num/delete");
      $this->assertNoLink('Edit');

      // Click "Create Volume" link.
      $this->clickLink(t('Create Volume'));

      // Make sure creating page.
      $this->assertResponse(200);
      $this->assertText(t('Add AWS Cloud Volume'));

      // Make sure the default value of field snapshot_id.
      $this->assertSession()->fieldValueEquals('snapshot_id', $add[$i]['name']);
    }

    // Edit Snapshot information.
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Change Snapshot Name in mock data.
      $add[$i]['name'] = 'snap-' . $this->getRandomAwsId();
      $this->updateSnapshotInMockData($num - 1, $add[$i]['name']);

    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Snapshot #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Snapshots.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Delete Snapshot in mock data.
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {
      $this->deleteFirstSnapshotInMockData();
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Snapshot #@num', ['@num' => $num + 1]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Snapshots.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }
  }

  /**
   * Test the operation of creating volume.
   */
  public function testCreateVolumeOperation() {
    $this->repeatTestCreateVolumeOperation(
      self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT
    );
  }

  /**
   * Repeat testing the operation of creating volume.
   *
   * @param int $max_count
   *   Max test repeating count.
   */
  private function repeatTestCreateVolumeOperation($max_count) {
    $cloud_context = $this->cloudContext;

    // Create random volumes.
    $volumes = $this->createRandomVolumes();
    $this->updateDescribeVolumesInMockData($volumes);

    // Create the volume entities.
    $v = 1;
    foreach ($volumes as $volume) {
      $this->createTestVolume(
        $v,
        $volume['VolumeId'],
        $volume['Name'],
        $cloud_context,
        Utils::getRandomUid()
      );
      $v++;
    }

    // Add a new Snapshot.
    $add = $this->createSnapshotTestData();
    for ($i = 0; $i < $max_count; $i++) {
      $this->reloadMockData();

      // Set volume ID.
      $add[$i]['volume_id'] = $volumes[array_rand($volumes)]['VolumeId'];
      $num = $i + 1;
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/snapshot/add",
                            $add[$i],
                            t('Save'));
      $this->assertResponse(200);

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/snapshot");
      $this->assertResponse(200, t('HTTP 200: List | Snapshot #@num', ['@num' => $num]));
      $this->assertText(t('Create Volume'));

      // Add snapshot to DescribeSnapshots of Mock data.
      $snapshot_id = $this->latestTemplateVars['snapshot_id'];
      $this->addDescribeSnapshotsInMockData($snapshot_id);

      // Click "Create Volume" link.
      $this->clickLink(t('Create Volume'), $i);

      // Make sure creating page.
      $this->assertResponse(200);
      $this->assertText(t('Add AWS Cloud Volume'));

      // Make sure the default value of field snapshot_id.
      $this->assertSession()->fieldValueEquals('snapshot_id', $snapshot_id);
    }
  }

  /**
   * Create update snapshot test cases.
   *
   * @return string[][]
   *   test cases array.
   */
  private function createUpdateSnapshotTestCases() {
    $test_cases = [];
    $random = $this->random;

    // Only id.
    $test_cases[] = ['id' => 'snap-' . $this->getRandomAwsId()];

    // Name and id.
    $test_cases[] = [
      'name' => 'Snapshot Name ' . $random->name(32, TRUE),
      'id' => 'snap-' . $this->getRandomAwsId(),
    ];

    return $test_cases;
  }

  /**
   * Update describe snapshot in mock data.
   *
   * @param array $test_cases
   *   Test cases array.
   */
  private function updateDescribeSnapshotsInMockData(array $test_cases) {
    $random = $this->random;

    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeSnapshots'] = ['Snapshots' => []];
    foreach ($test_cases as $test_case) {
      $snapshot = [
        'SnapshotId' => $test_case['id'],
        'VolumeSize' => 10,
        'Description' => $random->string(32, TRUE),
        'State' => 'completed',
        'VolumeId' => 'vol-' . $this->getRandomAwsId(),
        'Progress' => '100%',
        'Encrypted' => TRUE,
        'KmsKeyId' => 'arn:aws:kms:us-east-1:123456789012:key/6876fb1b-example',
        'OwnerId' => strval(rand(100000000000, 999999999999)),
        'OwnerAlias' => 'amazon',
        'StateMessage' => $random->string(32, TRUE),
        'StartTime' => date('c'),
      ];

      if (isset($test_case['name'])) {
        $snapshot['Tags'] = [
          ['Key' => 'Name', 'Value' => $test_case['name']],
        ];
      }

      $mock_data['DescribeSnapshots']['Snapshots'][] = $snapshot;
    }

    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Create snapshot test data.
   *
   * @return string[][]
   *   test data array.
   */
  private function createSnapshotTestData() {
    $data = [];

    for ($i = 0; $i < self::AWS_CLOUD_SNAPSHOT_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Input Fields.
      $data[$i] = [
        'name'        => "Name #$num - " . $this->random->name(32, TRUE),
        'volume_id'   => "vol-" . $this->getRandomAwsId(),
        'description' => "Description #$num - " . $this->random->name(64, TRUE),
      ];
    }
    return $data;
  }

  /**
   * Add Snapshot mock data.
   *
   * @param string $name
   *   The snapshot name.
   * @param string $volume_id
   *   The volume id.
   * @param string $description
   *   The description.
   */
  private function addSnapshotMockData(&$name, $volume_id, $description) {
    $mock_data = $this->getMockDataFromConfig();
    $vars = $this->getMockDataTemplateVars();

    $snapshot = [
      'SnapshotId' => $vars['snapshot_id'],
      'VolumeSize' => 10,
      'Description' => $description,
      'State' => 'completed',
      'VolumeId' => $volume_id,
      'Progress' => 100,
      'Encrypted' => FALSE,
      'KmsKeyId' => NULL,
      'OwnerId' => $this->random->name(8, TRUE),
      'OwnerAlias' => NULL,
      'StateMessage' => NULL,
      'StartTime' => $vars['start_time'],
    ];

    $name = $snapshot['SnapshotId'];

    $mock_data['DescribeSnapshots']['Snapshots'][] = $snapshot;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update Snapshot mock data.
   *
   * @param int $snapshot_index
   *   The index of Snapshot.
   * @param string $name
   *   The snapshot name.
   */
  private function updateSnapshotInMockData($snapshot_index, $name) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeSnapshots']['Snapshots'][$snapshot_index]['Tags'][0] = ['Key' => 'Name', 'Value' => $name];
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Delete first Snapshot in mock data.
   */
  private function deleteFirstSnapshotInMockData() {
    $mock_data = $this->getMockDataFromConfig();
    $snapshots = $mock_data['DescribeSnapshots']['Snapshots'];
    array_shift($snapshots);
    $mock_data['DescribeSnapshots']['Snapshots'] = $snapshots;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Create random volumes.
   *
   * @return array
   *   random security groups array.
   */
  private function createRandomVolumes() {
    $volumes = [];
    $count = rand(1, 10);
    for ($i = 0; $i < $count; $i++) {
      $volumes[] = [
        'VolumeId' => 'vol-' . $this->getRandomAwsId(),
        'Name' => "volume-name #$i - " . $this->random->name(32, TRUE),
      ];
    }

    return $volumes;
  }

  /**
   * Update describe volumes in mock data.
   *
   * @param array $volumes
   *   Volumes array.
   */
  private function updateDescribeVolumesInMockData(array $volumes) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeVolumes']['Volumes'] = $volumes;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Add describe snapshots in mock data.
   *
   * @param string $snapshot_id
   *   Snapshot id.
   */
  private function addDescribeSnapshotsInMockData($snapshot_id) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeSnapshots'] = [
      'Snapshots' => [
        [
          'SnapshotId' => $snapshot_id,
        ],
      ],
    ];
    $this->updateMockDataToConfig($mock_data);
  }

}
