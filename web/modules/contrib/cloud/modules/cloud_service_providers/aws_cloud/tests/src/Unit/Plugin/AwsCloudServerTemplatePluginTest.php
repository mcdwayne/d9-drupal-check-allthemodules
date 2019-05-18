<?php

namespace Drupal\Tests\aws_cloud\Unit\Plugin;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\aws_cloud\Traits\AwsCloudTestTrait;

use Drupal\Component\Utility\Random;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\aws_cloud\Entity\Ec2\ImageInterface;
use Drupal\aws_cloud\Entity\Ec2\KeyPairInterface;
use Drupal\aws_cloud\Entity\Ec2\NetworkInterfaceInterface;
use Drupal\aws_cloud\Entity\Ec2\SecurityGroupInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\aws_cloud\Plugin\AwsCloudServerTemplatePlugin;
use Drupal\cloud\Entity\CloudServerTemplateInterface;
use Drupal\aws_cloud\Service\AwsEc2ServiceInterface;
use Drupal\aws_cloud\Plugin\Field\FieldType\Tag;

/**
 * Tests AWS Cloud Template Plugin.
 *
 * @group AWS Cloud
 */
class AwsCloudServerTemplatePluginTest extends UnitTestCase {

  use AwsCloudTestTrait;

  // Amazon VPC Limits - Security groups per VPC (per region).
  const MAX_SECURITY_GROUPS_COUNT = 500;

  /**
   * Plugin.
   *
   * @var string
   */
  private $plugin;

  /**
   * Mock AWS EC2 Service.
   *
   * @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface
   */
  private $mockAwsEc2Service;

  /**
   * Creating random data utility.
   *
   * @var \Drupal\Component\Utility\Random
   */
  private $random;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->mockAwsEc2Service = $this->getMock(AwsEc2ServiceInterface::class);

    $mock_query = $this->getMock(QueryInterface::class);
    $mock_query->expects($this->any())
      ->method('condition')
      ->will($this->returnValue($mock_query));

    $mock_query->expects($this->any())
      ->method('execute')
      ->will($this->returnValue([]));

    $mock_storage = $this->getMock(EntityStorageInterface::class);
    $mock_storage->expects($this->any())
      ->method('getQuery')
      ->will($this->returnValue($mock_query));

    $mock_storage->expects($this->any())
      ->method('loadMultiple')
      ->will($this->returnValue([]));

    $mock_entity_type_manager = $this->getMock(EntityTypeManagerInterface::class);
    $mock_entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($mock_storage));

    $mock_uuid = $this->getMock(UuidInterface::class);
    $mock_uuid->expects($this->any())
      ->method('generate')
      ->will($this->returnValue(''));

    $this->plugin = new AwsCloudServerTemplatePlugin(
      [], '', [],
      $this->mockAwsEc2Service,
      $this->getMockBuilder(Messenger::class)
        ->disableOriginalConstructor()
        ->getMock(),
      $mock_entity_type_manager,
      $mock_uuid
    );

    $this->random = new Random();
  }

  /**
   * Tests launching a instance.
   */
  public function testLaunch() {
    $random = $this->random;

    // Mock object of Image.
    $mock_image = $this->getMock(ImageInterface::class);
    $image_value_map = [
      ['image_id', (object) ['value' => 'ami-' . $this->getRandomAwsId()]],
      ['root_device_type', 'ebs'],
    ];
    $mock_image->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($image_value_map));

    // Mock object of KeyPair.
    $mock_ssh_key = $this->getMock(KeyPairInterface::class);
    $ssh_key_value_map = [
      ['key_pair_name', (object) ['value' => $random->name(8, TRUE)]],
    ];
    $mock_ssh_key->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($ssh_key_value_map));

    // Mock object of NetworkInterface.
    $mock_network = $this->getMock(NetworkInterfaceInterface::class);
    $network_interface_id = 'eni-' . $this->getRandomAwsId();
    $mock_network->expects($this->any())
      ->method('getNetworkInterfaceId')
      ->will($this->returnValue($network_interface_id));

    $vpc_id = 'vpc-' . $this->getRandomAwsId();
    $field_security_groups_test_cases = $this->createFieldSecurityGroupsTestCases($vpc_id);
    $subnet_id = 'subnet-' . $this->getRandomAwsId();

    // Create random tags.
    $mock_tags = $this->createRandomTags();
    $aws_tags = $this->convertToAwsTags($mock_tags);

    // Add Name tag.
    $template_name = $random->name(8, TRUE);
    $aws_tags[] = [
      'Key' => 'Name',
      'Value' => $template_name,
    ];

    // Run test cases.
    foreach ($field_security_groups_test_cases as $field_security_groups_test_case) {
      $mock_template = $this->getMock(CloudServerTemplateInterface::class);
      $template_value_map = [
        ['field_test_only', (object) ['value' => '1']],
        ['field_image_id', (object) ['entity' => $mock_image]],
        ['field_max_count', (object) ['value' => 1]],
        ['field_min_count', (object) ['value' => 1]],
        ['field_monitoring', (object) ['value' => '1']],
        ['field_instance_type', (object) ['value' => $random->name(5, TRUE)]],
        ['field_ssh_key', (object) ['entity' => $mock_ssh_key]],
        [
          'field_kernel_id',
          (object) ['value' => 'aki-' . $this->getRandomAwsId()],
        ],
        ['field_ram', (object) ['value' => 'ari-' . $this->getRandomAwsId()]],
        ['field_user_data', (object) ['value' => $random->string(32, TRUE)]],
        [
          'field_availability_zone',
          (object) ['value' => $random->name(7, TRUE)],
        ],
        [
          'field_security_group',
          $this->extractArrayItem($field_security_groups_test_case, 0),
        ],
        ['field_network', (object) ['entity' => $mock_network]],
        ['field_subnet', (object) ['value' => $subnet_id]],
        ['field_instance_shutdown_behavior', (object) ['value' => '1']],
        ['field_vpc', (object) ['value' => $vpc_id]],
        ['field_iam_role', (object) ['value' => '']],
        ['field_tags', $mock_tags],
      ];
      $mock_template->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($template_value_map));

      $mock_template->expects($this->any())
        ->method('getName')
        ->will($this->returnValue($template_name));

      // Assert followings for the first argument of method runInstances,
      // 1. Has key SecurityGroups,
      // 2. Doesn't have key SecurityGroup,
      // 3. Contains the array of group1 and group2,
      // 4. Has key SubnetId,
      // 5. Contains subnet_id.
      $this->mockAwsEc2Service
        ->expects($this->any())
        ->method('runInstances')
        ->with(
          $this->logicalAnd(
            $this->arrayHasKey('SecurityGroupIds'),
            $this->logicalNot($this->arrayHasKey('SecurityGroups')),
            $this->contains($this->extractArrayItem($field_security_groups_test_case, 1)),
            $this->arrayHasKey('SubnetId'),
            $this->contains($subnet_id)
          ),
          $this->equalTo($aws_tags)
        )
        ->will($this->returnValue(TRUE));

      $return = $this->plugin->launch(
        $mock_template,
        $this->getMock(FormStateInterface::class)
      );
      $this->assertNotNull($return);
    }

  }

  /**
   * Extract array item.
   *
   * @param array $array
   *   Array to be extracted.
   * @param int $index
   *   Index.
   *
   * @return array
   *   Extracted array.
   */
  private function extractArrayItem(array $array, $index) {
    $extracted = [];
    foreach ($array as $item) {
      $extracted[] = $item[$index];
    }

    return $extracted;
  }

  /**
   * Create field security groups test cases.
   *
   * @param string $vpc_id
   *   The ID of vpc.
   *
   * @return array
   *   The test cases of security group.
   */
  private function createFieldSecurityGroupsTestCases($vpc_id) {

    for ($i = 0; $i < self::MAX_SECURITY_GROUPS_COUNT; $i++) {
      // Mock object of SecurityGroup.
      $mock_security_group = $this->getMock(SecurityGroupInterface::class);
      $group_id = 'sg-' . $this->getRandomAwsId();
      $mock_security_group->expects($this->any())
        ->method('getGroupId')
        ->will($this->returnValue($group_id));

      $mock_security_group->expects($this->any())
        ->method('getVpcId')
        ->will($this->returnValue($vpc_id));

      $mock_security_group_and_ids[] = [
        (object) ['entity' => $mock_security_group],
        $group_id,
      ];
    }

    $mock_security_group_and_ids_exclude_first_last = array_slice(
      $mock_security_group_and_ids,
      1,
      count($mock_security_group_and_ids) - 2
    );

    $field_security_groups_test_cases = [];

    // Test cases.
    //
    // Test when Security Groups has the only one item, the first item only.
    // Security Group A (* SELECTED).
    // ...
    // Security Group M.
    // ...
    // Security Group N.
    $field_security_groups_test_cases[] = [reset($mock_security_group_and_ids)];

    // Test when Security Groups has more than two items.
    if (self::MAX_SECURITY_GROUPS_COUNT > 1) {

      // The last item only.
      // Security Group A.
      // ...
      // Security Group M.
      // ...
      // Security Group N (* SELECTED).
      $field_security_groups_test_cases[] = [end($mock_security_group_and_ids)];

      // The first item and the last one only.
      // Security Group A (* SELECTED).
      // ...
      // Security Group M.
      // ...
      // Security Group N (* SELECTED).
      $field_security_groups_test_cases[] = [
        reset($mock_security_group_and_ids),
        end($mock_security_group_and_ids),
      ];

      if (self::MAX_SECURITY_GROUPS_COUNT > 2) {

        // Randomly picking up the only one item.
        // One random item.
        // Security Group A.
        // ...
        // Security Group M (* SELECTED).
        // ...
        // Security Group N.
        $rand_index = array_rand($mock_security_group_and_ids_exclude_first_last);
        $field_security_groups_test_cases[] = [
          // Pickup one index randomly (array index: 1 to the (last index -2))
          $mock_security_group_and_ids_exclude_first_last[$rand_index],
        ];

        // The first item and one random one.
        // Security Group A (* SELECTED).
        // ...
        // Security Group M (* SELECTED).
        // ...
        // Security Group N.
        $rand_index = array_rand($mock_security_group_and_ids_exclude_first_last);
        $field_security_groups_test_cases[] = [
          reset($mock_security_group_and_ids),
          // Pickup one index randomly (array index: 1 to the (last index -2))
          $mock_security_group_and_ids_exclude_first_last[$rand_index],
        ];

        // One random item and the last one.
        // Security Group A.
        // ...
        // Security Group M (* SELECTED).
        // ...
        // Security Group N (* SELECTED).
        $rand_index = array_rand($mock_security_group_and_ids_exclude_first_last);
        $field_security_groups_test_cases[] = [
          // Pickup one index randomly (array index: 1 to the (last index -2))
          $mock_security_group_and_ids_exclude_first_last[$rand_index],
          end($mock_security_group_and_ids),
        ];

        if (self::MAX_SECURITY_GROUPS_COUNT > 3) {

          // Two random items.
          // Security Group A.
          // ...
          // Security Group M1 (* SELECTED).
          // ...
          // Security Group M2 (* SELECTED).
          // ...
          // Security Group N.
          $array_indice = array_rand($mock_security_group_and_ids_exclude_first_last, 2);
          $field_security_groups_test_cases[] = [
            $mock_security_group_and_ids_exclude_first_last[$array_indice[0]],
            $mock_security_group_and_ids_exclude_first_last[$array_indice[1]],
          ];
        }
      }
    }

    return $field_security_groups_test_cases;
  }

  /**
   * Create random tags.
   *
   * @return array
   *   Tag mock objects.
   */
  private function createRandomTags() {
    $mock_tags = [];
    $count = rand(0, 5);
    for ($i = 0; $i < $count; $i++) {
      $mock_tag = $this->getMockBuilder(Tag::class)
        ->disableOriginalConstructor()
        ->getMock();

      $mock_tag->expects($this->any())
        ->method('getTagKey')
        ->will($this->returnValue('key_' . $this->random->name(8, TRUE)));

      $mock_tag->expects($this->any())
        ->method('getTagValue')
        ->will($this->returnValue('value_' . $this->random->name(8, TRUE)));

      $mock_tags[] = $mock_tag;
    }

    return $mock_tags;
  }

  /**
   * Convert mock tag objects to AWS tags.
   *
   * @param array $mock_tags
   *   Mock tag objects.
   *
   * @return array
   *   Aws tags.
   */
  private function convertToAwsTags(array $mock_tags) {
    $tags = [];
    foreach ($mock_tags as $mock_tag) {
      $tags[] = [
        'Key' => $mock_tag->getTagKey(),
        'Value' => $mock_tag->getTagValue(),
      ];
    }

    return $tags;
  }

}
