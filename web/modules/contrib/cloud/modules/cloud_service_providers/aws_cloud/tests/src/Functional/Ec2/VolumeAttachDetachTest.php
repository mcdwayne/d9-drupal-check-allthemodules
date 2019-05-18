<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

/**
 * Volume attach and detach test case.
 *
 * @group AWS Cloud
 */
class VolumeAttachDetachTest extends AwsCloudTestCase {

  /**
   * Number of times to repeat the test.
   */
  const MAX_TEST_REPEAT_COUNT = 3;

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
    ];
  }

  /**
   * Test volume attach.
   */
  public function testVolumeAttachDetach() {
    $this->repeatTestVolumeAttachDetach(self::MAX_TEST_REPEAT_COUNT);
  }

  /**
   * Repeating test volume attach detach.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestVolumeAttachDetach($max_test_repeat_count = 1) {

    for ($i = 1; $i <= $max_test_repeat_count; $i++) {
      // Setup for testing.
      $device_name = $this->random->name(8, TRUE);

      // Setup a test instance.
      $instance = $this->createTestInstance($i);
      $instance_id = $instance->getInstanceId();

      // Setup a test volume.
      $volume = $this->createTestVolume(
        $i,
        'vol-' . $this->getRandomAwsId(),
        "volume-name #$i - " . $this->random->name(32, TRUE),
        $this->cloudContext,
        $this->loggedInUser->id()
      );
      $volume_id = $volume->getVolumeId();

      $attach_data = [
        'device_name' => $device_name,
        'instance_id' => $instance_id,
      ];

      // Test attach.
      $this->updateVolumeInMockData('AttachVolume', $device_name, $volume_id, $instance_id, 'attaching');
      $this->drupalPostForm("/clouds/aws_cloud/$this->cloudContext/volume/$i/attach",
        $attach_data,
        t('Attach'));

      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Volume #@num', ['@num' => $i]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText(t('Volume @volume is attaching to @instance', [
        '@instance' => $instance_id,
        '@volume' => $volume_id,
      ]));

      // Test detach.
      $volume->setState('in-use');
      $volume->setAttachmentInformation($instance_id);
      $volume->save();
      $this->updateVolumeInMockData('DetachVolume', $device_name, $volume_id, $instance_id, 'detaching');
      $this->drupalPostForm("/clouds/aws_cloud/$this->cloudContext/volume/$i/detach",
        [],
        t('Detach'));
      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Volume #@num', ['@num' => $i]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText(t('Volume @volume is detaching from @instance', [
        '@instance' => $instance_id,
        '@volume' => $volume_id,
      ]));
    }

  }

  /**
   * Update the volume state and instance.
   */
  private function updateVolumeInMockData($api, $device, $volume_id, $instance_id, $state) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data[$api] = [
      'Device' => $device,
      'InstanceId' => $instance_id,
      'State' => $state,
      'VolumeId' => $volume_id,
    ];
    $this->updateMockDataToConfig($mock_data);
  }

}
