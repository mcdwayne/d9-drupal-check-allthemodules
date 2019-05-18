<?php

namespace Drupal\entity_role_view_mode_switcher\Form;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Class RuleForm.
 */
class RuleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_role_view_mode_switcher\Entity\RuleInterface $rule */
    $rule = $this->entity;

    // Disable caching for the form.
    $form['#cache'] = ['max-age' => 0];

    // Do not flatten nested form fields.
    $form['#tree'] = TRUE;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $rule->label(),
      '#description' => $this->t("Label for the View Mode Switcher Rule."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $rule->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_role_view_mode_switcher\Entity\Rule::load',
      ],
      '#disabled' => !$rule->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */
    $form['conditions_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Conditions'),
      '#prefix' => '<h3>' . $this->t('Conditions') . '</h3><div id="js-ajax-elements-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#description' => $this->t('The conditions will be evaluated in order. The first one to pass will take effect. If none pass, no view mode switching will occur.'),
    ];

    $form['conditions_container']['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('The conditions will be evaluated in order. The first one to pass will take effect. If none pass, no view mode switching will occur.'),
    ];

    $form['conditions_container']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Negate'),
        $this->t('Role'),
        $this->t('Original View Mode'),
        $this->t('New View Mode'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There are no conditions.'),
    ];

    $conditions = $rule->getConditions();
    if (empty($form_state->get('conditions_deltas'))) {
      if (empty($conditions)) {
        $form_state->set('conditions_deltas', range(0, 0));
      }
      else {
        $form_state->set('conditions_deltas', array_keys($conditions));
      }
    }

    /** @var array $conditionsDeltas */
    $conditionsDeltas = $form_state->get('conditions_deltas');

    foreach ($conditionsDeltas as $delta) {
      $form['conditions_container']['table'][$delta] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];

      $form['conditions_container']['table'][$delta]['negate'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Negate'),
        '#default_value' => isset($conditions[$delta]) ? $conditions[$delta]['negate'] : FALSE,
        '#description' => $this->t('Negate this condition.'),
      ];

      $form['conditions_container']['table'][$delta]['role_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Role'),
        '#options' => self::roleOptions(),
        '#default_value' => isset($conditions[$delta]) ? $conditions[$delta]['role_id'] : '',
        '#description' => $this->t('The role to trigger the switch.'),
      ];

      $form['conditions_container']['table'][$delta]['original_view_mode_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Original View Mode'),
        '#options' => self::viewModeOptions(),
        '#default_value' => isset($conditions[$delta]) ? $conditions[$delta]['original_view_mode_id'] : '',
        '#description' => $this->t('The entity type / view mode to trigger the switch. Make sure entity types match between this and the New View Mode.'),
      ];

      $form['conditions_container']['table'][$delta]['new_view_mode_id'] = [
        '#type' => 'select',
        '#title' => $this->t('New View Mode'),
        '#options' => self::viewModeOptions(),
        '#default_value' => isset($conditions[$delta]) ? $conditions[$delta]['new_view_mode_id'] : '',
        '#description' => $this->t('The entity type / view mode to switch to. Make sure entity types match between this and the Original View Mode.'),
      ];

      $form['conditions_container']['table'][$delta]['remove_condition'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => ['::ajaxRemoveCondition'],
        '#ajax' => [
          'callback' => '::ajaxRemoveConditionCallback',
          'wrapper' => 'js-ajax-elements-wrapper',
        ],
        '#weight' => -50,
        '#attributes' => [
          'class' => ['button-small'],
        ],
        '#name' => 'remove_condition_' . $delta,
      ];
    }

    $form['conditions_container']['add_condition'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Condition'),
      '#submit' => ['::ajaxAddCondition'],
      '#ajax' => [
        'callback' => '::ajaxAddConditionCallback',
        'wrapper' => 'js-ajax-elements-wrapper',
      ],
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $rule = $this->entity;
    $status = $rule->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label View Mode Switcher Rule.', [
          '%label' => $rule->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label View Mode Switcher Rule.', [
          '%label' => $rule->label(),
        ]));
    }
    $form_state->setRedirectUrl($rule->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    $values = $form_state->getValues();
    $conditions = $values['conditions_container']['table'];

    /** @var \Drupal\entity_role_view_mode_switcher\Entity\RuleInterface $entity */
    $entity->setConditionsFromArray($conditions);
  }

  /**
   * AJAX submit callback for removing conditions to the rule.
   */
  public static function ajaxRemoveCondition(array &$form, FormStateInterface $form_state) {
    // Get the triggering item.
    $deltaRemove = $form_state->getTriggeringElement()['#parents'][1];

    // Store our form state.
    $conditionsDeltas = $form_state->get('conditions_deltas');

    // Find the key of the item we need to remove.
    $key_to_remove = array_search($deltaRemove, $conditionsDeltas, TRUE);

    // Remove our triggered element.
    unset($conditionsDeltas[$key_to_remove]);

    // Rebuild the field deltas values.
    $form_state->set('conditions_deltas', $conditionsDeltas);

    // Rebuild the form.
    $form_state->setRebuild();

    // Return any messages set.
    drupal_get_messages();
  }

  /**
   * AJAX call back for add action that updates the conditions table.
   */
  public static function ajaxRemoveConditionCallback(array &$form, FormStateInterface $form_state) {
    return $form['conditions_container'];
  }

  /**
   * AJAX submit callback for adding conditions to the rule.
   */
  public static function ajaxAddCondition(array &$form, FormStateInterface $form_state) {

    // Check to see if there is more than one item in our array.
    $conditionsDeltas = $form_state->get('conditions_deltas');
    if (\count($conditionsDeltas) > 0) {
      // Add a new element to array and set it to our highest value plus one.
      $conditionsDeltas[] = max($conditionsDeltas) + 1;
    }
    else {
      // Set the new array element to 0.
      $conditionsDeltas[] = 0;
    }

    // Rebuild the field deltas values.
    $form_state->set('conditions_deltas', $conditionsDeltas);

    // Rebuild the form.
    $form_state->setRebuild();

    // Return any messages set.
    drupal_get_messages();
  }

  /**
   * AJAX call back for add action that updates the conditions table.
   */
  public static function ajaxAddConditionCallback(array &$form, FormStateInterface $form_state) {
    return $form['conditions_container'];
  }

  /**
   * Provides the options for roles.
   *
   * @return array
   *   Array of roles options for form.
   */
  protected static function roleOptions() {
    return array_map(function ($item) {
      return $item->label();
    }, Role::loadMultiple());
  }

  /**
   * Provides the options for view modes.
   *
   * @return array
   *   Array of view mode options for form.
   */
  protected static function viewModeOptions() {
    return array_map(function ($item) {
      /** @var \Drupal\Core\Entity\EntityViewModeInterface $item */
      return $item->getTargetType() . ' - ' . $item->label();
    }, EntityViewMode::loadMultiple());;
  }

}
