<?php

namespace Drupal\Tests\cloud\Functional;

use Drupal\Tests\aws_cloud\Functional\Ec2\AwsCloudTestCase;

/**
 * Tests CloudServerTemplate.
 *
 * @group Cloud
 */
class CloudServerTemplateTest extends AwsCloudTestCase {

  const CLOUD_SERVER_TEMPLATES_REPEAT_COUNT = 3;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'cloud',
    'aws_cloud',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'add cloud server template entities',
      'list cloud server template',
      'view any published cloud server template entities',
      'view any unpublished cloud server template entities',
      'edit any cloud server template entities',
      'delete any cloud server template entities',
      'access cloud server template revisions',
      'revert all cloud server template revisions',
      'delete all cloud server template revisions',
    ];
  }

  /**
   * Tests CRUD for server_template information.
   */
  public function testCloudServerTemplate() {
    $this->createImage();
    $this->createSecurityGroup();
    $this->createKeyPair();

    $cloud_context = $this->cloudContext;

    // List Server Template for AWS.
    $this->drupalGet("/clouds/design/server_template/list/$cloud_context");
    $this->assertResponse(200, t('HTTP 200: List | Server Template'));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));

    $vpcs = $this->createRandomVpcs();
    $subnets = $this->createRandomSubnets();
    $this->updateVpcsAndSubnetsToMockData($vpcs, $subnets);

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Add a new server_template information.
    $add = $this->createServerTemplateTestData();
    for ($i = 0; $i < self::CLOUD_SERVER_TEMPLATES_REPEAT_COUNT; $i++) {
      $num = $i + 1;

      $vpc_index = array_rand($vpcs);
      $add[$i]['field_vpc'] = $vpcs[$vpc_index]['VpcId'];
      $vpc_name = $this->getNameFromArray($vpcs, $vpc_index, 'VpcId');

      $subnet_index = array_rand($subnets);
      $add[$i]['field_subnet'] = $subnets[$subnet_index]['SubnetId'];
      $subnet_name = $this->getNameFromArray($subnets, $subnet_index, 'SubnetId');

      $iam_role_index = array_rand($iam_roles);
      $add[$i]['field_iam_role'] = $iam_roles[$iam_role_index]['Arn'];
      $iam_role_name = $iam_roles[$iam_role_index]['InstanceProfileName'];

      $this->drupalPostForm("/clouds/design/server_template/add/$cloud_context/aws_cloud",
                            $add[$i],
                            t('Save'));
      $this->assertResponse(200, t('HTTP 200: Add | A New CloudServerTemplate Form #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
      $this->assertText(
        t('Created the @name Cloud Server Template', [
          '@name' => $add[$i]['name[0][value]'],
        ]),
        t('Confirm Message: Created the @name Cloud Server Template', [
          '@name' => $add[$i]['name[0][value]'],
        ])
      );
      $this->assertText($add[$i]['name[0][value]']);
      $this->assertText($vpc_name);
      $this->assertText($subnet_name);
      $this->assertText($iam_role_name);
      $this->assertText($add[$i]['field_tags[0][tag_key]']);
      $this->assertText($add[$i]['field_tags[0][tag_value]']);

      // Make sure listing.
      $this->drupalGet("/clouds/design/server_template/list/$cloud_context");
      $this->assertResponse(200, t('HTTP 200: List | Server Template #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($add[$j]['name[0][value]'],
                        t("Make sure w/ Listing @num: @name", [
                          '@num' => $j + 1,
                          '@name' => $add[$j]['name[0][value]'],
                        ]));
      }
    }

    // Edit case.
    $edit = $this->createServerTemplateTestData();
    for ($i = 0; $i < self::CLOUD_SERVER_TEMPLATES_REPEAT_COUNT; $i++) {
      $num = $i + 1;

      $vpc_index = array_rand($vpcs);
      $edit[$i]['field_vpc'] = $vpcs[$vpc_index]['VpcId'];
      $vpc_name = $this->getNameFromArray($vpcs, $vpc_index, 'VpcId');

      $subnet_index = array_rand($subnets);
      $edit[$i]['field_subnet'] = $subnets[$subnet_index]['SubnetId'];
      $subnet_name = $this->getNameFromArray($subnets, $subnet_index, 'SubnetId');

      $this->drupalPostForm("/clouds/design/server_template/edit/$cloud_context/$num",
                            $edit[$i],
                            t('Save'));
      $this->assertResponse(200, t('HTTP 200: Edit | A New Server Template #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
      $this->assertText(
        t('Saved the @name Cloud Server Template.',
          [
            '@name' => $edit[$i]['name[0][value]'],
          ]
        ),
        t('Confirm Message: Saved the @name Cloud Server Template.',
          [
            '@name' => $edit[$i]['name[0][value]'],
          ]
        )
      );
      $this->assertText($edit[$i]['name[0][value]']);
      $this->assertText($vpc_name);
      $this->assertText($subnet_name);

      // Make sure listing.
      $this->drupalGet("/clouds/design/server_template/list/$cloud_context");
      $this->assertResponse(200, t('HTTP 200: List | Server Template #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($edit[$j]['name[0][value]'],
                        t("Make sure w/ Listing @num: @name", [
                          '@num' => $j + 1,
                          '@name' => $edit[$j]['name[0][value]'],
                        ]));
      }
    }

    // Delete server_template Items
    // 3 times.
    for ($i = 0; $i < self::CLOUD_SERVER_TEMPLATES_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      $this->drupalGet("/clouds/design/server_template/delete/$cloud_context/$num");
      $this->drupalPostForm("/clouds/design/server_template/delete/$cloud_context/$num",
                            [],
                            t('Delete'));
      $this->assertResponse(200, t('HTTP 200: Server Template | Delete #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Make sure w/o Warnings'));

      // Make sure listing.
      $this->drupalGet("/clouds/design/server_template/list/$cloud_context");
      $this->assertResponse(200, t('HTTP 200: List | Server Template #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
      $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertNoText($edit[$j]['name[0][value]'],
                          t("Make sure w/ Listing @num: @name", [
                            '@num' => $j + 1,
                            '@name' => $edit[$j]['name[0][value]'],
                          ]));
      }
    }
  }

  /**
   * Tests CRUD for server_template revision information.
   */
  public function testCloudServerTemplateRevision() {
    $cloud_context = $this->cloudContext;

    $this->createImage();
    $this->createSecurityGroup();
    $this->createKeyPair();

    $vpcs = $this->createRandomVpcs();
    $subnets = $this->createRandomSubnets();
    $this->updateVpcsAndSubnetsToMockData($vpcs, $subnets);

    // IAM Roles.
    $iam_roles = $this->createRandomIamRoles();
    $this->updateIamRolesToMockData($iam_roles);

    // Create a server template.
    $add = $this->createServerTemplateTestData();

    $vpc_index = array_rand($vpcs);
    $add[0]['field_vpc'] = $vpcs[$vpc_index]['VpcId'];

    $subnet_index = array_rand($subnets);
    $add[0]['field_subnet'] = $subnets[$subnet_index]['SubnetId'];

    $this->drupalPostForm("/clouds/design/server_template/add/$cloud_context/aws_cloud",
                          $add[0],
                          t('Save'));
    $this->assertResponse(200, t('HTTP 200: Add | A New CloudServerTemplate Form #@num', ['@num' => 1]));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
    $this->assertText(
      t('Created the @name Cloud Server Template',
        [
          '@name' => $add[0]['name[0][value]'],
        ]
      ),
      t('Confirm Message: Created the @name Cloud Server Template',
        [
          '@name' => $add[0]['name[0][value]'],
        ]
      )
    );

    // Make sure listing revisions.
    $this->drupalGet("/clouds/design/server_template/$cloud_context/1/revisions");
    $this->assertResponse(200, t('HTTP 200: List | Server Template Revision'));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));

    // Create a new revision.
    $edit = $add[0];
    $old_description = $edit['field_description[0][value]'];
    $revision_desc = $this->random->name(32, TRUE);
    $edit['field_description[0][value]'] = $revision_desc;
    $edit['new_revision'] = '1';
    $this->drupalPostForm("/clouds/design/server_template/edit/$cloud_context/1",
                          $edit,
                          t('Save'));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
    $this->assertText(
      t('Saved the @name Cloud Server Template.', [
        '@name' => $edit['name[0][value]'],
      ]),
      t('Confirm Message: Saved the @name Cloud Server Template.',
        [
          '@name' => $edit['name[0][value]'],
        ])
    );

    // Make sure listing revisions.
    $this->drupalGet("/clouds/design/server_template/$cloud_context/1/revisions");
    $this->assertResponse(200, t('HTTP 200: List | Server Template Revision'));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
    $this->assertSession()->linkByHrefExists("server_template/$cloud_context/1/revisions/1/view");
    $this->assertSession()->linkByHrefExists("server_template/$cloud_context/1/revisions/1/revert");
    $this->assertSession()->linkByHrefExists("server_template/$cloud_context/1/revisions/1/delete");

    // View the revision.
    $this->drupalGet("/clouds/design/server_template/$cloud_context/1/revisions/1/view");
    $this->assertResponse(200, t('HTTP 200: View | Server Template Revision'));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
    $this->assertText($old_description);

    // Revert the revision.
    $this->drupalPostForm("/clouds/design/server_template/$cloud_context/1/revisions/1/revert",
                          [],
                          t('Revert'));
    $this->assertResponse(200, t('HTTP 200: List | Server Template Revision'));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
    $this->assertText(t('Cloud Server Template @name has been reverted', [
      '@name' => $edit['name[0][value]'],
    ]));
    // A new revision is created.
    $this->assertSession()->linkByHrefExists("server_template/$cloud_context/1/revisions/2/view");

    // Delete the revision.
    $this->drupalPostForm("/clouds/design/server_template/$cloud_context/1/revisions/1/delete",
                          [],
                          t('Delete'));
    $this->assertResponse(200, t('HTTP 200: List | Server Template Revision'));
    $this->assertNoText(t('Notice'), t('Make sure w/o Notice'));
    $this->assertNoText(t('Warning'), t('Make sure w/o Warnings'));
    $this->assertText(t('Cloud Server Template @name has been deleted.', [
      '@name' => $edit['name[0][value]'],
    ]));
    // The revision is deleted.
    $this->assertSession()->linkByHrefNotExists("server_template/$cloud_context/1/revisions/1/view");

    // Test copy function.
    $this->drupalGet('/clouds/design/server_template/list/' . $cloud_context);
    $this->clickLink($add[0]['name[0][value]']);
    $this->clickLink('Copy');
    $copy_url = $this->getUrl();
    $this->drupalPostForm($copy_url, [], 'Copy');
    $this->assertText('Server template copied.');
    $this->assertText('Copy of ' . $add[0]['name[0][value]']);
  }

  /**
   * Create server template test data.
   */
  private function createServerTemplateTestData() {
    $random = $this->random;

    // 3 times.
    for ($i = 0; $i < self::CLOUD_SERVER_TEMPLATES_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Input Fields.
      $data[] = [
        'cloud_context[0][value]' => $this->cloudContext,
        'name[0][value]' => "Template #$num - " . date('Y/m/d') . $random->name(16, TRUE),
        'field_description[0][value]' => "#$num: " . date('Y/m/d H:i:s - D M j G:i:s T Y')
        . ' - SimpleTest Server Template Description - '
        . $random->name(32, TRUE),
        'field_test_only[value]' => '1',
        'field_instance_type' => "m$num.xlarge",
        'field_availability_zone' => 'us-west-1',
        'field_monitoring[value]' => '1',
        'field_image_id' => 1,
        'field_min_count[0][value]' => 1,
        'field_max_count[0][value]' => 1,
        'status[value]' => '1',
        'field_kernel_id[0][value]' => 'aki-' . $this->getRandomAwsId(),
        'field_ram[0][value]' => 'ari-' . $this->getRandomAwsId(),
        'field_security_group' => 1,
        'field_ssh_key' => 1,
        'field_tags[0][tag_key]' => 'key_' . $random->name(8, TRUE),
        'field_tags[0][tag_value]' => 'value_' . $random->name(8, TRUE),
      ];
    }
    return $data;
  }

  /**
   * Create image.
   */
  private function createImage() {

    // Create image.
    $image_id = 'ami-' . $this->getRandomAwsId();
    $image = entity_create('aws_cloud_image', []);
    $image->setCloudContext($this->cloudContext);
    $image->set('ami_name', 'image1');
    $image->setImageId($image_id);
    $image->save();
  }

  /**
   * Create security group.
   */
  private function createSecurityGroup() {
    $random = $this->random;

    // Create security group.
    $group_id = 'sg-' . $this->getRandomAwsId();
    $group = entity_create('aws_cloud_security_group', []);
    $group->setCloudContext($this->cloudContext);
    $group->set('group_name', 'Group ' . $random->name(16));
    $group->setGroupId($group_id);
    $group->save();
  }

  /**
   * Create Key Pair.
   */
  private function createKeyPair() {
    $random = $this->random;

    // Create key pair.
    $key = entity_create('aws_cloud_key_pair', []);
    $key->setCloudContext($this->cloudContext);
    $key->set('key_pair_name', 'Key ' . $random->name(16));
    $key->save();
  }

  /**
   * Create random vpcs.
   *
   * @return array
   *   Random vpcs array.
   */
  private function createRandomVpcs() {
    $random = $this->random;

    $vpcs = [];
    $count = rand(1, 10);
    for ($i = 0; $i < $count; $i++) {
      $vpcs[] = [
        'VpcId' => 'vpc-' . $this->getRandomAwsId(),
        'Tags' => [
          ['Key' => 'Name', 'Value' => $random->name(10, TRUE)],
        ],
      ];

      $vpcs[] = [
        'VpcId' => 'vpc-' . $this->getRandomAwsId(),
      ];
    }

    return $vpcs;
  }

  /**
   * Create random subnets.
   *
   * @return array
   *   Random subnets array.
   */
  private function createRandomSubnets() {
    $random = $this->random;

    $subnets = [];
    $count = rand(1, 10);
    for ($i = 0; $i < $count; $i++) {
      $subnets[] = [
        'SubnetId' => 'subnet-' . $this->getRandomAwsId(),
        'Tags' => [
          ['Key' => 'Name', 'Value' => $random->name(10, TRUE)],
        ],
      ];

      $subnets[] = [
        'SubnetId' => 'subnet-' . $this->getRandomAwsId(),
      ];
    }

    return $subnets;
  }

  /**
   * Update vpcs and subnets to mock data.
   *
   * @param array $vpcs
   *   The vpcs array.
   * @param array $subnets
   *   The subnets array.
   */
  private function updateVpcsAndSubnetsToMockData(array $vpcs, array $subnets) {
    $mock_data = $this->getMockDataFromConfig();
    $mock_data['DescribeVpcs']['Vpcs'] = $vpcs;
    $mock_data['DescribeSubnets']['Subnets'] = $subnets;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Get name from array.
   *
   * @param array $array
   *   The vpcs or subnets array.
   * @param int $index
   *   The index of array.
   * @param string $id_key_name
   *   The key name of index.
   *
   * @return string
   *   value of array.
   */
  private function getNameFromArray(array $array, $index, $id_key_name) {
    // Get id.
    $id = $array[$index][$id_key_name];

    // Get name if Tags exists.
    $name = $id;
    if (isset($array[$index]['Tags'])) {
      $name = $array[$index]['Tags'][0]['Value'];
    }

    return sprintf('%s (%s)', $name, $id);
  }

}
