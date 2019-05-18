<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the security group view builders.
 */
class SecurityGroupViewBuilder extends Ec2BaseViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsetDefs() {
    return [
      [
        'name' => 'security_group',
        'title' => t('Security Group'),
        'open' => TRUE,
        'fields' => [
          'group_id',
          'group_name',
          'description',
          'vpc_id',
        ],
      ],
      [
        'name' => 'rules',
        'title' => t('Rules'),
        'open' => FALSE,
        'fields' => [
          'ip_permission',
          'outbound_permission',
        ],
      ],
      [
        'name' => 'others',
        'title' => t('Others'),
        'open' => FALSE,
        'fields' => [
          'cloud_context',
          'uid',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);
    $build['#pre_render'][] = [$this, 'removeIpPermissionsField'];
    return $build;
  }

  /**
   * Show a default message if not permissions are configured.
   *
   * @param array $build
   *   Build array.
   *
   * @return array
   *   The updated renderable array.
   */
  public function removeIpPermissionsField(array $build) {
    /* @var \Drupal\aws_cloud\Entity\Ec2\SecurityGroup $security */
    $security =& $build['rules'][0]['#aws_cloud_security_group'];

    $inbound = $security->getIpPermission();
    $outbound = $security->getOutboundPermission();
    if ($inbound->count() == 0 && $outbound->count() == 0) {
      unset($build['rules'][0]);
      $build['rules'][] = [
        '#markup' => $this->t('No permissions configured'),
      ];
    }
    return $build;
  }

}
