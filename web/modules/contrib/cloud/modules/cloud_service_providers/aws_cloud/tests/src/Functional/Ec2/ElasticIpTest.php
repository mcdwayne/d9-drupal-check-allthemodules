<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

use Drupal\Tests\aws_cloud\Functional\Utils;
use Drupal\Component\Serialization\Yaml;

/**
 * Tests AWS Cloud Elastic IP.
 *
 * @group AWS Cloud
 */
class ElasticIpTest extends AwsCloudTestCase {

  const AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT = 3;

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'list aws cloud elastic ip',
      'add aws cloud elastic ip',
      'view aws cloud elastic ip',
      'edit aws cloud elastic ip',
      'delete aws cloud elastic ip',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMockDataTemplateVars() {
    return [
      'public_ip' => Utils::getRandomPublicIp(),
      'allocation_id' => 'eipalloc-' . $this->getRandomAwsId(),
      'domain' => 'vpc',
    ];
  }

  /**
   * Tests CRUD for Elastic IP information.
   */
  public function testElasticIp() {
    $cloud_context = $this->cloudContext;

    // List Elastic IP for Amazon EC2.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip");
    $this->assertResponse(200, t('List | HTTP 200: Elastic IP'));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Make sure w/o Warnings'));

    $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip/add");

    // Add a new Elastic IP.
    $add = $this->createElasticIpTestData();
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      $this->reloadMockData();
      $domain = $this->getRandomDomain();
      $this->updateDomainInMockData($domain);

      $num = $i + 1;

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/elastic_ip/add",
                            $add[$i],
                            t('Save'));

      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Elastic IP #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText($add[$i]['name'], t('Elastic IP: @name', ['@name' => $add[$i]['name']]));
      $this->assertText(
        t('The AWS Cloud Elastic IP "@name', [
          '@name' => $add[$i]['name'],
        ]),
        t('Confirm Message: The AWS Cloud Elastic IP "@name" has been created.', [
          '@name' => $add[$i]['name'],
        ])
      );

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip/$num");
      $this->assertResponse(200, t('Add | View | HTTP 200: Elastic IP #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | View | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | View | Make sure w/o Warnings'));

      // Make sure domain is updated.
      $this->assertText($domain, t('Add | View | Make sure domain: @domain', ['@domain' => $domain]));

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip");
      $this->assertResponse(200, t('Add | List | HTTP 200: Elastic IP #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | List | Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText(
          $add[$j]['name'],
          t('Add | List | Make sure w/ Listing: @name', [
            '@name' => $add[$i]['name'],
          ])
        );
      }
    }

    // Edit an Elastic IP information.
    $edit = $this->createElasticIpTestData();
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      unset($edit[$i]['domain']);

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/edit",
                            $edit[$i],
                            t('Save'));

      $this->assertResponse(200, t('Edit | HTTP 200: A New AWS Cloud Elastic IP #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Elastic IP "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ]),
        t('Confirm Message: Edit | The AWS Cloud Elastic IP "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip");
      $this->assertResponse(200, t('Edit | List | HTTP 200: Elastic IP #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($edit[$j]['name'],
                        t('Make sure w/ Listing: @name', [
                          '@name' => $edit[$j]['name'],
                        ]));
      }
    }

    // Delete Elastic IP
    // 3 times.
    $this->updateInstanceInMockData();
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/delete");
      $this->assertResponse(200, t('Delete | HTTP 200: Elastic IP #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/delete",
                            [],
                            t('Delete'));

      $this->assertResponse(200, t('Delete | HTTP 200: The Cloud Elastic IP #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->assertText($edit[$i]['name'], t('Name: @name', ['@name' => $edit[$i]['name']]));
      $this->assertText(
        t('The AWS Cloud Elastic IP "@name" has been deleted.', [
          '@name' => $edit[$i]['name'],
        ]),
        t('Confirm Message: Delete | The AWS Cloud Elastic IP "@name" has been deleted.', [
          '@name' => $edit[$i]['name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip");
      $this->assertResponse(200, t('Delete | List | HTTP 200: Elastic IP #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | List | Make sure w/o Warnings'));

      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertNoText($edit[$j]['name'],
          t('Delete | List | Make sure w/ Listing: @name', [
            '@name' => $edit[$j]['name'],
          ]));
      }
    }
  }

  /**
   * Create elastic ip test data.
   *
   * @return string[][]
   *   Elastic IP array.
   */
  private function createElasticIpTestData() {
    $data = [];
    // 3 times.
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      // Input Fields.
      $num = $i + 1;
      $data[$i] = [
        'name'        => "Elastic IP #$num - " . date('Y/m/d - ') . $this->random->name(15, TRUE),
        'domain'      => 'standard',
      ];
    }
    return $data;
  }

  /**
   * Get Random domain.
   *
   * @return string
   *   a random domain.
   */
  private function getRandomDomain() {
    $domains = ['standard', 'vpc'];
    return $domains[array_rand($domains)];
  }

  /**
   * Update domain in mock data.
   *
   * @param string $domain
   *   A domain.
   */
  private function updateDomainInMockData($domain) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['AllocateAddress']['Domain'] = $domain;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Add an instance into the mock data.
   *
   * This is used during elastic ip delete testing.
   */
  private function updateInstanceInMockData() {
    $public_ip = Utils::getRandomPublicIp();
    $private_ip = Utils::getRandomPrivateIp();
    $regions = ['us-west-1', 'us-west-2'];
    $region = $regions[array_rand($regions)];

    $vars = [
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
      'uid' => $this->loggedInUser->id(),
    ];
    $instance_mock_data_content = $this->getMockDataFileContent('Drupal\Tests\aws_cloud\Functional\Ec2\InstanceTest', $vars, '_instance');

    $mock_data = $this->getMockDataFromConfig();
    $instance_mock_data = Yaml::decode($instance_mock_data_content);
    $mock_data['DescribeInstances']['Reservations'][0]['OwnerId'] = $this->random->name(8, TRUE);
    $mock_data['DescribeInstances']['Reservations'][0]['ReservationId'] = $this->random->name(8, TRUE);
    $mock_data['DescribeInstances']['Reservations'][0]['Instances'][] = $instance_mock_data;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Test updating elastic ips.
   */
  public function testUpdateElasticIpList() {
    $cloud_context = $this->cloudContext;

    // Add a new Elastic IP.
    $add = $this->createElasticIpTestData();
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $add[$i]['public_ip'] = Utils::getRandomPublicIp();
      $this->addElasticIpMockData($add[$i]['name'], $add[$i]['domain'], $add[$i]['public_ip']);
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Elastic IP #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['name'],
        ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Elastic IPs.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['name'],
        ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Confirm the detailed view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/edit");
      $this->assertLink(t('Associate Elastic Ip'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/associate");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/delete");
      $this->assertLink(t('List AWS Cloud Elastic IPs'));
      // Click 'Refresh'.
      $this->clickLink(t('List AWS Cloud Elastic IPs'));
      $this->assertResponse(200, t('Edit | List | HTTP 200: Elastic IP #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      // Confirm the edit view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/edit");
      $this->assertNoLink(t('Edit'));
      $this->assertLink(t('Associate Elastic Ip'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/associate");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/delete");
    }

    // Edit Elastic IP information.
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Setup a test instance.
      $instance = $this->createTestInstance($i, $add[$i]['public_ip']);
      $instance_id = $instance->getInstanceId();

      // Change Elastic IP Name in mock data.
      $add[$i]['name'] = "Elastic IP #$num - " . date('Y/m/d - ') . $this->random->name(15, TRUE);
      $add[$i]['association_id'] = $this->random->name(8, TRUE);
      $this->updateElasticIpInMockData($num - 1, $add[$i]['name'], $add[$i]['association_id'], $instance_id);

    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Elastic IP #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['name'],
        ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Elastic IPs.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['name'],
        ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Confirm the detailed view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip/$num");
      $this->assertLink(t('Disassociate'));

      // Confirm the edit view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/edit");
      $this->assertLink(t('Disassociate Elastic Ip'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/elastic_ip/$num/disassociate");
    }

    // Delete Elastic IP in mock data.
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      $this->deleteFirstElasticIpInMockData();
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/elastic_ip");
    $this->assertResponse(200, t('Edit | List | HTTP 200: Elastic IP #@num', ['@num' => $num + 1]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['name'],
        ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Elastic IPs.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_ELASTIC_IP_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['name'],
        ]));
    }
  }

  /**
   * Add Elastic IP mock data.
   *
   * @param string $name
   *   The elastic ip name.
   * @param string $domain
   *   The domain.
   * @param string $public_ip
   *   The public ip.
   */
  private function addElasticIpMockData($name, $domain, $public_ip) {
    $mock_data = $this->getMockDataFromConfig();
    $elastic_ip = [
      'AllocationId' => $name,
      'PublicIp' => $public_ip,
      'PrivateIpAddress' => Utils::getRandomPublicIp(),
      'Domain' => $domain,
    ];

    $mock_data['DescribeAddresses']['Addresses'][] = $elastic_ip;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Update Elastic IP mock data.
   *
   * @param int $elastic_ip_index
   *   The index of elastic ip.
   * @param string $name
   *   The elastic IP name.
   * @param string $association_id
   *   The association id.
   * @param string $instance_id
   *   The instance id.
   */
  private function updateElasticIpInMockData($elastic_ip_index, $name, $association_id, $instance_id) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeAddresses']['Addresses'][$elastic_ip_index]['AllocationId'] = $name;
    $mock_data['DescribeAddresses']['Addresses'][$elastic_ip_index]['AssociationId'] = $association_id;
    $mock_data['DescribeAddresses']['Addresses'][$elastic_ip_index]['InstanceId'] = $instance_id;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Delete first elastic IP in mock data.
   */
  private function deleteFirstElasticIpInMockData() {
    $mock_data = $this->getMockDataFromConfig();
    $addresses = $mock_data['DescribeAddresses']['Addresses'];
    array_shift($addresses);
    $mock_data['DescribeAddresses']['Addresses'] = $addresses;
    $this->updateMockDataToConfig($mock_data);
  }

}
