<?php

namespace Drupal\defined_table\Plugin\Field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Component\Utility\NestedArray;

/**
 * Common methods.
 */
trait DefinedTableSourceSelectionTrait {

  /**
   * Builds the datasource selector.
   */
  protected function buildSourceSelector($label, array $settings, $allow_dynamic = TRUE) {
    $element = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => $label,
    ];
    $options = [
      'values' => t('User values'),
      'taxonomy' => t('Taxonomy based'),
    ];
    if ($allow_dynamic) {
      $options['dynamic'] = $this->t('Per-entity setting');
    }
    $element['type'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $settings['type'],
      '#ajax' => [
        'callback' => [get_called_class(), 'fieldSettingsAjax'],
      ],
    ];

    switch ($settings['type']) {
      case 'values':
        $element['data'] = [
          '#type' => 'textarea',
          '#rows' => 3,
          '#title' => $this->t('Values'),
          '#description' => $this->t('Enter comma separated list of header row values.'),
          '#default_value' => $settings['data'],
        ];
        break;

      case 'taxonomy':
        $element['data'] = [
          '#type' => 'select',
          '#title' => $this->t('Vocabulary'),
          '#options' => [0 => $this->t('-- Select vocabulary --')],
          '#default_value' => $settings['data'],
        ];
        $vocabularies = Vocabulary::loadMultiple();
        foreach ($vocabularies as $id => $vocabulary) {
          $element['data']['#options'][$id] = $vocabulary->label();
        }
        break;

      case 'dynamic':
        $element['data'] = [
          '#type' => 'item',
          '#markup' => $this->t('Will be selected on the entity form.'),
        ];
        break;

      default:
        $element['data'] = [
          '#type' => 'item',
          '#title' => $this->t('Make a selection.'),
        ];

    }

    return $element;
  }

  /**
   * AJAX callback.
   */
  public static function fieldSettingsAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $trigger = $form_state->getTriggeringElement();
    $array_parents = $trigger['#array_parents'];
    $array_parents[count($array_parents) - 1] = 'data';
    $element = NestedArray::getValue($form, $array_parents);
    $element['#value'] = '';
    $parents = $trigger['#parents'];
    $parents[count($parents) - 1] = 'data';
    $selector = '.form-item-' . strtr(implode('-', $parents), ['_' => '-']);
    $response->addCommand(new ReplaceCommand(
      $selector,
      \Drupal::service('renderer')->render($element)
    ));
    return $response;
  }

}
