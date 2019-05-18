<?php

/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorTasks .
 *
 */

namespace Drupal\generator_ui\Form;

//Use the necessary libraries
use Drupal\Core\Form\FormStateInterface;


class GeneratorTasks extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'links.task';

  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['into'] = array(
      '#markup' => t('<h2>' . 'Please fill the blanks to create  module.links.task.yml in D8' . '</h2>'),
      "#weight" => -2
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('generator.links.task.yml.twig'),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['route'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Route name'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'autocomplete.route',
      '#description' => t('The first line is the machine name for your local task, which usually matches the machine name of the route (given in the "route_name" line).'),
    );
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Task title'),
      '#default_value' => 'Example Task',
      '#required' => TRUE,
    );
    $form['weight'] = array(
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => -2,
      '#description' => t('Lower (negative) numbers come before higher (positive) numbers)'),
    );
    $form['base_route'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Base route'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'autocomplete.route',
      '#description' => t('The machine name of the main task (tab) for the set
        of local tasks. You <strong>must</strong> follow the drupal path
        architecture (admin/configs/my-module/task will be for example
        a local task for admin/configs/my-module.'),
    );
    $form['note'] = array(
      '#markup' => t('<h5><em>' . 'Note: Local tasks from other modules can be altered using hook_menu_local_tasks_alter().' . '</em></h5>'),
    );
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('weight'))) {
      $form_state->setErrorByName('weight', $this->t('The weight must be numeric !'));
      return parent::validateForm($form, $form_state);
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public
  function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);

  }


}