<?php

/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorInfo .
 *
 */

namespace Drupal\generator_ui\Form;

//Use the necessary libraries
use Drupal\Core\Form\FormStateInterface;
use Drupal\generator_ui\Controller\GeneratorController;


class GeneratorTwig extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'twig';

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
      '#markup' => t('<h2>' . 'Check to generate code and learn more about Twig' . '</h2>'),
      "#weight" => -2
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('exampleTwig'=>'generator_twig.html.twig.twig'),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['tags'] = array(
      '#type' => 'checkbox',
      '#title' => t('Tags'),
      '#default_value' => '0',
      '#description' => t('Check if you want to generate different tags.'),
    );
    $form['tags_details'] = array(
      '#type' => 'fieldset',
      '#title' => t('Tags Reference in Drupal 8:'),
      '#collapsible' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="tags"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['tags_details']['blocks'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Blocks'),
      '#default_value' => '0',
      '#description' => $this->t('Blocks are used for inheritance and act as placeholders and replacements at the same time.Ex.  {% block head %}   {% endblock %}'),
    );
    $form['tags_details']['extends'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Extends'),
      '#default_value' => '0',
      '#description' => $this->t('The extends tag can be used to extend a template from another one..Ex.  {% extends "parent.html.twig" %}'),
    );
    $form['tags_details']['include'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include'),
      '#default_value' => '0',
      '#description' => $this->t('The include statement includes a template and returns the rendered content of that file into the current namespace.Ex.{% include "header.html.twig" %}'),
    );
    $form['tags_details']['import'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Import'),
      '#default_value' => '0',
      '#description' => $this->t('You can import the complete template into a variable.'),
    );
    $form['tags_details']['for'] = array(
      '#type' => 'checkbox',
      '#title' => t('For'),
      '#default_value' => '0',
      '#description' => $this->t('Loop over each item in a sequence.'),
    );
    $form['tags_details']['if'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('If'),
      '#default_value' => '0',
      '#description' => $this->t('The if statement in Twig is comparable with the if statements of PHP.'),
    );
    $form['tags_details']['set'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Set'),
      '#default_value' => '0',
      '#description' => $this->t('Inside code blocks you can also assign values to variables.Ex. {% set variable = "example" %}'),
    );
    $form['filters'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Filters'),
      '#default_value' => '0',
      '#description' => $this->t('Check if you want to generate different filters.'),
    );

    $form['filters_details'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Filters Reference in Drupal 8:'),
      '#collapsible' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="filters"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['filters_details']['merge'] = array(
      '#type' => 'checkbox',
      '#title' => t('Merge'),
      '#default_value' => '0',
      '#description' => $this->t('The merge filter merges an array with another array.'),
    );
    $form['filters_details']['lower'] = array(
      '#type' => 'checkbox',
      '#title' => t('Lower'),
      '#default_value' => '0',
      '#description' => $this->t('The lower filter converts a value to lowercase.'),
    );
    $form['filters_details']['length'] = array(
      '#type' => 'checkbox',
      '#title' => t('Length'),
      '#default_value' => '0',
      '#description' => $this->t('The length filter returns the number of items of a sequence or mapping, or the length of a string.'),
    );
    $form['filters_details']['keys'] = array(
      '#type' => 'checkbox',
      '#title' => t('Keys'),
      '#default_value' => '0',
      '#description' => $this->t('The keys filter returns the keys of an array.'),
    );
    $form['filters_details']['json_encode'] = array(
      '#type' => 'checkbox',
      '#title' => t('json_encode'),
      '#default_value' => '0',
      '#description' => $this->t('The json_encode filter returns the JSON representation of a value.'),
    );
    $form['filters_details']['join'] = array(
      '#type' => 'checkbox',
      '#title' => t('Join'),
      '#default_value' => '0',
      '#description' => $this->t('The join filter returns a string which is the concatenation of the items of a sequence.'),
    );
    $form['filters_details']['escape'] = array(
      '#type' => 'checkbox',
      '#title' => t('escape'),
      '#default_value' => '0',
      '#description' => $this->t('The escape filter escapes a string for safe insertion into the final output,Internally, escape uses the PHP native htmlspecialchars function for the HTML escaping strategy.'),
    );
    $form['filters_details']['date'] = array(
      '#type' => 'checkbox',
      '#title' => t('date'),
      '#default_value' => '0',
      '#description' => $this->t('The date filter formats a date to a given format.'),
    );
    $form['filters_details']['capitalize'] = array(
      '#type' => 'checkbox',
      '#title' => t('capitalize'),
      '#default_value' => '0',
      '#description' => $this->t('The capitalize filter capitalizes a value. The first character will be uppercase, all others lowercase.'),
    );
    $form = parent::buildForm($form, $form_state);
   // unset($form['module_name']);
  //  unset($form['btn_create']);
    unset($form['btn_download']);

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
  public
  function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);

  }


}