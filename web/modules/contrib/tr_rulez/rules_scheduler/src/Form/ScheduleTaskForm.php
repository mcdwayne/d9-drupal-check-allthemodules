<?php

namespace Drupal\rules_scheduler\Form;

use Drupal\Core\Cache\Exception\CacheableNotFoundHttpException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form to manually schedule a rules component.
 */
class ScheduleTaskForm extends SchedulerFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rules_scheduler_schedule_form';
  }

  /**
   * Provides the page title on the form.
   */
  public function getTitle($rules_component) {
    return $this->t('Schedule rules component "@label"', ['@label' => $rules_component]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rules_component = NULL) {
  //function rules_scheduler_schedule_form($form, &$form_state, $rules_config, $base_path) {
return;
    // Only components can be scheduled.
    if (!($rules_component instanceof RulesTriggerableInterface)) {
      RulesPluginUI::$basePath = $base_path;
      $form_state->setValue('component', $rules_component->name);

      $action = $this->actionManager->createInstance('schedule')
        ->setContextValue('component', $this->getTypedData('string', $rules_component));

//      $action = rules_action('schedule', ['component' => $rules_component->name]);

      $action->form($form, $form_state);
      // The component should be fixed, so hide the parameter for it.
      $form['parameter']['component']['#access'] = FALSE;
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Schedule'),
      ];
// @todo      $form['#validate'] = ['rules_ui_form_rules_config_validate'];
      return $form;
    }

    throw new CacheableNotFoundHttpException();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getValue('rules_element');
    $action->execute();
    $this->messenger()->addMessage($this->t('Component %label has been scheduled.', [
      '%label' => rules_config_load($form_state->getValue('component'))->label(),
    ]));
    $form_state->setRedirect('rules_scheduler.schedule');
  }

}
