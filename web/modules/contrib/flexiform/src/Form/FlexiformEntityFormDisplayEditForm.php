<?php

namespace Drupal\flexiform\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\field_ui\Form\EntityFormDisplayEditForm;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerInterface;
use Drupal\flexiform\MultipleEntityFormState;

/**
 * Provides Flexiform form elements for the EntityFormDisplay entity type.
 */
class FlexiformEntityFormDisplayEditForm extends EntityFormDisplayEditForm {

  use AjaxFormTrait;

  /**
   * The form entity manager object.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  protected $formEntityManager;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    if (!isset($form['#parents'])) {
      $form['#parents'] = [];
    }

    $form_state = MultipleEntityFormState::createForForm($form, $form_state);
    $form = parent::form($form, $form_state);
    $form['#entity_type'] = $this->entity->getTargetEntityTypeId();
    $form['#bundle'] = $this->entity->getTargetBundle();
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['fields']['#header'][0] = $this->t('Component');

    // Components.
    $component_type_manager = \Drupal::service('plugin.manager.flexiform.form_component_type');
    $component_rows = [];
    foreach ($component_type_manager->getDefinitions() as $component_type => $definition) {
      $component_rows += $component_type_manager
        ->createInstance($component_type)
        ->setFormDisplay($this->entity)
        ->setFormEntityManager($this->entity->getFormEntityManager($form_state))
        ->componentRows($this, $form, $form_state);
    }
    $form['fields'] = $component_rows + $form['fields'];

    // Enhancers.
    $form['enhancer'] = [
      '#type' => 'vertical_tabs',
    ];
    foreach ($this->entity->getFormEnhancers('configuration_form') as $enhancer_name => $enhancer) {
      if ($enhancer instanceof ConfigurableFormEnhancerInterface) {
        $form['enhancer_' . $enhancer_name] = [
          '#type' => 'details',
          '#title' => $enhancer->getPluginDefinition()['label'],
          '#parents' => ['enhancer', $enhancer_name],
          '#array_parents' => ['enhancer_' . $enhancer_name],
          '#group' => 'enhancer',
          '#tree' => TRUE,
        ];
        $form['enhancer_' . $enhancer_name] += $enhancer->configurationForm($form['enhancer_' . $enhancer_name], $form_state);
      }
    }

    if (!empty($form['modes'])) {
      $form['modes']['#weight'] = 90;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $form_state = MultipleEntityFormState::createForForm($form, $form_state);
    $form_values = $form_state->getValues();
    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $form_values = array_diff_key($form_values, $this->entity->getPluginCollections());
    }

    // Collect component options.
    foreach ($form_values['fields'] as $component_name => $values) {
      // Do not handle extra fields here.
      // @todo: Make extra fields a component type.
      if (!empty($form['#extra'][$component_name])) {
        continue;
      }

      if ($values['region'] == 'hidden') {
        $entity->removeComponent($component_name);
      }
      else {
        $options = $entity->getComponent($component_name);
        $options = $entity->getComponentTypePlugin(!empty($options['component_type']) ? $options['component_type'] : 'field_widget', $this->entity->getFormEntityManager($form_state))->submitComponentRow($component_name, $values, $form, $form_state);
        $entity->setComponent($component_name, $options);
      }
    }

    // Collect options for extra field components.
    foreach ($form['#extra'] as $name) {
      if ($form_values['fields'][$name]['region'] == 'hidden') {
        $entity->removeComponent($name);
      }
      else {
        $entity->setComponent($name, [
          'weight' => $form_values['fields'][$name]['weight'],
          'region' => $form_values['fields'][$name]['region'],
        ]);
      }
    }

    // Loop over the enhancers and let them set their configuration internally
    // this then gets saved in the presave of the FormDisplay entity.
    foreach ($entity->getFormEnhancers('configuration_form') as $enhancer_name => $enhancer) {
      if ($enhancer instanceof ConfigurableFormEnhancerInterface) {
        $enhancer->configurationFormSubmit($form['enhancer_' . $enhancer_name], $form_state);
      }
    }
  }

  /**
   * Get the form entity manager.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   *   The entity form manager.
   */
  public function getFormEntityManager() {
    if (empty($this->formEntityManager)) {
      $this->formEntityManager = $this->entity->getFormEntityManager();
    }

    return $this->formEntityManager;
  }

}
