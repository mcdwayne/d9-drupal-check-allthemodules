<?php

namespace Drupal\rules_scheduler\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\rules\Exception\EvaluationException;
use Drupal\rules\Exception\IntegrityException;

/**
 * Provides a 'Schedule component evaluation' action.
 *
 * @RulesAction(
 *   id = "schedule",
 *   label = @Translation("Schedule component evaluation"),
 *   category = @Translation("Rules scheduler"),
 *   context = {
 *     "component" = @ContextDefinition("string",
 *       label = @Translation("Component"),
 *       description = @Translation("Select the component to schedule. Only components containing actions are available – no condition sets."),
 *       list_options_callback = "getOptionsList"
 *     ),
 *     "date" = @ContextDefinition("datetime_iso8601",
 *       label = @Translation("Scheduled evaluation date")
 *     ),
 *     "identifier" = @ContextDefinition("string",
 *       label = @Translation("Identifier"),
 *       description = @Translation("A string used for identifying this task. Any existing tasks for this component with the same identifier will be replaced."),
 *       optional = TRUE
 *     )
 *   }
 * )
 */
class ScheduleTask extends RulesActionBase {

  /**
   * Order status options callback.
   */
  public function getOptionsList() {
    return rules_get_components(TRUE, 'action');
  }

  /**
   * RulesAction implementation for scheduling components.
   *
   * @param string $component
   *   The component name.
   * @param string $date
   *   The datetime that this task should run status.
   * @param string $identifier
   *   The task identifier.
   */
  protected function doExecute($component, $date, $identifier = NULL) {
  }

  /**
   * Base action implementation for scheduling components.
   */
  public function rules_scheduler_action_schedule($args, $element) {
    $state = $args['state'];
    if ($component = rules_get_cache('comp_' . $args['component'])) {
      // Manually create a new evaluation state for scheduling the evaluation.
      $new_state = new RulesState();

      // Register all parameters as variables.
      foreach ($element->pluginParameterInfo() as $name => $info) {
        if (strpos($name, 'param_') === 0) {
          // Remove the parameter name prefix 'param_'.
          $var_name = substr($name, 6);
          $new_state->addVariable($var_name, $state->currentArguments[$name], $info);
        }
      }
      rules_scheduler_schedule_task([
        'date' => $args['date'],
        'config' => $args['component'],
        'data' => $new_state,
        'identifier' => $args['identifier'],
      ]);
    }
    else {
      throw new EvaluationException('Unable to get the component %name', ['%name' => $args['component']], $element, RulesLog::ERROR);
    }
  }

  /**
   * Info alteration callback for the schedule action.
   */
  public function rules_scheduler_action_schedule_info_alter(&$element_info, RulesPlugin $element) {
    if (isset($element->settings['component'])) {
      // If run during a cache rebuild the cache might not be instantiated yet,
      // so fail back to loading the component from database.
      if (($component = rules_get_cache('comp_' . $element->settings['component'])) || $component = rules_config_load($element->settings['component'])) {
        // Add in the needed parameters.
        foreach ($component->parameterInfo() as $name => $info) {
          $element_info['parameter']['param_' . $name] = $info;
        }
      }
    }
  }

  /**
   * Validate callback for the schedule action.
   *
   * Makes sure the component exists and is not dirty.
   *
   * @see rules_element_invoke_component_validate()
   */
  public function validate(RulesPlugin $element) {
    $info = $element->info();
    $component = rules_config_load($element->settings['component']);
    if (!$component) {
      throw new IntegrityException(t('The component %config does not exist.', ['%config' => $element->settings['component']]), $element);
    }
    // Check if the component is marked as dirty.
    rules_config_update_dirty_flag($component);
    if (!empty($component->dirty)) {
      throw new IntegrityException(t('The utilized component %config fails the integrity check.', ['%config' => $element->settings['component']]), $element);
    }
  }

  /**
   * Help for the schedule action.
   */
  public function help() {
    return t("Note that component evaluation is triggered by <em>cron</em> – make sure cron is configured correctly by checking your site's !status. The scheduling time accuracy depends on your configured cron interval. See <a href='@url'>the online documentation</a> for more information on how to schedule evaluation of components.", [
      '!status' => Link::createFromRoute($this->t('Status report'), 'system.status')->toString(),
      '@url' => rules_external_help('scheduler'),
    ]);
  }

  /**
   * Form alter callback for the schedule action.
   */
  public function rules_scheduler_action_schedule_form_alter(&$form, &$form_state, $options, RulesAbstractPlugin $element) {
    $first_step = empty($element->settings['component']);
    $form['reload'] = [
      '#weight' => 5,
      '#type' => 'submit',
      '#name' => 'reload',
      '#value' => $first_step ? t('Continue') : t('Reload form'),
      '#limit_validation_errors' => [['parameter', 'component']],
      '#submit' => ['rules_action_type_form_submit_rebuild'],
      '#ajax' => rules_ui_form_default_ajax(),
    ];
    // Use ajax and trigger as the reload button.
    $form['parameter']['component']['settings']['type']['#ajax'] = $form['reload']['#ajax'] + [
      'event' => 'change',
      'trigger_as' => ['name' => 'reload'],
    ];

    if ($first_step) {
      // In the first step show only the component select.
      foreach (element_children($form['parameter']) as $key) {
        if ($key != 'component') {
          unset($form['parameter'][$key]);
        }
      }
      unset($form['submit']);
      unset($form['provides']);
    }
    else {
      // Hide the reload button in case js is enabled and
      // it's not the first step.
      $form['reload']['#attributes'] = ['class' => ['rules-hide-js']];
    }
  }

}
