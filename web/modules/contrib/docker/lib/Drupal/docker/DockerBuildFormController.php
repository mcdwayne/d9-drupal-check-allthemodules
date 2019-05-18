<?php

/**
 * @file
 * Definition of Drupal\docker\DockerBuildFormController.
 */

namespace Drupal\docker;

use Drupal\Core\Entity\ContentEntityFormController;

/**
 * Base for controller for docker host forms.
 */
class DockerBuildFormController extends ContentEntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $docker_build = $this->entity;
    $form['foo'] = array(
      '#markup' => '<pre>' . print_r($docker_build->dockerfiles, true) . '</pre>'
    );

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
      '#default_value' => $docker_build->label,
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
      '#disabled' => TRUE,
      '#default_value' => $docker_build->machine_name,
    );

    $form['name']['description_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Description'),
      '#default_value' => !empty($docker_build->description) ? 1 : 0
    );
    $form['name']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Provide description'),
      '#title_display' => 'invisible',
      '#size' => 64,
      '#default_value' => $docker_build->description,
      '#states' => array(
        'visible' => array(
          ':input[name="description_enable"]' => array('checked' => TRUE),
        ),
      ),
    );

    return parent::form($form, $form_state, $docker_build);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    form_state_values_clean($form_state);
    unset($form_state['values']['description_enable']);
    $docker_build = parent::submit($form, $form_state);
    return $docker_build;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $docker_build = $this->entity;
    $docker_build->save();
    $form_state['redirect'] = 'docker/builds';
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);
    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';
    return $element;
  }

  /**
   * Returns an array of metadata keyed by instruction name.
   */
  private function getInstructionOptions() {
    global $user;
    return array(
      'FROM' => array(
        '#description' => t('The FROM instruction sets the Base Image for subsequent instructions.'),
        '#placeholder' => t('i.e. ubuntu'),
        '#required' => TRUE,
        '#multiple' => TRUE
      ),
      'MAINTAINER' => array(
        '#description' => t('The MAINTAINER instruction allows you to set the Author field of the generated images.'),
        '#placeholder' => t('i.e. @name', array('@name' => $user->name)),
        '#required' => FALSE,
        '#multiple' => FALSE
      ),
      'RUN' => array(
        '#description' => t('The RUN instruction will execute any commands on the current image and commit the results.'),
        '#placeholder' => t('i.e. apt-get install nginx'),
        '#required' => FALSE,
        '#multiple' => TRUE
      ),
      'CMD' => array(
        '#description' => t('The main purpose of a CMD is to provide defaults for an executing container. '),
        '#placeholder' => t('i.e. ["/usr/bin/wc","--help"]'),
        '#required' => FALSE,
        '#multiple' => FALSE
      ),
      'EXPOSE' => array(
        '#description' => t('The EXPOSE instruction sets ports to be publicly exposed when running the image.'),
        '#placeholder' => t('i.e. 80'),
        '#required' => FALSE,
        '#multiple' => TRUE
      ),
      'ENV' => array(
        '#description' => t('The ENV instruction sets the environment variable <key> to the value <value>. This value will be passed to all future RUN instructions.'),
        '#placeholder' => t('i.e. JAVA_HOME=/usr/local/java'),
        '#required' => FALSE,
        '#multiple' => TRUE
      ),
      'ADD' => array(
        '#description' => t('The ADD instruction will copy new files from <src> and add them to the container’s filesystem at path <dest>.'),
        '#placeholder' => t('i.e. /build/server/path /image/path'),
        '#required' => FALSE,
        '#multiple' => TRUE
      ),
      'ENTRYPOINT' => array(
        '#description' => t('The ENTRYPOINT instruction adds an entry command that will not be overwritten when arguments are passed to docker run, unlike the behavior of CMD.'),
        '#placeholder' => t('i.e. ["/usr/bin/wc"]'),
        '#required' => FALSE,
        '#multiple' => FALSE
      ),
      'VOLUME' => array(
        '#description' => t('The VOLUME instruction will add one or more new volumes to any container created from the image.'),
        '#placeholder' => t('i.e. ["/data"]'),
        '#required' => FALSE,
        '#multiple' => TRUE
      ),
      'USER' => array(
        '#description' => t('The USER instruction sets the username or UID to use when running the image.'),
        '#placeholder' => t('i.e. daemon'),
        '#required' => FALSE,
        '#multiple' => FALSE
      ),
      'WORKDIR' => array(
        '#description' => t('The WORKDIR instruction sets the working directory in which the command given by CMD is executed.'),
        '#placeholder' => t('i.e. /path/to/workdir'),
        '#required' => FALSE,
        '#multiple' => FALSE
      )
    );
  }
}