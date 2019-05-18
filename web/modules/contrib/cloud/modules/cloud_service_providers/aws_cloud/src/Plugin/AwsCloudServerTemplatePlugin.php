<?php

namespace Drupal\aws_cloud\Plugin;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\aws_cloud\Service\AwsEc2ServiceInterface;
use Drupal\cloud\Entity\CloudServerTemplateInterface;
use Drupal\cloud\Plugin\CloudServerTemplatePluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AWS Cloud Server Template Plugin.
 */
class AwsCloudServerTemplatePlugin extends PluginBase implements CloudServerTemplatePluginInterface, ContainerFactoryPluginInterface {


  /**
   * The AWS EC2 Service.
   *
   * @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface
   */
  protected $awsEc2Service;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * AwsCloudServerTemplatePlugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\aws_cloud\Service\AwsEc2ServiceInterface $aws_ec2_service
   *   The AWS EC2 Service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The Messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The uuid service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AwsEc2ServiceInterface $aws_ec2_service,
    Messenger $messenger,
    EntityTypeManagerInterface $entity_type_manager,
    UuidInterface $uuid_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->awsEc2Service = $aws_ec2_service;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('aws_cloud.ec2'),
      $container->get('messenger'),
      $container->get('entity_type.manager'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundleName() {
    return $this->pluginDefinition['entity_bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function launch(CloudServerTemplateInterface $cloud_server_template, FormStateInterface $form_state = NULL) {

    $params = [];
    $params['DryRun'] = $cloud_server_template->get('field_test_only')->value == "0" ? FALSE : TRUE;
    $params['ImageId'] = $cloud_server_template->get('field_image_id')->entity->get('image_id')->value;
    $params['MaxCount'] = $cloud_server_template->get('field_max_count')->value;
    $params['MinCount'] = $cloud_server_template->get('field_min_count')->value;
    $params['Monitoring']['Enabled'] = $cloud_server_template->get('field_monitoring')->value == "0" ? FALSE : TRUE;
    $params['InstanceType'] = $cloud_server_template->get('field_instance_type')->value;
    if (isset($cloud_server_template->get('field_ssh_key')->entity)) {
      $params['KeyName'] = $cloud_server_template->get('field_ssh_key')->entity->get('key_pair_name')->value;
    }

    if ($cloud_server_template->get('field_image_id')->entity->get('root_device_type') == 'ebs') {
      $params['InstanceInitiatedShutdownBehavior'] = $cloud_server_template->get('field_instance_shutdown_behavior')->value;
    }

    // Setup optional parameters.
    if (isset($cloud_server_template->get('field_kernel_id')->value)) {
      $params['KernelId'] = $cloud_server_template->get('field_kernel_id')->value;
    }
    if (isset($cloud_server_template->get('field_ram')->value)) {
      $params['RamdiskId'] = $cloud_server_template->get('field_ram')->value;
    }
    if (isset($cloud_server_template->get('field_user_data')->value)) {
      $params['UserData'] = base64_encode($cloud_server_template->get('field_user_data')->value);
    }
    if (isset($cloud_server_template->get('field_availability_zone')->value)) {
      $params['Placement']['AvailabilityZone'] = $cloud_server_template->get('field_availability_zone')->value;
    }

    $vpc_id = NULL;
    if ($cloud_server_template->get('field_subnet')->value != NULL) {
      $params['SubnetId'] = $cloud_server_template->get('field_subnet')->value;
      $vpc_id = $cloud_server_template->get('field_vpc')->value;
    }

    $params['SecurityGroupIds'] = [];
    foreach ($cloud_server_template->get('field_security_group') as $group) {
      if (isset($group->entity)
      && $vpc_id != NULL
      && $vpc_id == $group->entity->getVpcId()) {
        $params['SecurityGroupIds'][] = $group->entity->getGroupId();
      }
    }

    if (empty($params['SecurityGroupIds'])) {
      unset($params['SecurityGroupIds']);
    }

    if (isset($cloud_server_template->get('field_network')->entity)) {
      $params['NetworkInterfaces'] = [
        ['NetworkId' => $cloud_server_template->get('field_network')->entity->getNetworkInterfaceId()],
      ];
    }

    $iam_role = NULL;
    $cloud_server_template->get('field_iam_role')->value;
    if ($iam_role != NULL) {
      $params['IamInstanceProfile'] = ['Arn' => $iam_role];
    }

    $this->awsEc2Service->setCloudContext($cloud_server_template->getCloudContext());

    $tags_map = [];

    // Add tags from the template.
    foreach ($cloud_server_template->get('field_tags') as $tag_item) {
      $tags_map[$tag_item->getTagKey()] = $tag_item->getTagValue();
    }

    if ($form_state->getValue('termination_protection')) {
      $params['DisableApiTermination'] = $form_state->getValue('termination_protection') == "0" ? FALSE : TRUE;
    }
    else {
      // If the user checks the auto termination option
      // add it as a tag to AWS Ec2.
      if ($form_state->getValue('terminate')) {
        /* @var \Drupal\Core\Datetime\DrupalDateTime $timestamp */
        $timestamp = $form_state->getValue('termination_date');
        $tags_map['cloud_termination_timestamp'] = $timestamp->getTimeStamp();
      }
    }

    if (!empty($form_state->getValue('schedule'))) {
      // Send the schedule if scheduler is enabled.
      $config = \Drupal::config('aws_cloud.settings');
      $tags_map[$config->get('aws_cloud_scheduler_tag')]
        = $form_state->getValue('schedule');
    }

    $tags_map['Name'] = $cloud_server_template->getName();
    if ($params['MaxCount'] > 1) {
      $cloud_launch_uuid = $this->uuidService->generate();
      $tags_map['Name'] .= $cloud_launch_uuid;
    }

    $tags = [];
    foreach ($tags_map as $tag_key => $tag_value) {
      $tags[] = [
        'Key' => $tag_key,
        'Value' => $tag_value,
      ];
    }

    if ($this->awsEc2Service->runInstances($params, $tags) != NULL) {
      // Update instances after launch.
      $this->awsEc2Service->updateInstances();
      if ($params['MaxCount'] > 1) {
        $this->updateInstanceName($cloud_server_template, $cloud_launch_uuid);
      }
      $this->messenger->addStatus('Instance launched.');
      $return_route = [
        'route_name' => 'view.aws_instances.page_1',
        'params' => ['cloud_context' => $cloud_server_template->getCloudContext()],
      ];
    }
    else {
      $return_route = [
        'route_name' => 'entity.cloud_server_template.canonical',
        'params' => ['cloud_server_template' => $cloud_server_template->id(), 'cloud_context' => $cloud_server_template->getCloudContext()],
      ];
    }

    return $return_route;
  }

  /**
   * Update instance name based on the name of the cloud server template.
   *
   * If the same instance name exists, the number suffix (#2, #3…) can be
   * added at the end of the cloud server template name.
   *
   * @param \Drupal\cloud_server_template\Entity\CloudServerTemplateInterface $cloud_server_template
   *   The cloud server template used to launch a instance.
   * @param string $cloud_launch_uuid
   *   The uuid to specify instances.
   */
  private function updateInstanceName(
    CloudServerTemplateInterface $cloud_server_template,
    $cloud_launch_uuid
  ) {
    $template_name = $cloud_server_template->getName();
    $cloud_context = $cloud_server_template->getCloudContext();

    $instance_storage = $this->entityTypeManager->getStorage('aws_cloud_instance');
    $instance_ids = $instance_storage
      ->getQuery()
      ->condition('name', $template_name . $cloud_launch_uuid)
      ->condition('cloud_context', $cloud_context)
      ->execute();

    $instances = $instance_storage->loadMultiple($instance_ids);
    $count = 1;
    $prefix = $this->getInstanceNamePrefix($template_name, $cloud_context);
    foreach ($instances as $instance) {
      $name = $prefix . $count++;
      $params = [
        'Resources' => [$instance->getInstanceId()],
      ];
      $params['Tags'][] = [
        'Key' => 'Name',
        'Value' => $name,
      ];
      $this->awsEc2Service->createTags($params);
    }

    if (count($instances) > 0) {
      $this->awsEc2Service->updateInstances();
    }
  }

  /**
   * Get the prefix of instance name.
   *
   * The prefix will be something like below.
   * 1. 1st Launch:
   *   Cloud Orchestrator #1, Cloud Orchestrator #2.
   * 2. 2nd Launch:
   *   Cloud Orchestrator #2-1, Cloud Orchestrator #2-2.
   * 2. 3nd Launch:
   *   Cloud Orchestrator #3-1, Cloud Orchestrator #3-2.
   *
   * @param string $template_name
   *   The template name.
   * @param string $cloud_context
   *   The cloud context.
   *
   * @return string
   *   The prefix of instance name.
   */
  private function getInstanceNamePrefix($template_name, $cloud_context) {
    $instance_storage = $this->entityTypeManager->getStorage('aws_cloud_instance');
    $instance_ids = $instance_storage
      ->getQuery()
      ->condition('name', "$template_name #%", 'like')
      ->condition('cloud_context', $cloud_context)
      ->execute();

    $instances = $instance_storage->loadMultiple($instance_ids);

    $instance_names = array_map(function ($instance) {
      return $instance->getName();
    }, $instances);

    $prefix = "$template_name #";
    if (array_search($prefix . '1', $instance_names) === FALSE) {
      return $prefix;
    }

    $index = 2;
    $prefix = "$template_name #$index-";
    while (array_search($prefix . '1', $instance_names) !== FALSE) {
      $index++;
      $prefix = "$template_name #$index-";
    }

    return $prefix;
  }

}
