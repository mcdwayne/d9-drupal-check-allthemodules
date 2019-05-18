<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

/**
 * Tests AWS Cloud Key Pair.
 *
 * @group AWS Cloud
 */
class KeyPairTest extends AwsCloudTestCase {

  const AWS_CLOUD_KEY_PAIR_REPEAT_COUNT = 3;

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'list aws cloud key pair',
      'add aws cloud key pair',
      'view aws cloud key pair',
      'edit aws cloud key pair',
      'delete aws cloud key pair',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMockDataTemplateVars() {
    $key_fingerprint_parts = [];
    for ($i = 0; $i < 20; $i++) {
      $key_fingerprint_parts[] = sprintf('%02x', rand(0, 255));
    }

    $key_material = '---- BEGIN RSA PRIVATE KEY ----'
      . $this->random->name(871, TRUE)
      . '-----END RSA PRIVATE KEY-----';
    return [
      'key_name' => $this->random->name(15, TRUE),
      'key_fingerprint' => implode(':', $key_fingerprint_parts),
      'key_material' => $key_material,
    ];
  }

  /**
   * Tests CRUD for Key Pair information.
   */
  public function testKeyPair() {
    $cloud_context = $this->cloudContext;

    // List Key Pair for Amazon EC2.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/key_pair");
    $this->assertResponse(200, t('List | HTTP 200: Key Pair'));
    $this->assertNoText(t('Notice'), t('List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('List | Make sure w/o Warnings'));

    // Add a new Key Pair.
    $add = $this->createKeyPairTestData();
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      $this->reloadMockData();

      $num = $i + 1;

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/key_pair/add",
                            $add[$i],
                            t('Save'));

      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Key Pair #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Key Pair "@key_pair_name', [
          '@key_pair_name' => $add[$i]['key_pair_name'],
        ]),
        t('Confirm Message: Add | The AWS Cloud Key Pair "@key_pair_name" has been created.', [
          '@key_pair_name' => $add[$i]['key_pair_name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/key_pair");
      $this->assertResponse(200, t('Add | List | HTTP 200: Key Pair #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | List | Make sure w/o Warnings'));

      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($add[$j]['key_pair_name'],
                        t('Make sure w/ Listing: @key_pair_name',
                                          ['@key_pair_name' => $add[$j]['key_pair_name']]));
      }
    }

    // Delete Key Pair.
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/key_pair/$num/delete");
      $this->assertResponse(200, t('Delete | HTTP 200: Key Pair #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/key_pair/$num/delete",
                            [],
                            t('Delete'));

      $this->assertResponse(200, t('Delete | HTTP 200: The Cloud Key Pair #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->assertText($add[$i]['key_pair_name'], t('Name: @key_pair_name', ['@key_pair_name' => $add[$i]['key_pair_name']]));
      $this->assertText(
        t('The AWS Cloud Key Pair "@key_pair_name" has been deleted.', [
          '@key_pair_name' => $add[$i]['key_pair_name'],
        ]),
        t('Confirm Message: Delete | The AWS Cloud Key Pair "@key_pair_name" has been deleted.', [
          '@key_pair_name' => $add[$i]['key_pair_name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/key_pair");
      $this->assertResponse(200, t('HTTP 200: Delete | Key Pair #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertNoText($add[$i]['key_pair_name'],
          t('Delete | List | Make sure w/ Listing: @key_pair_name', [
            '@key_pair_name' => $add[$i]['key_pair_name'],
          ]));
      }
    }
  }

  /**
   * Create KeyPair test data.
   */
  private function createKeyPairTestData() {
    $data = [];

    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      // Input Fields.
      $data[$i] = [
        'key_pair_name' => $this->random->name(15, TRUE),
      ];
    }
    return $data;
  }

  /**
   * Test updating key pair list.
   */
  public function testUpdateKeyPairList() {

    $cloud_context = $this->cloudContext;

    // Add a new Key Pair.
    $add = $this->createKeyPairTestData();
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->addKeyPairMockData($add[$i]['key_pair_name']);
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/key_pair");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Key Pair #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['key_pair_name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['key_pair_name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Key Pairs.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['key_pair_name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['key_pair_name'],
          ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Confirm the detailed view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/key_pair/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/key_pair/$num/edit");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/key_pair/$num/delete");
      $this->assertLink(t('List AWS Cloud Key Pairs'));
      // Click 'Refresh'.
      $this->clickLink(t('List AWS Cloud Key Pairs'));
      $this->assertResponse(200, t('Edit | List | HTTP 200: Key Pair #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      // Confirm the edit view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/key_pair/$num/edit");
      $this->assertNoLink(t('Edit'));
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/key_pair/$num/delete");
    }

    // Edit Key Pair information.
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Change Key Pair Name in mock data.
      $add[$i]['key_pair_name'] = $this->random->name(15, TRUE);
      $this->updateKeyPairInMockData($num - 1, $add[$i]['key_pair_name']);

    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/key_pair");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Key Pair #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['key_pair_name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['key_pair_name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Key Pairs.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['key_pair_name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['key_pair_name'],
          ]));
    }

    // Delete Key Pair in mock data.
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      $this->deleteFirstKeyPairInMockData();
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/key_pair");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Key Pair #@num', ['@num' => $num + 1]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['key_pair_name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['key_pair_name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Key Pairs.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_KEY_PAIR_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['key_pair_name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['key_pair_name'],
          ]));
    }

  }

  /**
   * Add Key Pair mock data.
   *
   * @param string $name
   *   The key pair name.
   */
  private function addKeyPairMockData($name) {
    $mock_data = $this->getMockDataFromConfig();
    $vars = $this->getMockDataTemplateVars();
    $key_pair = [
      'KeyName' => $name,
      'KeyFingerprint' => $vars['key_fingerprint'],
    ];
    $mock_data['DescribeKeyPairs']['KeyPairs'][] = $key_pair;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update Key Pair mock data.
   *
   * @param int $key_pair_index
   *   The index of key pair.
   * @param string $name
   *   The key pair name.
   */
  private function updateKeyPairInMockData($key_pair_index, $name) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeKeyPairs']['KeyPairs'][$key_pair_index]['KeyName'] = $name;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Delete first Key Pair in mock data.
   */
  private function deleteFirstKeyPairInMockData() {
    $mock_data = $this->getMockDataFromConfig();
    $key_pairs = $mock_data['DescribeKeyPairs']['KeyPairs'];
    array_shift($key_pairs);
    $mock_data['DescribeKeyPairs']['KeyPairs'] = $key_pairs;
    $this->updateMockDataToConfig($mock_data);
  }

}
