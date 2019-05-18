<?php

namespace Drupal\flag_attendance_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'attendance_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "attendance_formatter",
 *   label = @Translation("Attendance formatter"),
 *   field_types = {
 *     "flag_attendance"
 *   }
 * )
 */
class FlagAttendanceFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The routeMatch object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routematch;

  /**
   * The flag object.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flag;

  /**
   * Constructs an WidgetBase object.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routematch
   *   The current user.
   * @param \Drupal\flag\FlagServiceInterface $flag
   *   The renderer service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RouteMatchInterface $routematch, FlagServiceInterface $flag) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->routematch = $routematch;
    $this->flag = $flag;
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
      $container->get('current_route_match'),
      $container->get('flag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Display attendance for all users on this content.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    // Get the default value.
    $default_value = unserialize($items->value);
    // Get current entity.
    foreach ($this->routematch->getParameters() as $param) {
      if ($param instanceof EntityInterface) {
        $entities[] = $param;
      }
    }
    // Load flag.
    $flag = $this->flag->getFlagById($items->getSettings('flag')['flag']);
    // Get flagging users.
    $users = $this->flag->getFlaggingUsers($entities[0], $flag);

    // Create table header.
    $header = [
      'user' => $this->t('User'),
    ];
    foreach ($default_value as $value) {
      $header[$value['date']] = $value['date'];
    }

    // Create table.
    $element[0]['table'] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    // Fill attendance table.
    $i = 0;
    foreach ($users as $users_value) {
      $element[0]['table'][$i]['user'] = [
        '#type' => 'item',
        '#title' => $users_value->getUsername(),
      ];
      foreach ($default_value as $attendance_value) {
        $element[0]['table'][$i][$attendance_value['date']] = [
          '#type' => 'item',
          '#title' => $attendance_value[$users_value->getUsername()],
        ];
      }
      $i++;
    }

    return $element;
  }

}
