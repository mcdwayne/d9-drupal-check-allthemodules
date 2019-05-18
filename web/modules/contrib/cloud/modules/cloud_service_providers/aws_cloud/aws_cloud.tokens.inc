<?php

/**
 * @file
 * Builds placeholder replacement tokens for aws-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function aws_cloud_token_info() {
  $types = [
    'name' => t('AWS Cloud Instances'),
    'description' => t('Tokens related to individual aws cloud instances.'),
    'needs-data' => 'aws_cloud_instance',
  ];
  $instance['name'] = [
    'name' => t('Instance Name'),
    'description' => t('The name of the Instance entity'),
  ];
  $instance['id'] = [
    'name' => t('Instance ID'),
    'description' => t('AWS Instance ID'),
  ];
  $instance['launch_time'] = [
    'name' => t('Launch Time'),
    'description' => t('The time the instance launched'),
  ];
  $instance['instance_state'] = [
    'name' => t('Instance State'),
    'description' => t('The current state of the instance'),
  ];
  $instance['availability_zone'] = [
    'name' => t('Availability Zone'),
    'description' => t('Zone the instance is in'),
  ];
  $instance['private_ip'] = [
    'name' => t('Private IP Address'),
    'description' => t('The instance private ip'),
  ];
  $instance['public_ip'] = [
    'name' => t('Public IP Address'),
    'description' => t('The instance public ip'),
  ];
  $instance['elastic_ip'] = [
    'name' => t('Elastic IP Address'),
    'description' => t('The instance elastic ip address'),
  ];
  $instance['instance_link'] = [
    'name' => t('Instance Link'),
    'description' => t('Absolute link to the instance'),
  ];
  $instance['instance_edit_link'] = [
    'name' => t('Edit Instance Link'),
    'description' => t('Absolute link to edit the instance'),
  ];

  $volume['name'] = [
    'name' => t('Volume Name'),
    'description' => t('The name of the volume entity'),
  ];
  $volume['volume_link'] = [
    'name' => t('Volume Link'),
    'description' => t('Absolute link to volume'),
  ];
  $volume['volume_edit_link'] = [
    'name' => t('Volume Edit Link'),
    'description' => t('Absolute link to edit the volume'),
  ];
  $volume['created'] = [
    'name' => t('Create date'),
    'description' => t('The volume create date'),
  ];

  $snapshot['name'] = [
    'name' => t('Snapshot Name'),
    'description' => t('The name of the snapshot entity'),
  ];
  $volume['snapshot_link'] = [
    'name' => t('Snapshot Link'),
    'description' => t('Absolute link to snapshot'),
  ];
  $volume['volume_snapshot_link'] = [
    'name' => t('Snapshot Edit Link'),
    'description' => t('Absolute link to edit the snapshot'),
  ];
  $volume['created'] = [
    'name' => t('Create date'),
    'description' => t('The snapshot create date'),
  ];

  $instance_email['instances'] = [
    'name' => t('List of instances'),
    'description' => t('List of instances to display to user.'),
  ];

  $volume_email['volumes'] = [
    'name' => t('List of volumes'),
    'description' => t('List of volumes to display to user.'),
  ];

  $snapshot_email['snapshots'] = [
    'name' => t('List of snapshots'),
    'description' => t('List of snapshots to display to user.'),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'aws_cloud_instance' => $instance,
      'aws_cloud_volume' => $volume,
      'aws_cloud_snapshot' => $snapshot,
      'aws_cloud_instance_email' => $instance_email,
      'aws_cloud_volume_email' => $volume_email,
      'aws_cloud_snapshot_email' => $snapshot_email,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function aws_cloud_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];
  if ($type == 'aws_cloud_instance' && !empty($data['aws_cloud_instance'])) {
    $replacements = aws_cloud_instance_tokens($tokens, $data);
  }
  elseif ($type == 'aws_cloud_volume' && !empty($data['aws_cloud_volume'])) {
    $replacements = aws_cloud_volume_tokens($tokens, $data);
  }
  elseif ($type == 'aws_cloud_snapshot' && !empty($data['aws_cloud_snapshot'])) {
    $replacements = aws_cloud_snapshot_tokens($tokens, $data);
  }
  elseif ($type == 'aws_cloud_instance_email') {
    $replacements = aws_cloud_instance_email_tokens($tokens, $data);
  }
  elseif ($type == 'aws_cloud_volume_email') {
    $replacements = aws_cloud_volume_email_tokens($tokens, $data);
  }
  elseif ($type == 'aws_cloud_snapshot_email') {
    $replacements = aws_cloud_snapshot_email_tokens($tokens, $data);
  }
  return $replacements;
}

/**
 * Setup instance email token substitution.
 *
 * @param array $tokens
 *   Tokens array.
 * @param array $data
 *   Data array.
 *
 * @return array
 *   The initialized replacement array.
 */
function aws_cloud_instance_email_tokens(array $tokens, array $data) {
  $replacements = [];
  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'instances':
        $replacements[$original] = $data['instances'];
        break;
    }
  }
  return $replacements;
}

/**
 * Setup volume email token substitution.
 *
 * @param array $tokens
 *   Tokens array.
 * @param array $data
 *   Data array.
 *
 * @return array
 *   The initialized replacement array.
 */
function aws_cloud_volume_email_tokens(array $tokens, array $data) {
  $replacements = [];
  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'volumes':
        $replacements[$original] = $data['volumes'];
        break;
    }
  }
  return $replacements;
}

/**
 * Setup snapshot email token substitution.
 *
 * @param array $tokens
 *   Tokens array.
 * @param array $data
 *   Data array.
 *
 * @return array
 *   The initialized replacement array.
 */
function aws_cloud_snapshot_email_tokens(array $tokens, array $data) {
  $replacements = [];
  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'snapshots':
        $replacements[$original] = $data['snapshots'];
        break;
    }
  }
  return $replacements;
}

/**
 * Setup volume token substitution.
 *
 * @param array $tokens
 *   Tokens array.
 * @param array $data
 *   Data array.
 *
 * @return array
 *   The initialized replacement array.
 */
function aws_cloud_volume_tokens(array $tokens, array $data) {
  $replacements = [];
  /* @var \Drupal\aws_cloud\Entity\Ec2\Volume $volume */
  $volume = $data['aws_cloud_volume'];
  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'name':
        $replacements[$original] = $volume->getName();
        break;

      case 'volume_link':
        $replacements[$original] = $volume->toUrl('canonical', ['absolute' => TRUE])->toString();
        break;

      case 'volume_link_edit':
        $replacements[$original] = $volume->toUrl('edit-form', ['absolute' => TRUE])
          ->toString();
        break;

      case 'created':
        $replacements[$original] = \Drupal::service('date.formatter')->format($volume->created->value, 'custom', 'Y-m-d H:i:s O');
        break;
    }
  }
  return $replacements;
}

/**
 * Setup snapshot token substitution.
 *
 * @param array $tokens
 *   Tokens array.
 * @param array $data
 *   Data array.
 *
 * @return array
 *   The initialized replacement array.
 */
function aws_cloud_snapshot_tokens(array $tokens, array $data) {
  $replacements = [];
  /* @var \Drupal\aws_cloud\Entity\Ec2\Snapshot $snapshot */
  $snapshot = $data['aws_cloud_snapshot'];
  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'name':
        $replacements[$original] = $snapshot->getName();
        break;

      case 'snapshot_link':
        $replacements[$original] = $snapshot->toUrl('canonical', ['absolute' => TRUE])->toString();
        break;

      case 'snapshot_link_edit':
        $replacements[$original] = $snapshot->toUrl('edit-form', ['absolute' => TRUE])
          ->toString();
        break;

      case 'created':
        $replacements[$original] = \Drupal::service('date.formatter')->format($snapshot->created->value, 'custom', 'Y-m-d H:i:s O');
        break;
    }
  }
  return $replacements;
}

/**
 * Setup instance token substitution.
 *
 * @param array $tokens
 *   Tokens array.
 * @param array $data
 *   Data array.
 *
 * @return array
 *   The initialized replacement array.
 */
function aws_cloud_instance_tokens(array $tokens, array $data) {
  $replacements = [];
  /** @var \Drupal\aws_cloud\Entity\Ec2\Instance $instance */
  $instance = $data['aws_cloud_instance'];

  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'name':
        $replacements[$original] = $instance->getName();
        break;

      case 'id':
        $replacements[$original] = $instance->getInstanceId();
        break;

      case 'launch_time':
        $replacements[$original] = \Drupal::service('date.formatter')
          ->format($instance->getLaunchTime(), 'custom', 'Y-m-d H:i:s O');
        break;

      case 'instance_state':
        $replacements[$original] = $instance->getInstanceState();
        break;

      case 'availability_zone':
        $replacements[$original] = $instance->getAvailabilityZone();
        break;

      case 'private_ip':
        $replacements[$original] = $instance->getPrivateIps();
        break;

      case 'public_ip':
        $replacements[$original] = $instance->getPublicIp();
        break;

      case 'elastic_ip':
        $replacements[$original] = $instance->getElasticIp();
        break;

      case 'instance_link':
        $replacements[$original] = $instance->toUrl('canonical', ['absolute' => TRUE])
          ->toString();
        break;

      case 'instance_edit_link':
        $replacements[$original] = $instance->toUrl('edit-form', ['absolute' => TRUE])
          ->toString();
        break;

    }
  }
  return $replacements;
}
