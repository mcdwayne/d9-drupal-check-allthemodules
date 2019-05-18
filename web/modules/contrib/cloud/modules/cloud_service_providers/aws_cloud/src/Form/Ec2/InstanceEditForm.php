<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\aws_cloud\Entity\Ec2\ElasticIp;
use Drupal\aws_cloud\Entity\Ec2\PublicIpEntityLinkHtmlGenerator;

/**
 * Form controller for the CloudScripting entity edit forms.
 *
 * @ingroup aws_cloud
 */
class InstanceEditForm extends AwsCloudContentForm {

  const SECURITY_GROUP_DELIMITER = ', ';

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\Instance */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $weight = -50;

    $form['instance'] = [
      '#type' => 'details',
      '#title' => $this->t('Instance'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['instance']['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->label(),
      '#required'      => FALSE,
    ];

    $form['instance']['instance_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Instance ID')),
      '#markup'        => $entity->getInstanceId(),
    ];

    $form['instance']['instance_state'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Instance State')),
      '#markup'        => $entity->getInstanceState(),
    ];

    $form['instance']['instance_type'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Instance Type'),
      '#wrapped_label' => TRUE,
      '#default_value' => $entity->getInstanceType(),
      '#required'      => FALSE,
      '#options'       => $this->getInstanceTypeOptions(),
    ];

    $form['instance']['cost'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Cost')),
      '#markup'        => '$' . $entity->getCost(),
    ];

    $form['instance']['iam_role'] = [
      '#type'          => 'select',
      '#title'         => $this->t('IAM Role'),
      '#wrapped_label' => TRUE,
      '#default_value' => $entity->getIamRole(),
      '#required'      => FALSE,
      '#options'       => $this->getIamRoleOptions(),
      '#empty_value'   => '',
      '#empty_option'  => $this->t('No Role'),
    ];

    if ($entity->getInstanceState() !== 'stopped') {
      $form['instance']['instance_type'] += [
        '#attributes'  => ['readonly' => 'readonly'],
        '#disabled'    => TRUE,
      ];
    }

    $form['instance']['image_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('AMI Image')),
      '#markup'        => $entity->getImageId(),
    ];

    $form['instance']['kernel_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Kernel Image')),
      '#markup'        => $entity->getKernelId(),
    ];

    $form['instance']['ramdisk_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Ramdisk Image')),
      '#markup'        => $entity->getRamdiskId(),
    ];

    $form['instance']['virtualization'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Virtualization')),
      '#markup'        => $entity->getVirtualization(),
    ];

    $form['instance']['reservation'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Reservation')),
      '#markup'        => $entity->getReservation(),
    ];

    $form['instance']['launch_time'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Launch Time')),
      '#markup'        => format_date($entity->getLaunchTime(), 'short'),
    ];

    $form['network'] = [
      '#type' => 'details',
      '#title' => $this->t('Network'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $public_ip = $entity->getPublicIp();
    $current_elastic_ip = isset($public_ip) ? $this->getElasticIpByIpLookup($public_ip) : FALSE;

    // Public IP is not an elastic ip.  It is an AWS assigned IP address.
    // Just display it.
    if (isset($public_ip) && $current_elastic_ip == FALSE) {
      $form['network']['public_ip'] = $this->entityLinkRenderer->renderFormElements(
        $entity->getPublicIp(),
        'aws_cloud_elastic_ip',
        'public_ip',
        ['#title' => $this->getItemTitle($this->t('Public IP'))]
      );
    }
    else {
      if ($entity->getInstanceState() == 'stopped') {
        // If no elastic_ip assigned and no available ips, prompt user to create
        // one.
        if (count($this->getAvailableElasticIpCount()) == 0 && $current_elastic_ip == FALSE) {
          $link = Link::createFromRoute($this->t('Create a new Elastic IP'), 'view.aws_elastic_ip.page_1', [
            'cloud_context' => $entity->getCloudContext(),
          ])->toString();
          $form['network']['elastic_ip_link'] = [
            '#type'          => 'item',
            '#title'         => $this->getItemTitle($this->t('Elastic IP')),
            '#markup'        => $link,
            '#not_field'     => TRUE,
          ];
        }
        elseif (count($this->getNetworkInterfaceCount()) > 1) {
          // If instance has more than one network Interface, link will go to
          // elastic ip list page.
          $link = Link::createFromRoute($this->t('Associate Elastic IP'), 'view.aws_elastic_ip.page_1', [
            'cloud_context' => $entity->getCloudContext(),
          ])->toString();

          $form['network']['elastic_ip_link'] = [
            '#type'          => 'item',
            '#title'         => $this->getItemTitle($this->t('Elastic IP')),
            '#markup'        => $link,
          ];
        }
        else {
          $available_elastic_ips = $this->getAvailableElasticIps();
          if ($current_elastic_ip != FALSE) {
            unset($available_elastic_ips[-1]);
            $available_elastic_ips[$current_elastic_ip->getAllocationId()] = $current_elastic_ip->getPublicIp();
          }

          $form['network']['add_new_elastic_ip'] = [
            '#type' => 'select',
            '#title' => $this->getItemTitle($this->t('Elastic IP')),
            '#options' => $available_elastic_ips,
            '#default_value' => $current_elastic_ip != FALSE ? $current_elastic_ip->getAllocationId() : '',
          ];

          // Store the current allocation id so we can use it to compare after
          // the form is submitted.
          if ($current_elastic_ip != FALSE) {
            $form['network']['current_allocation_id'] = [
              '#type' => 'value',
              '#value' => $current_elastic_ip->getAllocationId(),
            ];
            $form['network']['current_association_id'] = [
              '#type' => 'value',
              '#value' => $current_elastic_ip->getAssociationId(),
            ];
          }
        }
      }
      else {
        if ($current_elastic_ip != FALSE) {
          $form['network']['public_ip'] = $this->entityLinkRenderer->renderFormElements(
            $entity->getPublicIp(),
            'aws_cloud_elastic_ip',
            'public_ip',
            [
              '#title' => $this->getItemTitle($this->t('Elastic IP')),
            ],
            '',
            PublicIpEntityLinkHtmlGenerator::class
          );
        }
      }
    }

    $form['network']['public_dns'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Public DNS')),
      '#markup'        => $entity->getPublicDns(),
    ];

    $form['network']['private_ips'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Private IPs')),
      '#markup'        => $entity->getPrivateIps(),
    ];

    $form['network']['security_groups'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Security Groups'),
      '#default_value' => explode(self::SECURITY_GROUP_DELIMITER,
                                  $entity->getSecurityGroups()),
      '#required'      => TRUE,
      '#multiple'      => TRUE,
      '#options'       => $this->getSecurityGroupsOptions(),
    ];

    $form['network']['key_pair_name'] = $this->entityLinkRenderer->renderFormElements(
      $entity->getKeyPairName(),
      'aws_cloud_key_pair',
      'key_pair_name',
      ['#title' => $this->getItemTitle($this->t('Key Pair Name'))]
    );

    $form['network']['vpc_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('VPC ID')),
      '#markup'        => $entity->getVpcId(),
    ];

    $form['network']['subnet_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Subnet ID')),
      '#markup'        => $entity->getSubnetId(),
    ];

    $form['network']['availability_zone'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Availability Zone')),
      '#markup'        => $entity->getAvailabilityZone(),
    ];

    $interfaces = [];
    foreach ($entity->getNetworkInterfaces() as $interface) {
      $render_element = $this->entityLinkRenderer->renderViewElement(
        $interface['value'],
        'aws_cloud_network_interface',
        'network_interface_id'
      );
      $interfaces[] = '<div>' . $render_element['#markup'] . '</div>';
    }

    if (count($interfaces)) {
      $form['network']['network_interfaces'] = [
        '#type' => 'item',
        '#title' => $this->getItemTitle($this->t('Network Interfaces')),
        '#markup' => implode($interfaces),
      ];
    }

    $form['storage'] = [
      '#type'          => 'details',
      '#title'         => $this->t('Storage'),
      '#open'          => TRUE,
      '#weight'        => $weight++,
    ];

    $form['storage']['root_device_type'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Root Device Type')),
      '#markup'        => $entity->getRootDeviceType(),
    ];

    $form['storage']['root_device'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Root Device')),
      '#markup'        => $entity->getRootDevice(),
    ];

    $form['storage']['ebs_optimized'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('EBS Optimized')),
      '#markup'        => $entity->getEbsOptimized() == 0 ? 'Off' : 'On',
    ];

    $form['fieldset_tags'] = [
      '#type'          => 'details',
      '#title'         => $this->t('Tags'),
      '#open'          => TRUE,
      '#weight'        => $weight++,
    ];

    $form['fieldset_tags'][] = $form['tags'];
    unset($form['tags']);

    $form['options'] = [
      '#type'          => 'details',
      '#title'         => $this->t('Options'),
      '#open'          => TRUE,
      '#weight'        => $weight++,
    ];

    $form['options']['termination_protection'] = [
      '#title'         => t('Termination Protection'),
      '#type'          => 'checkbox',
      '#description'   => t('Indicates whether termination protection is enabled. If enabled, this instance cannot be terminated using the console, API, or CLI until termination protection is disabled.'),
      '#default_value' => $entity->getTerminationProtection(),
      '#weight'        => $weight++,
    ];

    $form['options']['is_monitoring'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Monitoring Enabled')),
      '#markup'        => $entity->isMonitoring()
      ? $this->t('Enabled') : $this->t('Disabled'),
      '#weight'        => $weight++,
    ];

    $form['options']['ami_launch_index'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('AMI Launch Index')),
      '#markup'        => $entity->getAmiLaunchIndex(),
      '#weight'        => $weight++,
    ];

    $form['options']['tenancy'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Tenancy')),
      '#markup'        => $entity->getTenancy(),
      '#weight'        => $weight++,
    ];

    // Set a message for termination timestamp.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $form['termination_timestamp']['widget'][0]['value']['#description']
      = $this->t('Format: %format. Leave blank for no automatic termination.',
                ['%format' => Datetime::formatExample($date_format . ' ' . $time_format)]);

    $form['termination_timestamp']['#weight'] = $weight++;
    $form['options']['termination_timestamp'] = $form['termination_timestamp'];
    unset($form['termination_timestamp']);

    $schedule = $entity->getSchedule();
    $form['options']['schedule'] = [
      '#title' => t('Schedule'),
      '#type' => 'select',
      '#default_value' => isset($schedule) ? $entity->getSchedule() : '',
      '#options'       => aws_cloud_get_schedule(),
      '#description'   => t('Specify a start/stop schedule. This helps reduce server hosting costs.'),
      '#weight'        => $weight++,
    ];

    $form['options']['login_username'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Login Username')),
      '#markup'        => $entity->getLoginUsername() ?: 'ec2-user',
      '#weight'        => $weight++,
    ];

    $form['options']['user_data'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('User Data'),
      '#maxlength'     => 4096,
      '#cols'          => 60,
      '#rows'          => 3,
      '#default_value' => $entity->getUserData(),
      '#required'      => FALSE,
      '#attributes'    => ['readonly' => 'readonly'],
      '#disabled'      => TRUE,
      '#weight'        => $weight++,
    ];

    $this->addOthersFieldset($form, $weight++);

    $form['actions'] = $this->actions($form, $form_state, $cloud_context);
    $form['actions']['#weight'] = $weight++;

    // Hide delete button if termination_protection is selected.
    if ($entity->getTerminationProtection() == 1) {
      $form['actions']['delete']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $termination_timestamp = $form_state->getValue('termination_timestamp')[0]['value'];
    $termination_protection = $form_state->getValue('termination_protection');
    if ($termination_timestamp != NULL && $termination_protection == 1) {
      $form_state->setErrorByName(
        'termination_timestamp',
        t('"@name1" should be left blank if "@name2" is selected. Please leave "@name1" blank or unselect "@name2".',
          ['@name1' => t('Termination Date'), '@name2' => t('Termination Protection')]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\Core\Datetime\DrupalDateTime $termination_timestamp */
    $termination_timestamp = $form_state->getValue('termination_timestamp')[0]['value'];
    if ($termination_timestamp == NULL) {
      // Unset the termination timestamp.
      $this->entity->set('termination_timestamp', NULL);
    }

    $security_groups = array_values($form_state->getValue('security_groups'));
    if ($security_groups == NULL) {
      $security_groups = [];
    }
    $this->entity->setSecurityGroups(implode(self::SECURITY_GROUP_DELIMITER, $security_groups));

    parent::save($form, $form_state);

    $this->updateTagsField($form, $form_state);

    $this->awsEc2Service->setCloudContext($this->entity->getCloudContext());

    $this->updateAwsTags();

    // Update the instance type.
    if ($this->entity->getInstanceState() == 'stopped'
      && $this->entity->getInstanceType() != $form['instance']['instance_type']['#default_value']) {

      $this->awsEc2Service->modifyInstanceAttribute([
        'InstanceId' => $this->entity->getInstanceId(),
        'InstanceType' => ['Value' => $this->entity->getInstanceType()],
      ]);
    }

    // Update security group.
    if ($this->entity->getSecurityGroups() != implode(self::SECURITY_GROUP_DELIMITER, $form['network']['security_groups']['#default_value'])) {

      $this->awsEc2Service->modifyInstanceAttribute([
        'InstanceId' => $this->entity->getInstanceId(),
        'Groups' => $this->getSecurityGroupIdsByNames($security_groups),
      ]);
    }

    // Update terminate protection.
    if ($this->entity->getTerminationProtection() != $form['options']['termination_protection']['#default_value']) {

      $this->awsEc2Service->modifyInstanceAttribute([
        'InstanceId' => $this->entity->getInstanceId(),
        'DisableApiTermination' => [
          'Value' => $this->entity->getTerminationProtection() == 0 ? FALSE : TRUE,
        ],
      ]);
    }

    // Update IAM role.
    $associations_result = $this->awsEc2Service->describeIamInstanceProfileAssociations([
      'Filters' => [
        [
          'Name' => 'instance-id',
          'Values' => [$this->entity->getInstanceId()],
        ],
      ],
    ]);

    $associations = $associations_result['IamInstanceProfileAssociations'];
    if (empty($associations)) {
      if ($this->entity->getIamRole() != NULL) {
        // Associate.
        $this->awsEc2Service->associateIamInstanceProfile([
          'InstanceId' => $this->entity->getInstanceId(),
          'IamInstanceProfile' => [
            'Arn' => $this->entity->getIamRole(),
          ],
        ]);
      }
    }
    else {
      if ($this->entity->getIamRole() == NULL) {
        // Disassociate.
        $this->awsEc2Service->disassociateIamInstanceProfile([
          'AssociationId' => $associations[0]['AssociationId'],
        ]);
      }
      elseif ($this->entity->getIamRole() != $associations[0]['IamInstanceProfile']['Arn']) {
        // Disassociate.
        $this->awsEc2Service->disassociateIamInstanceProfile([
          'AssociationId' => $associations[0]['AssociationId'],
        ]);

        // Associate.
        $this->awsEc2Service->associateIamInstanceProfile([
          'InstanceId' => $this->entity->getInstanceId(),
          'IamInstanceProfile' => [
            'Arn' => $this->entity->getIamRole(),
          ],
        ]);
      }
    }

    // If elastic ip is specified and instance is stopped, attach it.
    if ($this->entity->getInstanceState() == 'stopped') {
      $new_elastic_ip = $form_state->getValue('add_new_elastic_ip');
      if (isset($new_elastic_ip) && $new_elastic_ip != -1) {

        $current_allocation_id = $form_state->getValue('current_allocation_id');
        $current_association_id = $form_state->getValue('current_association_id');
        $update_entities = FALSE;

        if (isset($current_allocation_id) && isset($current_association_id)) {
          // Instance already has allocation_id. Disassociate and reassociate.
          if ($current_allocation_id != $new_elastic_ip) {
            $this->awsEc2Service->disassociateAddress([
              'AssociationId' => $current_association_id,
              'InstanceId' => $this->entity->getInstanceId(),
            ]);

            $this->awsEc2Service->associateAddress([
              'AllocationId' => $new_elastic_ip,
              'InstanceId' => $this->entity->getInstanceId(),
            ]);
            $update_entities = TRUE;
          }
        }
        else {
          // Instance does not have elastic ip. Assign a new one.
          $this->awsEc2Service->associateAddress([
            'AllocationId' => $new_elastic_ip,
            'InstanceId' => $this->entity->getInstanceId(),
          ]);
          $update_entities = TRUE;
        }
        if ($update_entities == TRUE) {
          // Update the following entities if the elastic ips have changed.
          $this->awsEc2Service->updateElasticIp();
          $this->awsEc2Service->updateInstances();
          $this->awsEc2Service->updateNetworkInterfaces();
          $this->clearCacheValues();
        }
      }
    }
  }

  /**
   * Get Instance Type Options.
   *
   * @return array
   *   Array of Instance Type Options.
   */
  private function getInstanceTypeOptions() {
    // This function gets the instance types from an EC2 endpoint.
    $instance_types = aws_cloud_get_instance_types($this->entity->getCloudContext());
    return array_combine(array_keys($instance_types), array_keys($instance_types));
  }

  /**
   * Get IAM Role Options.
   *
   * @return array
   *   Array of IAM Role Options.
   */
  private function getIamRoleOptions() {
    return aws_cloud_get_iam_roles($this->entity->getCloudContext());
  }

  /**
   * Get Security Groups Options.
   *
   * @return array
   *   Array of Security Groups Options.
   */
  private function getSecurityGroupsOptions() {
    $options = [];
    $this->awsEc2Service->setCloudContext($this->entity->get('cloud_context')->value);
    $response = $this->awsEc2Service->describeSecurityGroups(
      [
        'Filters' => [
          [
            'Name' => 'vpc-id',
            'Values' => [$this->entity->getVpcId()],
          ],
        ],
      ]);

    foreach ($response['SecurityGroups'] as $security_group) {
      $options[$security_group['GroupName']] = $security_group['GroupName'];
    }

    asort($options);
    return $options;
  }

  /**
   * Get Security Group IDs by Names.
   *
   * @param array $group_names
   *   Array of group names.
   *
   * @return array
   *   Array of group names.
   */
  private function getSecurityGroupIdsByNames(array $group_names) {
    $response = $this->awsEc2Service->describeSecurityGroups([
      'GroupNames' => $group_names,
    ]);

    $group_ids = [];
    foreach ($response['SecurityGroups'] as $security_group) {
      $group_ids[] = $security_group['GroupId'];
    }

    return $group_ids;
  }

  /**
   * Helper function to update field tags.
   *
   * @param array $form
   *   The form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  private function updateTagsField(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form_tags = $form_state->getValue('tags');
    usort($form_tags, function ($a, $b) {
      return intval($a['_weight']) - intval($b['_weight']);
    });

    $fixed_tags = [];
    $fixed_tags['Name'] = $entity->getName();
    $fixed_tags['cloud_launched_by_uid'] = $entity->getOwner() == NULL
    ? ''
    : $entity->getOwner()->id();

    $termination_timestamp = $form_state->getValue('termination_timestamp')[0]['value'];
    $fixed_tags['cloud_termination_timestamp'] = $termination_timestamp == NULL
    ? ''
    : $termination_timestamp->getTimestamp();

    $config = \Drupal::config('aws_cloud.settings');
    if ($config->get('aws_cloud_scheduler') == TRUE) {
      $fixed_tags['aws_cloud_scheduler_tag'] = $this->getSchedule();
    }

    $tags = [];
    foreach ($form_tags as $form_tag) {
      $tag_key = $form_tag['tag_key'];
      if ($tag_key === '') {
        continue;
      }

      // Skip special tags.
      if (strpos($tag_key, 'aws:') === 0) {
        continue;
      }

      $tag_value = $form_tag['tag_value'];
      if (isset($fixed_tags[$tag_key])) {
        $tag_value = $fixed_tags[$tag_key];
        unset($fixed_tags[$tag_key]);
      }

      $tags[] = ['tag_key' => $tag_key, 'tag_value' => $tag_value];
    }

    foreach ($fixed_tags as $tag_key => $tag_value) {
      $tags[] = ['tag_key' => $tag_key, 'tag_value' => $tag_value];
    }

    $entity->setTags($tags);
    $entity->save();
  }

  /**
   * Helper function to update AWS tags.
   */
  private function updateAwsTags() {
    $params = [
      'Resources' => [$this->entity->getInstanceId()],
    ];

    // Delete old tags.
    $this->awsEc2Service->deleteTags($params);

    foreach ($this->entity->getTags() as $tag) {
      $params['Tags'][] = [
        'Key' => $tag['tag_key'],
        'Value' => $tag['tag_value'],
      ];
    }

    // Create new tags.
    $this->awsEc2Service->createTags($params);
  }

  /**
   * Helper function to build elastic ip dropdown.
   */
  private function getAvailableElasticIps() {
    $ips[-1] = $this->t('Select an Elastic IP');

    $available_ips = $this->getAvailableElasticIpCount();

    foreach ($available_ips as $ip) {
      $elastic_ip = ElasticIp::load($ip);
      $ips[$elastic_ip->getAllocationId()] = $this->t('@name (@ip)', [
        '@name' => $elastic_ip->getName(),
        '@ip' => $elastic_ip->getPublicIp(),
      ]);
    }
    return $ips;
  }

  /**
   * Helper function to query db for available elastic ips.
   */
  private function getAvailableElasticIpCount() {
    return $this->entityTypeManager->getStorage('aws_cloud_elastic_ip')
      ->getQuery()
      ->condition('cloud_context', $this->entity->getCloudContext())
      ->notExists('association_id')
      ->execute();
  }

  /**
   * Helper to look up an elastic ip row given the elastic ip address.
   *
   * @param string $ip
   *   Ip address used to look up the row.
   *
   * @return bool|mixed
   *   FALSE if no row found or the ElasticIp entity.
   */
  private function getElasticIpByIpLookup($ip) {
    $elastic_ip = FALSE;
    $result = $this->entityTypeManager->getStorage('aws_cloud_elastic_ip')
      ->loadByProperties([
        'cloud_context' => $this->entity->getCloudContext(),
        'public_ip' => $ip,
      ]);
    if (count($result) == 1) {
      $elastic_ip = array_shift($result);
    }
    return $elastic_ip;
  }

  /**
   * Helper function to get network interfaces for an instance.
   */
  private function getNetworkInterfaceCount() {
    return $this->entityTypeManager->getStorage('aws_cloud_network_interface')
      ->loadByProperties([
        'instance_id' => $this->entity->getInstanceId(),
      ]);
  }

}
