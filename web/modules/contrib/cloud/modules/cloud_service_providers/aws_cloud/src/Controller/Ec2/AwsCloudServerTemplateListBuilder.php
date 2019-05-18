<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\cloud\Service\EntityLinkRendererInterface;
use Drupal\cloud\Controller\CloudServerTemplateListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for CloudServerTemplate entity.
 *
 * @ingroup cloud_server_template
 */
class AwsCloudServerTemplateListBuilder extends CloudServerTemplateListBuilder {

  /**
   * Entity link renderer object.
   *
   * @var \Drupal\cloud\Service\EntityLinkRendererInterface
   */
  protected $entityLinkRenderer;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('entity.link_renderer')
    );
  }

  /**
   * Constructs a new Ec2CloudServerTemplateListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\cloud\Service\EntityLinkRendererInterface $entity_link_renderer
   *   The entity link render service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    RouteMatchInterface $route_match,
    AccountProxyInterface $current_user,
    EntityLinkRendererInterface $entity_link_renderer
  ) {
    parent::__construct($entity_type, $storage, $route_match, $current_user);

    $this->entityLinkRenderer = $entity_link_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $parent_header = parent::buildHeader();
    $header = [
      // The header gives the table the information it needs in order to make
      // the query calls for ordering. TableSort uses the field information
      // to know what database column to sort by.
      $parent_header[0],
      [
        'data' => t('AMI Name'),
        'specifier' => 'field_image_id',
        'field' => 'field_image_id',
      ],
      [
        'data' => t('Instance Type'),
        'specifier' => 'field_instance_type',
        'field' => 'field_instance_type',
      ],
      [
        'data' => t('Security Group'),
        'specifier' => 'field_security_group',
        'field' => 'field_security_group',
      ],
      [
        'data' => t('Key Pair'),
        'specifier' => 'field_ssh_key',
        'field' => 'field_ssh_key',
      ],
      [
        'data' => t('VPC'),
        'specifier' => 'field_vpc',
        'field' => 'field_vpc',
      ],
      [
        'data' => t('Max Count'),
        'specifier' => 'field_max_count',
        'field' => 'field_max_count',
      ],
    ];
    $header['operations'] = $parent_header['operations'];
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $parent_row = parent::buildRow($entity);

    $row[] = $parent_row['name'];

    // AMI image.
    $image = $entity->get('field_image_id')->entity;
    if ($image != NULL) {
      $row[] = [
        'data' => $this->entityLinkRenderer->renderViewElement(
          $image->getImageId(),
          'aws_cloud_image',
          'image_id',
          [],
          $image->getName()
        ),
      ];
    }
    else {
      $row[] = '';
    }

    // Instance type.
    $row[] = $entity->get('field_instance_type')->value;

    // Security groups.
    $htmls = [];
    foreach ($entity->get('field_security_group') as $group) {
      if ($group->entity != NULL) {
        $group_id = $group->entity->getGroupId();
        $element = $this->entityLinkRenderer->renderViewElement(
          $group_id,
          'aws_cloud_security_group',
          'group_id',
          [],
          $group->entity->getName()
        );

        $htmls[] = $element['#markup'];
      }
      else {
        $htmls[] = '';
      }
    }
    $row[] = [
      'data' => ['#markup' => implode(', ', $htmls)],
    ];

    // SSH key.
    if ($entity->get('field_ssh_key')->entity != NULL) {
      $row[] = [
        'data' => $this->entityLinkRenderer->renderViewElement(
          $entity->get('field_ssh_key')->entity->getKeyPairName(),
          'aws_cloud_key_pair',
          'key_pair_name'
        ),
      ];
    }
    else {
      $row[] = '';
    }

    $row[] = $entity->get('field_vpc')->value;
    $row[] = $entity->get('field_max_count')->value;
    $row[] = $parent_row['operations'];

    return $row;
  }

}
