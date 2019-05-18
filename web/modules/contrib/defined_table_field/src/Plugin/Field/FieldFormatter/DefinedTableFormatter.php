<?php

namespace Drupal\defined_table\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Plugin implementation of the default defined_table formatter.
 *
 * @FieldFormatter (
 *   id = "defined_table",
 *   label = @Translation("Table"),
 *   field_types = {
 *     "defined_table"
 *   }
 * )
 */
class DefinedTableFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {

    $field = $items->getFieldDefinition();
    $field_name = $field->getName();
    $field_settings = $field->getSettings();

    $elements = [];

    foreach ($items as $delta => $item) {

      if (!empty($item->values)) {

        $elements[$delta]['defined_table'] = [
          '#type' => 'table',
          '#header' => [],
          '#rows' => [],
        ];

        // Convert dynamic values to field settings.
        foreach (['header', 'arguments'] as $axis) {
          if ($field_settings[$axis]['type'] === 'dynamic') {
            $field_settings[$axis] = $item->{$axis};
          }
        }

        $axes_data = [];
        foreach (['header', 'arguments'] as $axis) {
          switch ($field_settings[$axis]['type']) {
            case 'values':
              $values = explode(',', $field_settings[$axis]['data']);
              foreach ($values as $index => $value) {
                $axes_data[$axis][$index + 1] = trim($value);
              }
              break;

            case 'taxonomy':
              if ($terms = $this->termStorage->loadTree($field_settings[$axis]['data'], 0, 1, FALSE)) {
                foreach ($terms as $term) {
                  $axes_data[$axis][$term->tid] = $term->name;
                }
              }
              else {
                drupal_set_message($this->t("Vocabulary %vocaulary doesn't exist. Please check field settings.", ['%vocaulary' => $field_settings[$axis]['data']]), 'error');
              }
              break;

          }
        }

        $elements[$delta]['defined_table']['#header'][] = isset($field_settings['arguments']['title']) ? $field_settings['arguments']['title'] : '';
        foreach ($axes_data['header'] as $cell) {
          $elements[$delta]['defined_table']['#header'][] = $cell;
        }

        $values = [];
        foreach ($axes_data['arguments'] as $row_key => $argument) {
          $values[$row_key] = [
            [
              'data' => $argument,
              'class' => ['label'],
            ],
          ];
          if (isset($item->values[$row_key])) {
            foreach ($item->values[$row_key] as $value) {
              // If type is checkbox then replace value with label.
              if ($field_settings['input_type'] == 'checkbox') {
                $value = $value ? $field_settings['on_label'] : $field_settings['off_label'];
              }
              $values[$row_key][] = $value;
            }
          }
        }

        foreach ($values as $i => $row) {
          foreach ($row as $j => $cell) {
            $elements[$delta]['defined_table']['#rows'][$i][$j] = $cell;
          }
        }
      }
    }
    return $elements;
  }

}
