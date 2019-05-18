<?php

namespace Drupal\flexiform_wizard\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an edit form for flexiform wizard entities.
 */
class WizardEditForm extends WizardForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\flexiform_wizard\Entity\Wizard $entity */
    $entity = $this->entity;
    $form = parent::form($form, $form_state);

    $form['parameters'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Machine-Name'),
        $this->t('Label'),
        $this->t('Entity Type'),
      ],
      '#empty' => $this->t("This wizard doesn't have an parameters defined yet. Add parameters by altering the path."),
      '#theme_wrappers' => ['fieldset' => ['#title' => $this->t('Parameters')]],
    ];
    preg_match_all('/\{(?P<parameter>[A-Za-z0-9_\-]+)\}/', $entity->get('path'), $matches, PREG_PATTERN_ORDER);
    $parameters = $entity->get('parameters');

    $entity_type_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      foreach (\Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id) as $bundle_id => $bundle_info) {
        $entity_type_options[$entity_type->getLabel()->render()][$entity_type_id . ':' . $bundle_id] = $bundle_info['label'];
      }
    }

    foreach ($matches['parameter'] as $param_name) {
      $form['parameters'][$param_name]['machine_name'] = [
        '#type' => 'item',
        '#markup' => $param_name,
        '#value' => $param_name,
      ];
      $form['parameters'][$param_name]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Parameter Label'),
        '#title_display' => 'invisible',
        '#default_value' => !empty($parameters[$param_name]['label']) ? $parameters[$param_name]['label'] : '',
      ];
      $form['parameters'][$param_name]['entity_type_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Type'),
        '#title_display' => 'invisible',
        '#options' => $entity_type_options,
        '#default_value' => !empty($parameters[$param_name]['entity_type']) && !empty($parameters[$param_name]['bundle']) ? $parameters[$param_name]['entity_type'] . ':' . $parameters[$param_name]['bundle'] : NULL,
        '#element_validate' => [
          ['\Drupal\flexiform_wizard\Form\WizardEditForm', 'parameterEntityTypeBundleElementValidate'],
        ],
      ];
    }

    $form['pages'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Machine-Name'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t("This wizard doesn't have any pages defined yet."),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'wizard-page-weight',
        ],
      ],
      '#theme_wrappers' => ['fieldset' => ['#title' => $this->t('Pages')]],
    ];
    foreach ($entity->getPages() as $name => $page) {
      $form['pages'][$name]['#attributes']['class'][] = 'draggable';
      $form['pages'][$name]['#weight'] = $page['weight'] ?: 0;
      $form['pages'][$name]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Page Label'),
        '#title_display' => 'invisible',
        '#default_value' => $page['label'],
      ];
      $form['pages'][$name]['machine_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Machine-Name'),
        '#title_display' => 'invisible',
        '#default_value' => $name,
      ];
      $form['pages'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $page['label']]),
        '#title_display' => 'invisible',
        '#default_value' => $page['weight'],
        '#attributes' => ['class' => ['wizard-page-weight']],
      ];
    }

    return $form;
  }

  /**
   * Element validation handler for the parameter entity type bundle.
   *
   * @param array $element
   *   The element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The complete form array.
   */
  public static function parameterEntityTypeBundleElementValidate(array $element, FormStateInterface $form_state, array $form = []) {
    $parents = $element['#parents'];
    $entity_type_bundle = $form_state->getValue($parents);

    array_pop($parents);
    $parameter_info = $form_state->getValue($parents);
    unset($parameter_info['entity_type_bundle']);
    list($parameter_info['entity_type'], $parameter_info['bundle']) = explode(':', $entity_type_bundle, 2);

    $form_state->setValue($parents, $parameter_info);
  }

}
