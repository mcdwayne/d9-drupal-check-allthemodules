<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

use Drupal\Tests\aws_cloud\Functional\Utils;

/**
 * Tests AWS Cloud Security Group.
 *
 * @group AWS Cloud
 */
class SecurityGroupTest extends AwsCloudTestCase {

  const AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT = 3;

  const AWS_CLOUD_SECURITY_GROUP_RULE_REPEAT_COUNT = 3;

  const RULES_INBOUND = 0;

  const RULES_OUTBOUND = 1;

  const RULES_MIX = 2;

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'list aws cloud security group',
      'add aws cloud security group',
      'view aws cloud security group',
      'edit aws cloud security group',
      'delete aws cloud security group',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMockDataTemplateVars() {
    return [
      'vpc_id' => 'vpc-' . $this->getRandomAwsId(),
      'cidr_block' => Utils::getRandomCidr(),
      'group_id' => 'sg-' . $this->getRandomAwsId(),
      'group_name' => $this->random->name(8, TRUE),
    ];
  }

  /**
   * Tests CRUD for Security Group information.
   */
  public function testSecurityGroup() {
    $cloud_context = $this->cloudContext;

    // List Security Group for Amazon EC2.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group");
    $this->assertResponse(200, t('List | HTTP 200: Security Group'));
    $this->assertNoText(t('Notice'), t('List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('List | Make sure w/o Warnings'));

    $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group/add");

    // Add a new Security Group.
    $add = $this->createSecurityGroupTestData();
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT; $i++) {
      $this->reloadMockData();

      $num = $i + 1;

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/security_group/add",
                            $add[$i],
                            t('Save'));

      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Security Group', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText($add[$i]['group_name'], t('Add | Key Pair: @group_name', ['@group_name' => $add[$i]['group_name']]));
      $this->assertText(
        t('The AWS Cloud Security Group "@group_name" has been created.', [
          '@group_name' => $add[$i]['group_name'],
        ]),
        t('Confirm Message: Add | The AWS Cloud Security Group "@group_name" has been created.', [
          '@group_name' => $add[$i]['group_name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group");
      $this->assertResponse(200, t('Add | List | HTTP 200: List | Security Group #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | List | Make sure w/o Warnings'));
      // 3 times.
      for ($j = 0; $j < $i + 1; $j++) {
        $this->assertText($add[$i]['group_name'],
                        t('Add | List | Make sure w/ Listing: @group_name', [
                          '@group_name' => $add[$i]['group_name'],
                        ]));
      }
    }

    // Security Group doesn't have an edit operation.
    // Edit an Security Group information.
    $edit = $this->createSecurityGroupTestData(TRUE);
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      unset($edit[$i]['description']);

      // Initialize the mock data. Run security_group update so the data
      // gets imported.
      $this->reloadMockData();

      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/security_group/$num/edit",
                            $edit[$i],
                            t('Save'));

      $this->assertResponse(200, t('Edit | HTTP 200: A New AWS Cloud Security Group #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | Make sure w/o Warnings'));
      $this->assertText(
        t('The AWS Cloud Security Group "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ]),
        t('Confirm Message: Edit | The AWS Cloud Security Group "@name" has been saved.', [
          '@name' => $edit[$i]['name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group");
      $this->assertResponse(200, t('Edit | List | HTTP 200: Security Group #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      $this->assertText($edit[$i]['name'], t('key_pair: @name',
                        ['@name' => $edit[$i]['name']]));
    }

    // Delete Security Group.
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group/$num/delete");
      $this->assertResponse(200, t('Delete | HTTP 200: Security Group #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/security_group/$num/delete",
                            [],
                            t('Delete'));

      $this->assertResponse(200, t('Delete | HTTP 200: The Cloud Security Group #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | Make sure w/o Warnings'));
      $this->assertText($add[$i]['group_name'], t('Group Name: @group_name', ['@group_name' => $add[$i]['group_name']]));
      $this->assertText(
        t('The AWS Cloud Security Group "@group_name" has been deleted.', [
          '@group_name' => $add[$i]['group_name'],
        ]),
        t('Confirm Message: Delete | The AWS Cloud Security Group "@group_name" has been deleted.', [
          '@group_name' => $add[$i]['group_name'],
        ])
      );

      // Make sure listing.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group");
      $this->assertResponse(200, t('HTTP 200: Delete | Security Group #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Delete | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Delete | List | Make sure w/o Warnings'));
    }
  }

  /**
   * Test that permissions are being pulled in from the api.
   */
  public function testIpPermissionUpdateFromApi() {
    $this->repeatTestIpPermissionsFromApiUpdate(self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT);
  }

  /**
   * Private test function.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestIpPermissionsFromApiUpdate($max_test_repeat_count = 1) {
    $cloud_context = $this->cloudContext;
    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      $this->reloadMockData();

      // Get the default variables.
      $defaults = $this->latestTemplateVars;

      $rules = [
        [
          'type' => self::RULES_INBOUND,
          'source' => 'ip4',
          'cidr_ip' => Utils::getRandomCidr(),
          'from_port' => Utils::getRandomFromPort(),
          'to_port' => Utils::getRandomToPort(),
        ],
        [
          'type' => self::RULES_INBOUND,
          'source' => 'ip6',
          'cidr_ip_v6' => Utils::getRandomCidrV6(),
          'from_port' => Utils::getRandomFromPort(),
          'to_port' => Utils::getRandomToPort(),
        ],
        [
          'type' => self::RULES_INBOUND,
          'source' => 'group',
          'user_id' => $this->random->name(8, TRUE),
          'group_name' => $this->random->name(8, TRUE),
          'group_id' => 'sg-' . $this->getRandomAwsId(),
          'vpc_id' => 'vpc-' . $this->getRandomAwsId(),
          'peering_connection_id' => 'pcx-' . $this->getRandomAwsId(),
          'peering_status' => 'active',
          'from_port' => Utils::getRandomFromPort(),
          'to_port' => Utils::getRandomToPort(),
        ],
        [
          'type' => self::RULES_OUTBOUND,
          'source' => 'ip4',
          'cidr_ip' => Utils::getRandomCidr(),
          'from_port' => Utils::getRandomFromPort(),
          'to_port' => Utils::getRandomToPort(),
        ],
        [
          'type' => self::RULES_OUTBOUND,
          'source' => 'ip6',
          'cidr_ip_v6' => Utils::getRandomCidrV6(),
          'from_port' => Utils::getRandomFromPort(),
          'to_port' => Utils::getRandomToPort(),
        ],
        [
          'type' => self::RULES_OUTBOUND,
          'source' => 'group',
          'user_id' => $this->random->name(8, TRUE),
          'group_name' => $this->random->name(8, TRUE),
          'group_id' => 'sg-' . $this->getRandomAwsId(),
          'vpc_id' => 'vpc-' . $this->getRandomAwsId(),
          'peering_connection_id' => 'pcx-' . $this->getRandomAwsId(),
          'peering_status' => 'active',
          'from_port' => Utils::getRandomFromPort(),
          'to_port' => Utils::getRandomToPort(),
        ],
      ];

      $this->updateRulesMockData($rules);

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group/update");
      $this->assertResponse(200);

      // Navigate to the group listing page.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group");

      // Click on a specific group.
      $this->clickLink($defaults['group_name']);
      $this->assertText($defaults['group_name'], t('Group Name'));

      // Assert permissions.
      foreach ($rules as $rule) {
        $type_name = $rule['type'] == self::RULES_INBOUND ? 'Inbound' : 'Outbound';
        foreach ($rule as $key => $value) {
          if ($key == 'type' || $key == 'source') {
            continue;
          }

          $this->assertText(
            $rule[$key],
            t("@type @key",
              [
                '@type' => $type_name,
                '@key' => $key,
              ]
            )
          );
        }
      }
    }
  }

  /**
   * Test for editing ip permissions.
   */
  public function testIpPermissionsEdit() {
    $this->repeatTestIpPermissionsEdit(self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT);
  }

  /**
   * Test for editing ip permissions.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestIpPermissionsEdit($max_test_repeat_count = 1) {
    $cloud_context = $this->cloudContext;

    $add = $this->createSecurityGroupTestData();

    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      $num = $i + 1;

      $this->reloadMockData();

      $defaults = $this->latestTemplateVars;
      $defaults['group_name'] = $add[$i]['group_name'];
      $add[$i]['vpc_id'] = $defaults['vpc_id'];

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group/add");
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/security_group/add",
        $add[$i],
        t('Save'));

      // After save, assert the save is successful.
      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Security Group', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText($add[$i]['group_name'], t('Add | Key Pair: @group_name', ['@group_name' => $add[$i]['group_name']]));
      $this->assertText(
        t('The AWS Cloud Security Group "@group_name" has been created.', [
          '@group_name' => $add[$i]['group_name'],
        ]),
        t('Confirm Message: Add | The AWS Cloud Security Group "@group_name" has been created.', [
          '@group_name' => $add[$i]['group_name'],
        ])
      );

      $edit_url = "/clouds/aws_cloud/$cloud_context/security_group/$num/edit";
      $view_url = "/clouds/aws_cloud/$cloud_context/security_group";

      // Test case 1. (Inbound rule add (only) / delete).
      $rules = $this->createRules(self::RULES_INBOUND, $edit_url);
      $this->revokeRules($rules, $view_url);

      // Test case 2. (Outbound rule (only) add / delete).
      $rules = $this->createRules(self::RULES_OUTBOUND, $edit_url);
      $this->revokeRules($rules, $view_url);

      // Test case 3. (Combination of mixing above Test case 1. and 2.).
      $rules = $this->createRules(self::RULES_MIX, $edit_url);
      $this->revokeRules($rules, $view_url);
    }
  }

  /**
   * Test the validation constraints.
   */
  public function testIpPermissionsValidate() {
    return $this->repeatTestIpPermissionsValidate(self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT);
  }

  /**
   * Test the validation constraints.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestIpPermissionsValidate($max_test_repeat_count = 1) {
    $cloud_context = $this->cloudContext;

    $add = $this->createSecurityGroupTestData();

    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      $num = $i + 1;

      $this->reloadMockData();

      $defaults = $this->latestTemplateVars;
      $defaults['group_name'] = $add[$i]['group_name'];
      $add[$i]['vpc_id'] = $defaults['vpc_id'];

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group/add");
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/security_group/add",
        $add[$i],
        t('Save'));

      // After save, assert the save is successful.
      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Security Group', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText($add[$i]['group_name'], t('Add | Key Pair: @group_name', ['@group_name' => $add[$i]['group_name']]));
      $this->assertText(
        t('The AWS Cloud Security Group "@group_name" has been created.', [
          '@group_name' => $add[$i]['group_name'],
        ]),
        t('Confirm Message: Add | The AWS Cloud Security Group "@group_name" has been created.', [
          '@group_name' => $add[$i]['group_name'],
        ]));

      // Verify From port validation error.
      $rules = [
        'ip_permission[0][from_port]' => $this->random->name(2, TRUE),
        'ip_permission[0][to_port]' => Utils::getRandomToPort(),
        'ip_permission[0][cidr_ip]' => Utils::getRandomCidr(),
        'ip_permission[0][source]' => 'ip4',
      ];
      $this->drupalPostForm($this->getUrl(), $rules, t('Save'));
      $this->assertText(t('The From Port is not numeric.'), t('Number From Port test'));

      // Verify From port validation error.
      $rules = [
        'ip_permission[0][from_port]' => Utils::getRandomFromPort(),
        'ip_permission[0][to_port]' => $this->random->name(2, TRUE),
        'ip_permission[0][cidr_ip]' => Utils::getRandomCidr(),
        'ip_permission[0][source]' => 'ip4',
      ];
      $this->drupalPostForm($this->getUrl(), $rules, t('Save'));
      $this->assertText(t('The To Port is not numeric.'), t('Numeric To Port test'));

      // Verify CIDR IP empty test.
      $rules = [
        'ip_permission[0][from_port]' => Utils::getRandomFromPort(),
        'ip_permission[0][to_port]' => Utils::getRandomToPort(),
        'ip_permission[0][cidr_ip]' => '',
        'ip_permission[0][source]' => 'ip4',
      ];
      $this->drupalPostForm($this->getUrl(), $rules, t('Save'));
      $this->assertText(t('CIDR ip is empty.'), t('CIDR ip empty test'));

      // Verify valid CIDR IP address.
      $rules = [
        'ip_permission[0][from_port]' => Utils::getRandomFromPort(),
        'ip_permission[0][to_port]' => Utils::getRandomToPort(),
        'ip_permission[0][cidr_ip]' => Utils::getRandomPublicIp(),
        'ip_permission[0][source]' => 'ip4',
      ];
      $this->drupalPostForm($this->getUrl(), $rules, t('Save'));
      $this->assertText(t('CIDR ip is not valid. Single ip addresses must be in x.x.x.x/32 notation.'), t('CIDR ip valid test'));

      // Verify valid CIDR IPv6 address.
      $rules = [
        'ip_permission[0][from_port]' => Utils::getRandomFromPort(),
        'ip_permission[0][to_port]' => Utils::getRandomToPort(),
        'ip_permission[0][cidr_ip_v6]' => Utils::getRandomPublicIp(),
        'ip_permission[0][source]' => 'ip6',
      ];
      $this->drupalPostForm($this->getUrl(), $rules, t('Save'));
      $this->assertText(t('CIDR ipv6 is not valid. Single ip addresses must be in x.x.x.x/32 notation.'), t('CIDR ipv6 valid test'));

      // Verify CIDR IPv6 empty test.
      $rules = [
        'ip_permission[0][from_port]' => Utils::getRandomFromPort(),
        'ip_permission[0][to_port]' => Utils::getRandomToPort(),
        'ip_permission[0][cidr_ip]' => '',
        'ip_permission[0][source]' => 'ip6',
      ];
      $this->drupalPostForm($this->getUrl(), $rules, t('Save'));
      $this->assertText(t('CIDR ipv6 is empty.'), t('CIDR ipv6 empty test'));

      // Verify Group Id.
      $rules = [
        'ip_permission[0][from_port]' => Utils::getRandomFromPort(),
        'ip_permission[0][to_port]' => Utils::getRandomToPort(),
        'ip_permission[0][group_id]' => '',
        'ip_permission[0][source]' => 'group',
      ];
      $this->drupalPostForm($this->getUrl(), $rules, t('Save'));
      $this->assertText(t('Group id is empty.'), t('Group id empty test'));

      // Verify to port is not greater than from port.
      $rules = [
        'ip_permission[0][from_port]' => Utils::getRandomToPort(),
        'ip_permission[0][to_port]' => Utils::getRandomFromPort(),
        'ip_permission[0][cidr_ip]' => Utils::getRandomCidr(),
        'ip_permission[0][source]' => 'ip4',
      ];
      $this->drupalPostForm($this->getUrl(), $rules, t('Save'));
      $this->assertText(t('From Port is greater than To Port.'), t('From port greater than to port test'));
    }

    // Validate errors related to non-vpc.
    $add = $this->createSecurityGroupTestData();

    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      $num = $i + 1 + $max_test_repeat_count;

      $this->reloadMockData();

      $defaults = $this->latestTemplateVars;
      $defaults['group_name'] = $add[$i]['group_name'];
      $add[$i]['vpc_id'] = '';

      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group/add");
      $this->drupalPostForm("/clouds/aws_cloud/$cloud_context/security_group/add",
        $add[$i],
        t('Save'));

      // After save, assert the save is successful.
      $this->assertResponse(200, t('Add | HTTP 200: A New AWS Cloud Security Group', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Add | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Add | Make sure w/o Warnings'));
      $this->assertText($add[$i]['group_name'], t('Add | Key Pair: @group_name', ['@group_name' => $add[$i]['group_name']]));
      $this->assertText(
        t('The AWS Cloud Security Group "@group_name" has been created.', [
          '@group_name' => $add[$i]['group_name'],
        ]),
        t('Confirm Message: Add | The AWS Cloud Security Group "@group_name" has been created.', [
          '@group_name' => $add[$i]['group_name'],
        ]));

      // Verify Group Name.
      $rules = [
        'ip_permission[0][from_port]' => Utils::getRandomFromPort(),
        'ip_permission[0][to_port]' => Utils::getRandomToPort(),
        'ip_permission[0][group_name]' => '',
        'ip_permission[0][source]' => 'group',
      ];
      $this->drupalPostForm($this->getUrl(), $rules, t('Save'));
      $this->assertText(t('Group name is empty.'), t('Group name empty test'));
    }
  }

  /**
   * Create security group test data.
   *
   * @param bool $is_edit
   *   Whether edit mode or not.
   *
   * @return array
   *   Security group test data.
   */
  private function createSecurityGroupTestData($is_edit = FALSE) {
    $data = [];

    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Input Fields.
      $data[$i] = [
        'description' => "Description #$num - " . $this->random->name(64, TRUE),
      ];

      if ($is_edit) {
        $data[$i]['name'] = "group-name-#$num - " . $this->random->name(15, TRUE);
      }
      else {
        $data[$i]['group_name'] = "group-name-#$num - " . $this->random->name(15, TRUE);
      }
    }
    return $data;
  }

  /**
   * Create rules.
   *
   * @param int $rules_type
   *   The type of rules. Inbound | Outbound | Mixed.
   * @param string $edit_url
   *   The URL of security group edit form.
   *
   * @return array
   *   The rules created.
   */
  private function createRules($rules_type, $edit_url) {
    $rules = [];
    $count = rand(1, self::AWS_CLOUD_SECURITY_GROUP_RULE_REPEAT_COUNT);
    for ($i = 0; $i < $count; $i++) {
      $permissions = [
        [
          'source' => 'ip4',
          'cidr_ip' => Utils::getRandomCidr(),
          'from_port' => Utils::getRandomFromPort(),
          'to_port' => Utils::getRandomToPort(),
        ],
        [
          'source' => 'ip6',
          'cidr_ip_v6' => Utils::getRandomCidrV6(),
          'from_port' => Utils::getRandomFromPort(),
          'to_port' => Utils::getRandomToPort(),
        ],
        [
          'source' => 'group',
          'user_id' => $this->random->name(8, TRUE),
          'group_name' => $this->random->name(8, TRUE),
          'group_id' => 'sg-' . $this->getRandomAwsId(),
          'vpc_id' => 'vpc-' . $this->getRandomAwsId(),
          'peering_connection_id' => 'pcx-' . $this->getRandomAwsId(),
          'peering_status' => 'active',
          'from_port' => Utils::getRandomFromPort(),
          'to_port' => Utils::getRandomToPort(),
        ],
      ];

      if ($rules_type == self::RULES_INBOUND || $rules_type == self::RULES_OUTBOUND) {
        $type = $rules_type;
      }
      else {
        $types = [self::RULES_INBOUND, self::RULES_OUTBOUND];
        $type = $types[array_rand($types)];
      }
      $rules[] = $permissions[array_rand($permissions)] + ['type' => $type];
    }

    // Post to form.
    $params = [];
    $inbound_index = 0;
    $outbound_index = 0;
    $rules_added = [];
    foreach ($rules as $rule) {
      if ($rule['type'] == self::RULES_INBOUND) {
        $index = $inbound_index++;
        $prefix = 'ip_permission';
      }
      else {
        $index = $outbound_index++;
        $prefix = 'outbound_permission';
      }

      foreach ($rule as $key => $value) {
        if ($key == 'type') {
          continue;
        }
        $params["${prefix}[${index}][${key}]"] = $value;
      }

      $rules_added[] = $rule;
      $this->updateRulesMockData($rules_added);

      $this->drupalPostForm($edit_url, $params, t('Save'));
      $this->assertText($rule['from_port'], 'Create Rule');
    }

    return $rules;
  }

  /**
   * Revoke rules.
   *
   * @param array $rules
   *   The rules to be revoked.
   * @param string $view_url
   *   The URL of security group detailed view.
   */
  private function revokeRules(array $rules, $view_url) {
    $count = count($rules);
    $inbound_rules = array_filter($rules, function ($a) {
      return $a['type'] == self::RULES_INBOUND;
    });
    $inbound_rules_count = count($inbound_rules);
    for ($i = 0; $i < $count; $i++) {
      $rule = array_shift($rules);

      $index = 0;
      if ($rule['type'] == self::RULES_OUTBOUND) {
        $index += $inbound_rules_count;
      }
      else {
        $inbound_rules_count--;
      }

      $this->clickLink(t('Revoke'), $index);
      $this->assertText($rule['from_port'], 'Revoke Rule');

      $this->updateRulesMockData($rules);
      $this->submitForm([], t('Revoke'));
      $this->assertText('Permission revoked', 'Revoke Rule');
    }
  }

  /**
   * Update mock data related to security group rules.
   *
   * @param array $rules
   *   The security group rules.
   */
  private function updateRulesMockData(array $rules) {
    $mock_data = $this->getMockDataFromConfig();

    $security_group =& $mock_data['DescribeSecurityGroups']['SecurityGroups'][0];
    $security_group['IpPermissions'] = [];
    $security_group['IpPermissionsEgress'] = [];
    foreach ($rules as $rule) {
      $permission_name = 'IpPermissions';
      if ($rule['type'] == self::RULES_OUTBOUND) {
        $permission_name = 'IpPermissionsEgress';
      }

      $permission = [
        'IpProtocol' => 'tcp',
        'FromPort' => $rule['from_port'],
        'ToPort' => $rule['to_port'],
      ];

      if ($rule['source'] == 'ip4') {
        $permission['IpRanges'] = [
          ['CidrIp' => $rule['cidr_ip']],
        ];
      }
      elseif ($rule['source'] == 'ip6') {
        $permission['Ipv6Ranges'] = [
          ['CidrIpv6' => $rule['cidr_ip_v6']],
        ];
      }
      elseif ($rule['source'] == 'group') {
        $permission['UserIdGroupPairs'] = [
          [
            'UserId' => $rule['user_id'],
            'GroupName' => $rule['group_name'],
            'GroupId' => $rule['group_id'],
            'VpcId' => $rule['vpc_id'],
            'VpcPeeringConnectionId' => $rule['peering_connection_id'],
            'PeeringStatus' => $rule['peering_status'],
          ],
        ];
      }

      $security_group[$permission_name][] = $permission;
    }

    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Tests updating security groups.
   */
  public function testUpdateSecurityGroupList() {
    $cloud_context = $this->cloudContext;

    // Delete init mock data.
    $this->deleteFirstSecurityGroupInMockData();

    // Add new Security Groups.
    $add = $this->createSecurityGroupTestData();
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT; $i++) {
      $num = $i + 1;
      $this->addSecurityGroupMockData($add[$i]['group_name'], $add[$i]['description']);
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group");
    $this->assertResponse(200, t('Edit | List | HTTP 200: SecurityGroup #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT; $i++) {
      $this->assertNoText($add[$i]['group_name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['group_name'],
        ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Security Groups.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT; $i++) {
      $this->assertText($add[$i]['group_name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['group_name'],
        ]));
    }

    // Make sure detailed and edit view.
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT; $i++) {

      $num = $i + 1;

      // Confirm the detailed view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group/$num");
      $this->assertLink(t('Edit'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/security_group/$num/edit");
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/security_group/$num/delete");
      $this->assertLink(t('List AWS Cloud Security Groups'));
      // Click 'Refresh'.
      $this->clickLink(t('List AWS Cloud Security Groups'));
      $this->assertResponse(200, t('Edit | List | HTTP 200: SecurityGroup #@num', ['@num' => $num]));
      $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
      $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));

      // Confirm the edit view.
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group/$num/edit");
      $this->assertNoLink(t('Edit'));
      $this->assertLink(t('Delete'));
      $this->assertLinkByHref("/clouds/aws_cloud/$cloud_context/security_group/$num/delete");
      $this->assertNoLink('Edit');
    }

    // Add a new Security Group.
    $num++;
    $data = [
      'description' => "Description #$num - " . $this->random->name(64, TRUE),
      'group_name' => "group-name-#$num - " . $this->random->name(15, TRUE),
    ];
    $this->addSecurityGroupMockData($data['group_name'], $data['description']);

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group");
    $this->assertResponse(200, t('Edit | List | HTTP 200: SecurityGroup #@num', ['@num' => $num]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    $this->assertNoText($data['group_name'],
      t('Edit | List | Make sure w/ Listing: @name', [
        '@name' => $data['group_name'],
      ]));

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Security Groups.'));
    $add = array_merge($add, [$data]);
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT + 1; $i++) {
      $this->assertText($add[$i]['group_name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['group_name'],
        ]));
    }

    // Delete SecurityGroup in mock data.
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT + 1; $i++) {
      $this->deleteFirstSecurityGroupInMockData();
    }

    // Make sure listing.
    $this->drupalGet("/clouds/aws_cloud/$cloud_context/security_group");
    $this->assertResponse(200, t('Edit | List | HTTP 200: SecurityGroup #@num', ['@num' => $num + 1]));
    $this->assertNoText(t('Notice'), t('Edit | List | Make sure w/o Notice'));
    $this->assertNoText(t('warning'), t('Edit | List | Make sure w/o Warnings'));
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT + 1; $i++) {
      $this->assertText($add[$i]['group_name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['group_name'],
        ]));
    }

    // Click 'Refresh'.
    $this->clickLink(t('Refresh'));
    $this->assertText(t('Updated Security Groups.'));
    // Make sure listing.
    for ($i = 0; $i < self::AWS_CLOUD_SECURITY_GROUP_REPEAT_COUNT + 1; $i++) {
      $this->assertNoText($add[$i]['group_name'],
        t('Edit | List | Make sure w/ Listing: @name', [
          '@name' => $add[$i]['group_name'],
        ]));
    }

  }

  /**
   * Add security group mock data.
   *
   * @param string $name
   *   The security group name.
   * @param string $description
   *   The description.
   */
  private function addSecurityGroupMockData($name, $description) {
    $mock_data = $this->getMockDataFromConfig();
    $vars = $this->getMockDataTemplateVars();
    $security_group = [
      'GroupId' => $vars['group_id'],
      'GroupName' => $name,
      'Description' => $description,
      'VpcId' => $vars['vpc_id'],
      'OwnerId' => $this->random->name(8, TRUE),
    ];
    $mock_data['DescribeSecurityGroups']['SecurityGroups'][] = $security_group;
    $this->updateMockDataToConfig($mock_data);
  }

  /**
   * Delete first security group in mock data.
   */
  private function deleteFirstSecurityGroupInMockData() {
    $mock_data = $this->getMockDataFromConfig();
    $security_groups = $mock_data['DescribeSecurityGroups']['SecurityGroups'];
    array_shift($security_groups);
    $mock_data['DescribeSecurityGroups']['SecurityGroups'] = $security_groups;
    $this->updateMockDataToConfig($mock_data);
  }

}
