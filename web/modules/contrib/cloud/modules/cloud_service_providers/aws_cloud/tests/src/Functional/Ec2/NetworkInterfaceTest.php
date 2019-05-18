<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

/**
 * Tests AWS Cloud Network Interface.
 *
 * @group AWS Cloud
 */
class NetworkInterfaceTest extends AwsCloudTestCase {

  const AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT = 3;

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'list aws cloud network interface',
      'add aws cloud network interface',
      'view aws cloud network interface',
      'edit aws cloud network interface',
      'delete aws cloud network interface',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMockDataTemplateVars() {
    return [
      'network_interface_id' => 'eni-' . $this->getRandomAwsId(),
      'vpc_id' => 'vpc-' . $this->getRandomAwsId(),
    ];
  }

  /**
   * Tests CRUD for Network Interface information.
   */
  public function testNetworkInterface() {
    $cloud_context = $this->cloudContext;

    // List Network Interface for Amazon EC2.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface");
    $this->assertResponse(200, t('List | HTTP 200: Network Interface'));
    $this->assertNoText(t('Notice'), t('List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('List | Make sure w/o Warnings'));

    // Add a new Network Interface.
    $add = $this->createNetworkInterfaceTestData();
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $this->reloadMockData();

      $num = $i + 1;

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/network_interface/add",
                            $add[$i],
                            t('Save'));
      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Network Interface #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Network Interface "@name', [
          '@name' => $add[$i]['name'],
        ]),
        t('Confirm Message: Add | The AWS Cloud Network Interface "@name" has been created.', [
          '@name' => $add[$i]['name'],
        ])
      );
      $this->assertText(
        $add[$i]['name'],
        t('Network Interface: @name', [
          '@name' => $add[$i]['name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface");
      $this->assertResponse(200, t('Add | List | HTTP 200: Network Interface #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | List | Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($add[$j]['name'],
                        t('Make sure w/ Listing: @name',
                        ['@name' => $add[$j]['name']]));
      }
    }

    // Edit an Network Interface information.
    $edit = $this->createNetworkInterfaceTestData();
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      unset($edit[$i]['subnet_id']);
      unset($edit[$i]['description']);
      unset($edit[$i]['primary_private_ip']);
      unset($edit[$i]['secondary_private_ips']);
      unset($edit[$i]['is_primary']);
      unset($edit[$i]['security_groups']);

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/network_interface/$num/edit",
                            $edit[$i],
                            t('Save'));
      $this->assertResponse(200, t('Edit | HTTP 200: A New AWS Cloud Network Interface #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Network Interface "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ]),
        t('Confirm Message: The AWS Cloud Network Interface "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ])
      );

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface");
      $this->assertResponse(200, t('Edit List | HTTP 200: Network Interface #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      $this->assertText($edit[$i]['name'], t('Network Interface: @name', ['@name' => $edit[$i]['name']]));

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface");
      $this->assertResponse(200, t('Edit | List | HTTP 200: Network Interface #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($edit[$i]['name'],
                        t('Edit | List | Make sure w/ Listing: @name', [
                          '@name' => $edit[$i]['name'],
                        ]));
      }
    }

    // Delete Network Interface.
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface/$num/delete");
      $this->assertResponse(200, t('HTTP 200: Delete | Network Interface #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/network_interface/$num/delete",
                            [],
                            t('Delete'));

      $this->assertResponse(200, t('Delete | HTTP 200: The Cloud Network Interface #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->assertText($edit[$i]['name'], t('Name: @name', ['@name' => $edit[$i]['name']]));
      $this->assertText(
        t('The AWS Cloud Network Interface "@name" has been deleted.', [
          '@name' => $edit[$i]['name'],
        ]),
        t('Confirm Message: Delete | The AWS Cloud Network Interface "@name" has been deleted.', [
          '@name' => $edit[$i]['name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface");
      $this->assertResponse(200, t('Delete | HTTP 200: Network Interface #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | List | Make sure w/o Warnings'));
    }
  }

  /**
   * Create network interface test data.
   */
  private function createNetworkInterfaceTestData() {
    $data = [];

    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Input Fields.
      $data[$i] = [
        'name'                  => $this->random->name(32, TRUE),
        'description'           => "Description #$num - " . $this->random->name(64, TRUE),
        'subnet_id'             => 'subnet_id-' . $this->getRandomAwsId(),
        'primary_private_ip' => implode('.', [
          rand(1, 254),
          rand(0, 254),
          rand(0, 254),
          rand(1, 255),
        ]),
        'secondary_private_ips' => implode('.', [
          rand(1, 254),
          rand(0, 254),
          rand(0, 254),
          rand(1, 255),
        ]),
        'is_primary'            => $num % 2,
        'security_groups'       => 'sg-' . $this->getRandomAwsId(),
      ];
    }
    return $data;
  }

  /**
   * Test updating network interface.
   */
  public function testUpdateNetworkInterfaceList() {
    $cloud_context = $this->cloudContext;

    // Add a new Network Interface.
    $add = $this->createNetworkInterfaceTestData();
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->addNetworkInterfaceMockData($add[$i]);
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Network Interface #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Network Interfaces.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Confirm the detailed view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/network_interface/$num/edit");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/network_interface/$num/delete");
      $this->assertLink(t('List AWS Cloud Network Interfaces'));
      // Click 'Refresh'.
      $this->clickLink(t('List AWS Cloud Network Interfaces'));
      $this->assertResponse(200, t('Edit | List | HTTP 200: Network Interface #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      // Confirm the edit view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface/$num/edit");
      $this->assertNoLink(t('Edit'));
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/network_interface/$num/delete");
      $this->assertNoLink('Edit');
    }

    // Edit Network Interface information.
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Change Network Interface Name in mock data.
      $add[$i]['name'] = 'eni-' . $this->getRandomAwsId();
      $this->updateNetworkInterfaceInMockData($num - 1, $add[$i]['name']);

    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Network Interface #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Network Interfaces.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Delete Network Interface in mock data.
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $this->deleteFirstNetworkInterfaceInMockData();
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/network_interface");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Network Interface #@num', ['@num' => $num + 1]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Network Interfaces.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_NETWORK_INTERFACE_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
          t('Edit | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ]));
    }

  }

  /**
   * Add Network Interface mock data.
   *
   * @param array $data
   *   Array of Network Interface data.
   */
  private function addNetworkInterfaceMockData(array &$data) {
    $mock_data = $this->getMockDataFromConfig();
    $vars = $this->getMockDataTemplateVars();
    $network_interface = [
      'NetworkInterfaceId' => $vars['network_interface_id'],
      'VpcId' => $vars['vpc_id'],
      'Description' => $data['description'],
      'SubnetId' => $data['subnet_id'],
      'MacAddress' => NULL,
      'Status' => 'in-use',
      'PrivateDnsName' => NULL,
      'Attachment' => [
        'AttachmentId' => NULL,
        'InstanceOwnerId' => NULL,
        'Status' => NULL,
        'InstanceId' => NULL,
        'DeviceIndex' => '',
        'DeleteOnTermination' => NULL,
      ],
      'Association' => [
        'AllocationId' => '',
      ],
      'OwnerId' => $this->random->name(8, TRUE),
      'SourceDestCheck' => NULL,
      'PrivateIpAddresses' => [
        [
          'Primary' => $data['is_primary'],
          'PrivateIpAddress' => $data['primary_private_ip'],
        ],
      ],
      'Groups' => [
        [
          'GroupName' => $data['security_groups'],
        ],
      ],
    ];
    $data['name'] = $network_interface['NetworkInterfaceId'];
    $mock_data['DescribeNetworkInterfaces']['NetworkInterfaces'][] = $network_interface;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update Network Interface mock data.
   *
   * @param int $network_interface_index
   *   The index of Network Interface.
   * @param string $name
   *   The network Interface name.
   */
  private function updateNetworkInterfaceInMockData($network_interface_index, $name) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeNetworkInterfaces']['NetworkInterfaces'][$network_interface_index]['NetworkInterfaceId'] = $name;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Delete first Network Interface in mock data.
   */
  private function deleteFirstNetworkInterfaceInMockData() {
    $mock_data = $this->getMockDataFromConfig();
    $network_interfaces = $mock_data['DescribeNetworkInterfaces']['NetworkInterfaces'];
    array_shift($network_interfaces);
    $mock_data['DescribeNetworkInterfaces']['NetworkInterfaces'] = $network_interfaces;
    $this->updateMockDataToConfig($mock_data);
  }

}
