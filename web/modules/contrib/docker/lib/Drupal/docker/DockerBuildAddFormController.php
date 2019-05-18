<?php

/**
 * @file
 * Definition of Drupal\docker\DockerBuildAddFormController.
 */

namespace Drupal\docker;

use Drupal\Core\Entity\ContentEntityFormController;

/**
 * Base for controller for docker host forms.
 */
class DockerBuildAddFormController extends ContentEntityFormController {

  /**
   * {@inheritdoc}
   */
  public function init(array &$form_state) {
    parent::init($form_state);
    drupal_set_title($this->t('Add new build'));
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    // Do not prepare the entity while it is being added.
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $docker_build = $this->entity;
    $form['#id'] = drupal_html_id('docker_build_form');

    $form['name'] = array(
      '#type' => 'fieldset',
      '#title' => t('Build basic information'),
      '#attributes' => array('class' => array('fieldset-no-legend')),
    );

    $form['name']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Build name'),
      '#required' => TRUE,
      '#size' => 32,
      '#default_value' => '',
      '#maxlength' => 255,
    );
    $form['name']['machine_name'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => 'views_get_view',
        'source' => array('name', 'label'),
      ),
      '#description' => $this->t('A unique machine-readable name for this build. It must only contain lowercase letters, numbers, and underscores.'),
    );
    $form['name']['description_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Description'),
    );
    $form['name']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Provide description'),
      '#title_display' => 'invisible',
      '#size' => 64,
      '#default_value' => '',
      '#states' => array(
        'visible' => array(
          ':input[name="description_enable"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form = parent::form($form, $form_state, $docker_build);

    // Hide dockerfile from add form.
    $form['dockerfile']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This is the default entity object builder function. It is called before any
   * other submit handler to build the new entity object to be passed to the
   * following submit handlers. At this point of the form workflow the entity is
   * validated and the form state can be updated, this way the subsequently
   * invoked handlers can retrieve a regular entity object to act on.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function submit(array $form, array &$form_state) {
    // Remove button and internal Form API values from submitted values.
    form_state_values_clean($form_state);
    unset($form_state['values']['description_enable']);
    $docker_build = parent::submit($form, $form_state);
    return $docker_build;
  }

  public function save(array $form, array &$form_state) {
    $docker_build = $this->entity;
    $docker_build->save();
    $uri = $docker_build->uri();
    $form_state['redirect'] = $uri['path'] . '/edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save and edit');
    $actions['cancel'] = array(
      '#value' => $this->t('Cancel'),
      '#submit' => array(
        array($this, 'cancel'),
      ),
      '#limit_validation_errors' => array(),
    );
    return $actions;
  }



  /**
   * Form submission handler for the 'cancel' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function cancel(array $form, array &$form_state) {
    $form_state['redirect'] = 'docker/builds';
  }
}