<?php

namespace Drupal\flag_attendance_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'attendance_default' widget.
 *
 * @FieldWidget(
 *   id = "attendance_default",
 *   label = @Translation("Attendance widget"),
 *   field_types = {
 *     "flag_attendance"
 *   },
 * )
 */
class FlagAttendanceWidget extends WidgetBase implements WidgetInterface, ContainerFactoryPluginInterface {

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
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routematch
   *   The current user.
   * @param \Drupal\flag\FlagServiceInterface $flag
   *   The renderer service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, RouteMatchInterface $routematch, FlagServiceInterface $flag) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

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
      $configuration['third_party_settings'],
      $container->get('current_route_match'),
      $container->get('flag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get default value.
    $default_value = unserialize($items[$delta]->value);

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
      'date' => $this->t('Date'),
    ];
    foreach ($users as $value) {
      $header[$value->getUsername()] = $value->getUsername();
    }

    // Create table.
    $element['table'] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    // Fill attendance table.
    $i = 0;
    foreach ($default_value as $value) {
      foreach ($value as $attendance_key => $attendance_value) {
        if ($attendance_key == 'date') {
          $element['table'][$i][$attendance_key] = [
            '#type' => 'date',
            '#default_value' => $attendance_value,
          ];
        }
        else {
          $element['table'][$i][$attendance_key] = [
            '#type' => 'checkbox',
            '#default_value' => $attendance_value,
          ];
        }
      }
      $i++;
    }

    $element['table'][$i]['date'] = [
      '#type' => 'date',
      '#default_value' => 0,
    ];
    foreach ($users as $value) {
      $element['table'][$i][$value->getUsername()] = [
        '#type' => 'checkbox',
        '#default_value' => 0,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Serialize data before save in database.
    parent::massageFormValues($values, $form, $form_state);
    foreach ($values[0]['table'] as $key => $value) {
      if (empty($value['date'])) {
        unset($values[0]['table'][$key]);
      }
    }
    $serialized = serialize($values[0]['table']);
    $values[0]['value'] = $serialized;
    return $values;
  }

}
