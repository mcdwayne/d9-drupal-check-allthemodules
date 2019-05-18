<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm.
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\generator_ui\Controller\GeneratorController;

class GeneratorGenerator extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to generate generators  in D8' . '</h2>'),
      "#weight" => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array(
        'form'=>'form_template.php.twig',
        'twig_name'=>'generator_test.html.twig.twig.twig',
         'routing_generator.routing.yml.twig'

      ),
    );
    $form['control-form'] = array(
      '#type' => 'fieldset',
      '#title' => t('The form'),
      '#collapsible' => TRUE,
    );
    $form['control-form']['form'] = array(
      '#type' => 'textfield',
      '#title' => t('Form generator name'),
      '#default_value' => 'GeneratorTest',
      '#description' => t('Path of form class: generator_ui/src/Form')
    );
    $form['control-form']['form_id_'] = array(
      '#type' => 'textfield',
      '#default_value' => 'generator_test',
      '#title' => $this->t('Form id'),
    );
    $form['twig_name'] = array(
      '#type' => 'textfield',
      '#default_value' => 'test',
      '#title' => $this->t('Name and path of the twig file'),
      '#description' => t('Path of twig file: templates/path-where-the-file-should-be-generated.')
    );
    $form = parent::buildForm($form, $form_state);
    $form['module_name']['#value'] = 'generator_ui';
    $form['module_name']['#disabled'] = TRUE;
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