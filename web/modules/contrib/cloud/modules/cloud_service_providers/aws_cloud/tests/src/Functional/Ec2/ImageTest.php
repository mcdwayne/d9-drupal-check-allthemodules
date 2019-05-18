<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

/**
 * Tests AWS Cloud Image.
 *
 * @group AWS Cloud
 */
class ImageTest extends AwsCloudTestCase {

  const AWS_CLOUD_IMAGE_REPEAT_COUNT = 3;

  const AWS_UPDATE_IMAGE_LIST_REFRESH_TIME_ADJUSTMENT = 10 * 60;

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'add aws cloud image',
      'list aws cloud image',
      'view any aws cloud image',
      'edit any aws cloud image',
      'delete any aws cloud image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMockDataTemplateVars() {
    return [
      'image_id' => 'ami-' . $this->getRandomAwsId(),
      'account_id' => rand(100000000000, 999999999999),
      'name' => $this->random->name(8, TRUE),
      'kernel_id' => 'aki-' . $this->getRandomAwsId(),
      'ramdisk_id' => 'ari-' . $this->getRandomAwsId(),
      'product_code1' => $this->random->name(8, TRUE),
      'product_code2' => $this->random->name(8, TRUE),
      'image_location' => $this->random->name(16, TRUE),
      'state_reason_message' => $this->random->name(8, TRUE),
      'platform' => $this->random->name(8, TRUE),
      'description' => $this->random->string(8, TRUE),
      'creation_date' => date('c'),
    ];
  }

  /**
   * Tests CRUD for image information.
   */
  public function testImage() {
    $cloud_context = $this->cloudContext;

    // List Image for Amazon EC2.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
    $this->assertResponse(200, t('List | HTTP 200: Image'));
    $this->assertNoText(t('Notice'), t('List | ake sure w/o Notice'));
    $this->assertNoText(t('warning'), t('List | Make sure w/o Warnings'));

    // Register a new Image.
    $add = $this->createImageTestData();
    // 3 times.
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT; $i++) {
      $this->reloadMockData();
      $num = $i + 1;

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/image/add",
                            $add[$i],
                            t('Save'));
      $this->assertResponse(200, t('Add | HTTP 200: A New Cloud Image #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText($add[$i]['name'], t('Name: @name', ['@name' => $add[$i]['name']]));
      $this->assertText(
        t('The AWS Cloud Image "@name', ['@name' => $add[$i]['name']]),
        t('Confirm Message: Add | The AWS Cloud Image "@name" has been created.', [
          '@name' => $add[$i]['name'],
        ]));

      // Make sure View.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image/$num");
      $this->assertResponse(200, t('Add | View | HTTP 200: Image #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | View | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | View | Make sure w/o Warnings'));

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
      $this->assertResponse(200, t('Add | List | HTTP 200: Image #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | List | Make sure w/o Warnings'));

      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($add[$j]['name'],
                        t('Add | List | Make sure w/ Listing: @name', [
                          '@name' => $add[$j]['name'],
                        ]));
      }
    }

    // Edit an Image information.
    $edit = $this->createImageTestData();
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT; $i++) {
      $num = $i + 1;

      unset($edit[$i]['instance_id']);
      unset($edit[$i]['description']);

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/image/$num/edit",
                            $edit[$i],
                            t('Save'));

      $this->assertResponse(200, t('HTTP 200: Edit | A New Image #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Make sure w/o Warnings'));
      $this->assertText($add[$i]['name'], t('Name: @name', ['@name' => $add[$i]['name']]));
      $this->assertText(
        t('The AWS Cloud Image "@name" has been saved.', ['@name' => $add[$i]['name']]),
        t('Confirm Message: The AWS Cloud Image "@name" has been saved.', [
          '@name' => $add[$i]['name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
      $this->assertResponse(200, t('Edit | List | HTTP 200: Image #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($add[$i]['name'],
                        t('Edit | List | Make sure w/ Listing: @name', [
                          '@name' => $add[$i]['name'],
                        ]));
      }
    }

    // Delete Image.
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->deleteImage($cloud_context, $num, $edit[$i]['name']);

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
      $this->assertResponse(200, t('Delete | List | HTTP 200: Image #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | List | Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertNoText($edit[$i]['name'],
          t('Delete | List | Make sure w/ Listing: @name', [
            '@name' => $edit[$i]['name'],
          ]));
      }
    }
  }

  /**
   * Test Import image.
   */
  public function testImportImage() {
    $cloud_context = $this->cloudContext;
    $image_id = 'ami-' . $this->getRandomAwsId();
    $product_code1 = $this->random->name(8, TRUE);
    $product_code2 = $this->random->name(8, TRUE);
    $name = "Image " . date('Y/m/d - ') . $this->random->name(8, TRUE);
    $this->updateImageInMockData($image_id, $name, $product_code1, $product_code2);

    // Import image.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/images/import");
    $this->assertResponse(200, t('Import | HTTP 200: Image'));
    $this->assertNoText(t('Notice'), t('Import | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Import | Make sure w/o Warnings'));

    $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/images/import",
                          ['image_ids' => $image_id],
                          t('Import'));
    $this->assertResponse(200, t('Import | HTTP 200: A New Cloud Image'));
    $this->assertNoText(t('Notice'), t('Import | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Import | Make sure w/o Warnings'));
    $this->assertText('Imported 1 images', t('Imported 1 images'));
    $this->assertText($image_id, t('Image Id: @image_id', ['@image_id' => $image_id]));

    $num = 1;

    // View image.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/image/$num");
    $this->assertResponse(200, t('Add | View | HTTP 200: Image #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Add | View | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Add | View | Make sure w/o Warnings'));
    $this->assertText($image_id, t('Image Id: @image_id', ['@image_id' => $image_id]));
    $this->assertText("$product_code1,$product_code2", t('Product code: @product_code1,@product_code2', [
      '@product_code1' => $product_code1,
      '@product_code2' => $product_code2,
    ]));

    // Delete image.
    $this->deleteImage($cloud_context, $num, $name);

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
    $this->assertResponse(200, t('Delete | List | HTTP 200: Image #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Delete | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Delete | List | Make sure w/o Warnings'));
    $this->assertNoText($name,
      t('Delete | List | Make sure w/ Listing: @name', [
        '@name' => $name,
      ]));
  }

  /**
   * Delete Image.
   *
   * @param string $cloud_context
   *   Cloud context.
   * @param int $num
   *   Delete image number.
   * @param string $name
   *   Delete image name.
   */
  private function deleteImage($cloud_context, $num, $name) {
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/image/$num/delete");
    $this->assertResponse(200, t('Delete | HTTP 200: Image #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
    $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/image/$num/delete",
                          [],
                          t('Delete'));

    $this->assertResponse(200, t('Delete | HTTP 200: The Cloud Image #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
    $this->assertText($name, t('Name: @name', ['@name' => $name]));
    $this->assertText(
      t('The AWS Cloud Image "@name" has been deleted.', ['@name' => $name]),
      t('Confirm Message : Delete | The AWS Cloud Image "@name" has been deleted.', [
        '@name' => $name,
      ])
    );

  }

  /**
   * Create image test data.
   *
   * @return string[][]
   *   test data array.
   */
  private function createImageTestData() {
    $data = [];
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT; $i++) {
      $num = $i + 1;

      // Input Fields.
      $data[$i] = [
        'name'        => "Image #$num - " . date('Y/m/d - ') . $this->random->name(8, TRUE),
        'instance_id' => 'i-' . $this->getRandomAwsId(),
        'description' => 'description-' . $this->random->name(64),
      ];
    }
    return $data;
  }

  /**
   * Update image mock data.
   *
   * @param string $image_id
   *   Image id.
   * @param string $name
   *   Image name.
   * @param string $product_code1
   *   Product code1.
   * @param string $product_code2
   *   Product code2.
   */
  private function updateImageInMockData($image_id, $name, $product_code1, $product_code2) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeImages']['Images'][0]['ImageId'] = $image_id;
    $mock_data['DescribeImages']['Images'][0]['Name'] = $name;
    $mock_data['DescribeImages']['Images'][0]['ProductCodes'][0]['ProductCode'] = $product_code1;
    $mock_data['DescribeImages']['Images'][0]['ProductCodes'][1]['ProductCode'] = $product_code2;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Test updating image list.
   */
  public function testUpdateImageList() {
    $cloud_context = $this->cloudContext;

    // Delete init mock data.
    $this->deleteFirstImageInMockData();

    // Add a new Image.
    $add = $this->createImageTestData();
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->addImageMockData($add[$i]['name'], $add[$i]['instance_id'], $add[$i]['description'], $cloud_context);
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Image #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Images.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Confirm the detailed view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/image/$num/edit");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/image/$num/delete");
      $this->assertLink(t('List AWS Cloud Images'));
      // Click 'Refresh'.
      $this->clickLink(t('List AWS Cloud Images'));
      $this->assertResponse(200, t('Edit | List | HTTP 200: Image #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      // Confirm the edit view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/image/$num/edit");
      $this->assertNoLink(t('Edit'));
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/image/$num/delete");
    }

    // Add a new Image.
    $num++;
    $data = [
      'name'        => "Image #$num - " . date('Y/m/d - ') . $this->random->name(8, TRUE),
      'instance_id' => 'i-' . $this->getRandomAwsId(),
      'description' => 'description-' . $this->random->name(64),
    ];
    $this->addImageMockData($data['name'], $data['instance_id'], $data['description'], $cloud_context);

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Image #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    $this->assertNoText($data['name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $data['name'],
        ]));

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Images.'));
    $add = array_merge($add, [$data]);
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT + 1; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Change refreched time of entities.
    $entity_type_manager = \Drupal::entityTypeManager();
    $entities = $entity_type_manager->getStorage('aws_cloud_image')->loadByProperties(
      ['cloud_context' => $cloud_context]
    );

    foreach ($entities as $entity) {
      $timestamp = time();
      $timestamp -= self::AWS_UPDATE_IMAGE_LIST_REFRESH_TIME_ADJUSTMENT;
      $entity->setRefreshed($timestamp);
      $entity->save();
    }

    // Delete Image in mock data.
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT + 1; $i++) {
      $this->deleteFirstImageInMockData();
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/image");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Image #@num', ['@num' => $num + 1]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT + 1; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Images.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_IMAGE_REPEAT_COUNT + 1; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }
  }

  /**
   * Add Image mock data.
   *
   * @param string $name
   *   The Image name.
   * @param string $instance_id
   *   The instance id.
   * @param string $description
   *   The description.
   * @param string $cloud_context
   *   The cloud context.
   */
  private function addImageMockData($name, $instance_id, $description, $cloud_context) {
    $cloud_config_plugin = \Drupal::service('plugin.manager.cloud_config_plugin');
    $cloud_config_plugin->setCloudContext($cloud_context);
    $cloud_config = $cloud_config_plugin->loadConfigEntity();
    $account_id = $cloud_config->get('field_account_id')->value;

    $mock_data = $this->getMockDataFromConfig();
    $vars = $this->getMockDataTemplateVars();

    $image = [
      'ImageId' => $vars['image_id'],
      'OwnerId' => $account_id,
      'Architecture' => 'x86_64',
      'Description' => $description,
      'VirtualizationType' => 'hvm',
      'RootDeviceType' => 'ebs',
      'RootDeviceName' => '/dev/sda1',
      'Name' => $name,
      'KernelId' => $vars['kernel_id'],
      'RamdiskId' => $vars['ramdisk_id'],
      'ImageType' => 'machine',
      'ProductCodes' => ['ProductCode' => [$vars['product_code1'], $vars['product_code2']]],
      'ImageLocation' => $vars['image_location'],
      'StateReason' => ['Message' => $vars['state_reason_message']],
      'Platform' => $vars['platform'],
      'Public' => NULL,
      'State' => 'available',
      'CreationDate' => $vars['creation_date'],
      'BlockDeviceMappings' => [['DeviceName' => NULL]],
    ];

    $mock_data['DescribeImages']['Images'][] = $image;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Delete first Image in mock data.
   */
  private function deleteFirstImageInMockData() {
    $mock_data = $this->getMockDataFromConfig();
    $images = $mock_data['DescribeImages']['Images'];
    array_shift($images);
    $mock_data['DescribeImages']['Images'] = $images;
    $this->updateMockDataToConfig($mock_data);
  }

}
