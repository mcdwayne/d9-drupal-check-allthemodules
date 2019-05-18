<?php

/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorInfo .
 *
 */

namespace Drupal\generator_ui\Form;

//Use the necessary libraries
use Drupal\Core\Form\FormStateInterface;


class GeneratorCssJs extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'css_js';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your module in D8' . '</h2>'),
      "#weight" => -2
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['choice'] = array(
      '#type' => 'select',
      '#title' => 'Select CSS or JS file',
      '#options' => array('css' => 'css', 'js' => 'js'),
      '#ajax' => array(
        'callback' => '::choice_cssjs',
        'wrapper' => 'ajax_choice',
      ),
    );
    if ($form_state->getValue('choice') == 'js') {
      $form['file_name'] = array(
        '#type' => 'textfield',
        '#title' => 'File name Js',
        '#default_value' => 'example',
        '#required' => TRUE,
        '#prefix' => '<div id="ajax_choice">',
        '#suffix' => '</div>',

      );
      $form['twig_file'] = array(
        "#type" => 'hidden',
        "#value" => array(
          'js.libraries.yml.twig',
          'generator.js.yml.twig'
        ),
      );

    }
    else {
      $form['file_name'] = array(
        '#type' => 'textfield',
        '#title' => 'File name css',
        '#default_value' => 'example',
        '#required' => TRUE,
        '#prefix' => '<div id="ajax_choice">',
        '#suffix' => '</div>',
      );
      $form['twig_file'] = array(
        "#type" => 'hidden',
        "#value" => array(
          'css.libraries.yml.twig',
          'css.module.yml.twig',
          'generator.css.yml.twig'
        ),
      );
    }
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * return type of routing: Form routing and controller routing.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   return choice of routing form.
   */
  public function choice_cssjs(array $form, FormStateInterface $form_state) {
    return $form['file_name'];
  }

  /**
   * return type of routing: Form routing and controller routing.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   return choice of routing form.
   */
  public function choiceRouting(array $form, FormStateInterface $form_state) {
    $choiceRouting = $form_state->getValue('type_routing');
    switch ($choiceRouting) {
      case "controller" :
        return $form['control_method'];
        break;
      case "form_" :
        return $form['control_form'];
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
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
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    return parent::submitForm($form, $form_state);
  }
}