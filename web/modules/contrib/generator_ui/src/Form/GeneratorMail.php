<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class GeneratorMail extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_mail';

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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your mail sends page in D8' . '</h2>'),
      "#weight" => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array(
        'form' => 'form_email.php.twig',
        "generator.module.yml.twig"
      ),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['form'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Form class'),
      '#default_value' => 'ExampleMailForm',
      '#description' => t('Path of Email Form class: module/src/Form/'),
      '#required' => TRUE,
    );
    $form['form_id_'] = array(
      '#type' => 'textfield',
      '#title' => t('Id of the Form'),
      '#default_value' => 'id_mail_form',
      '#required' => TRUE,
    );
    $form['validation_form'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Generate validation method: yes or no ?'),
    );
    $form['hook_mail_alter'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Generate hook_mail_alter ?'),
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