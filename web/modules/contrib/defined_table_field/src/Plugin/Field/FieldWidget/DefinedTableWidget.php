<?php

namespace Drupal\defined_table\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\defined_table\Plugin\Field\DefinedTableSourceSelectionTrait;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin implementation of the 'defined_table' widget.
 *
 * @FieldWidget (
 *   id = "defined_table",
 *   label = @Translation("Table input"),
 *   field_types = {
 *     "defined_table"
 *   },
 * )
 */
class DefinedTableWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  use DefinedTableSourceSelectionTrait;

  /**
   * Taxonomy term storage object.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $field = $items[0]->getFieldDefinition();
    $field_settings = $field->getSettings();

    $axes = [
      'header' => $this->t('Header'),
      'arguments' => $this->t('Argument labels'),
    ];

    // Check if dynamic settings are selected.
    $axes_selectors = [];
    foreach ($axes as $axis => $label) {
      if ($field_settings[$axis]['type'] == 'dynamic') {
        $axes_selectors[] = $axis;
      }
    }

    // Get settings for data axes.
    if (!empty($axes_selectors)) {
      $selector_parents = $element['#field_parents'];
      $selector_parents[] = $field->getName();
      $selector_parents[] = $delta;
      $selector_parents[] = 'axes_settings';
      $selector_settings = $form_state->getValue($selector_parents);
      // Fallback to item values.
      if (empty($selector_settings)) {
        $selector_settings = [];
        foreach ($axes_selectors as $axis) {
          $selector_settings[$axis] = $items[$delta]->{$axis};
        }
      }
    }

    $table = [
      '#type' => 'defined_table',
      '#arguments_title' => isset($field_settings['arguments']['title']) ? $field_settings['arguments']['title'] : '',
      '#header' => [],
      '#arguments' => [],
      '#input_type' => $field_settings['input_type'],
      '#default_value' => isset($items[$delta]->values) ? $items[$delta]->values : [],
    ];

    // Get header and argument labels data.
    foreach ($axes as $axis => $label) {
      if (isset($selector_settings[$axis])) {
        $axis_settings = $selector_settings[$axis];
      }
      else {
        $axis_settings = $field_settings[$axis];
      }

      switch ($axis_settings['type']) {
        case 'values':
          $values = explode(',', $axis_settings['data']);
          foreach ($values as $index => $value) {
            $table['#' . $axis][$index + 1] = trim($value);
          }
          break;

        case 'taxonomy':
          if ($terms = $this->termStorage->loadTree($axis_settings['data'], 0, 1, FALSE)) {
            foreach ($terms as $term) {
              $table['#' . $axis][$term->tid] = $term->name;
            }
          }
          break;
      }
    }

    // Build dynamic selector if required.
    if (!empty($axes_selectors)) {
      $element['axes_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Labels settings'),
      ];
      foreach ($axes_selectors as $axis) {
        if (empty($selector_settings[$axis])) {
          $selector_settings[$axis] = [
            'type' => 'values',
            'data' => '',
          ];
        }
        $element['axes_settings'][$axis] = $this->buildSourceSelector($axes[$axis], $selector_settings[$axis], FALSE);
      }

      // Ajax logic.
      $id_components = array_merge($element['#field_parents'], [
        $field->getName(),
        $delta,
        'table',
        'wrapper',
      ]);

      $table_id = implode('-', $id_components);
      $table['#prefix'] = '<div id="' . $table_id . '">';
      $table['#suffix'] = '</div>';

      $element['axes_settings']['rebuild'] = [
        '#type' => 'submit',
        '#submit' => [[get_called_class(), 'rebuildAxesSubmit']],
        '#value' => $this->t('Rebuild table'),
        '#ajax' => [
          'callback' => [get_called_class(), 'rebuildAxesAjax'],
          'wrapper' => $table_id,
        ],
      ];
    }

    $table['#element_validate'][] = [$this, 'validateTablefield'];

    $element['table'] = $table;
    $element['#type'] = 'fieldset';
    $element['#title'] = $field->getLabel();

    return $element;
  }

  /**
   * Rebuild submit handler.
   */
  public static function rebuildAxesSubmit($form, $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Rebuild AJAX callback.
   */
  public static function rebuildAxesAjax($form, $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = array_slice($trigger['#array_parents'], 0, -2, TRUE);
    $parents[] = 'table';
    $element = NestedArray::getValue($form, $parents);
    return $element;
  }

  /**
   * Validate handler.
   */
  public function validateTablefield(array &$element, FormStateInterface &$form_state, array $form) {
    if ($element['#required'] && $form_state->getTriggeringElement()['#type'] == 'submit') {
      $items = new FieldItemList($this->fieldDefinition);
      $this->extractFormValues($items, $form, $form_state);
      if (!$items->count()) {
        $form_state->setError($element, t('!name field is required.', ['!name' => $this->fieldDefinition->getLabel()]));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Set error only on the first item in a multi-valued field.
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element[0];
  }

}
