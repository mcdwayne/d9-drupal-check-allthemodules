<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorAjaxForm .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\generator_ui\Controller\GeneratorController;

class GeneratorAjaxForm extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_ajax_form';

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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your ajax Form in D8' . '</h2>'),
      "#weight" => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('form'=>'form_ajax.php.twig'),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['form'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Ajax Form class'),
      '#default_value' => 'ExampleAjaxForm',
      '#required' => TRUE,
    );
    $form['form_id_'] = array(
      '#type' => 'textfield',
      '#title' => t('Id of the Form'),
      '#default_value' => 'ajax_form',
      '#required' => TRUE,
    );
    $form['validation_form'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Ajax validation'),
    );
    $form['demo_field_ajax'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Field change demo.'),
    );
    $form['submission_demo_ajax'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Ajax action button demo'),
    );
    $form['submit_demo'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Ajax submit demo'),
    );
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