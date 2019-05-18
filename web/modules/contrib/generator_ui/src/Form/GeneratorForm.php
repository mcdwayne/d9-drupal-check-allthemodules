<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\generator_ui\Controller\GeneratorController;

class GeneratorForm extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_form';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Form in D8' . '</h2>'),
      '#weight' => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('form'=>'form_simple.php.twig'),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['form'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Form class'),
      '#default_value' => 'ExampleForm',
      '#description' => t('Has an impact of the path of the file : module/src/Form/xxx'),
      '#required' => TRUE,
    );
    $form['form_id_'] = array(
      '#type' => 'textfield',
      '#title' => t('Id of the Form'),
      '#default_value' => 'id_form',
      '#description' => t('Internal ID of the block, used in code.'),
      '#required' => TRUE,
    );

    // Add Field To load services from the container
    $form['servicesForm'] = array(
        '#type' => 'textfield',
        '#autocomplete_route_name' => 'generator.services_autocomplete',
        '#title' => t('Dependency injection'),
        '#description' => $this->t('Do you want to load services from the container?')
    );
    $form['validation_form'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Generate validation method'),
      '#description' => t('Allows to validate the form globally against certain conditions.')
    );
    $form['validate_number'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check to generate an element validation'),
      '#description' => t('Shows how to validate a specific form element.')
    );
    $form['validate_states'] = array(
      '#type' => 'checkbox',
      '#title' => t("State example"),
      '#description' => t('Shows how to make field depends one from another.')
    );
    $form['rebuild_form'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check to set the form to be rebuilt after processing'),
      '#description' => t('After submitting, the values entered in your form will be kept.'),
    );

    if (($form_state->getValue('servicesForm'))):
      $form['containers'] = array(
          "#type" => 'hidden',
          "#value" => $this->getContainer($form_state->getValue('servicesForm'))
      );
    endif;
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);
  }
}