<?php

namespace Drupal\aws_cloud\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ip_permission_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ip_permission_formatter",
 *   label = @Translation("IpPermission formatter"),
 *   field_types = {
 *     "ip_permission"
 *   }
 * )
 */
class IpPermissionFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * Constructs a new TimestampFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format storage.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A configuration factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage, RouteMatchInterface $route_match) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $date_formatter, $date_format_storage);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('date_format'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $rows = [];

    $security_group = $this->getSecurityGroupEntity();
    foreach ($items as $delta => $item) {
      /* @var \Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission $item */
      if (!$item->isEmpty()) {
        $revoke_link = Link::createFromRoute($this->t('Revoke'), 'entity.aws_cloud_security_group.revoke_form', [
          'cloud_context' => $security_group->getCloudContext(),
          'aws_cloud_security_group' => $security_group->id(),
          'type' => $items->getName(),
          'position' => $delta,
        ]);
        $rows[] = [
          ($item->ip_protocol == "-1") ? $this->t('All Traffic') : $item->ip_protocol,
          $item->from_port,
          $item->to_port,
          $item->cidr_ip,
          $item->cidr_ip_v6,
          $item->prefix_list_id,
          $item->group_id,
          $item->group_name,
          $item->peering_status,
          $item->user_id,
          $item->vpc_id,
          $item->peering_connection_id,
          $revoke_link,
        ];
      }
    }

    if (count($rows)) {
      $elements[0] = [
        '#theme' => 'table',
        '#header' => [
          $this->t('IP Protocol'),
          $this->t('From Port'),
          $this->t('To Port'),
          $this->t('CIDR IP'),
          $this->t('CIDR IP V6'),
          $this->t('Prefix List Id'),
          $this->t('Group Id'),
          $this->t('Group Name'),
          $this->t('Peering Status'),
          $this->t('Group User Id'),
          $this->t('VPC ID'),
          $this->t('Peering Connection Id'),
          $this->t('Operation'),
        ],
        '#rows' => $rows,
      ];
    }

    return $elements;
  }

  /**
   * Helper function to retrieve the Security Group from the route.
   */
  private function getSecurityGroupEntity() {
    $security_group = FALSE;
    foreach ($this->routeMatch->getParameters() as $param) {
      if ($param instanceof EntityInterface) {
        $security_group = $param;
      }
    }
    return $security_group;
  }

}
