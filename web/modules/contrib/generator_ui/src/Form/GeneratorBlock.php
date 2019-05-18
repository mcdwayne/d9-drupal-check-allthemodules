<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorBlock .
 *
 */
namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;


class GeneratorBlock extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_block';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Block in D8' . '</h2>'),
      "#weight" => -3
    );

    $form['config_block'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create a Configuration block'),
      '#weight' => -3,
      '#ajax' => array(
        'callback' => '::config_callback',
        'wrapper' => 'config-wrapper'
      ),
    );
    $form['transformation_path'] = array(
      '#type' => 'hidden',
      '#value' => TRUE,
    );
    $form['control_access'] = array(
      '#type' => 'checkbox',
      '#title' => t('Control access'),
      '#weight' => -3
    );
    $form['derivative'] = array(
      '#type' => 'checkbox',
      '#title' => t('Generate derivative'),
      '#ajax' => array(
        'callback' => '::derivative_callback',
        'wrapper' => 'config-derivative'
      ),
      '#weight' => -2
    );
    $form['class_block'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the block class'),
      '#default_value' => 'ExampleBlock',
      '#description' => t('Path of Block class depends on it and will be placed in module/src/Plugin/Block/xxx'),
      '#required' => TRUE,
    );

    $form['id_block'] = array(
      '#type' => 'textfield',
      '#title' => t('Id of the block'),
      '#description' => t('Internal ID of the block for code use.'),
      '#default_value' => 'id_block',
      '#required' => TRUE,
    );
    // Add Field To load services from the container
    $form['control_form']['servicesForm'] = array(
        '#type' => 'textfield',
        '#autocomplete_route_name' => 'generator.services_autocomplete',
        '#title' => t('Dependency injection'),
        '#description' => $this->t('Do you want to load services from the container?')
    );
    if (($form_state->getValue('servicesForm'))):
      $form['containers'] = array(
          "#type" => 'hidden',
          "#value" => $this->getContainer($form_state->getValue('servicesForm'))
      );
    endif;
    $form['category'] = array(
      '#type' => 'textfield',
      '#title' => t('Category of the block'),
      '#default_value' => t('My new category'),
      '#description' => t('Grouping blocks together on the block admin page. If not set, name of the module is used.'),
      '#required' => FALSE,
    );
    $form['admin_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label of the block'),
      '#default_value' => t('Hello !'),
      '#description' => t('Label that appears in the list of blocks in the block administration page.'),
      '#required' => TRUE,
    );

    if ($form_state->getValue('config_block') == 0) {
      $form['twig_file'] = array(
        "#type" => 'hidden',
        "#value" => array(
          'class_block' => 'block.php.twig',
        ),
      );
      $form['simple_block_fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Simple Block'),
        '#collapsible' => TRUE,
        '#prefix' => '<div id="config-wrapper">',
        '#suffix' => '</div>',
      );

      $form['simple_block_fieldset']['text'] = array(
        '#type' => 'textfield',
        '#title' => t('Text to display'),
        '#default_value' => t('Hello world !'),
        '#description' => t('Label that appears in the list of blocks in the block administration page.'),
        '#required' => TRUE,
      );
    }
    else {
      $form['config_block_fieldset'] = array(
        '#type' => 'hidden',
        '#prefix' => '<div id="config-wrapper">',
        '#suffix' => '</div>',
      );
    }
    if ($form_state->getValue('derivative')) {
      $form['twig_file'] = array(
        "#type" => 'hidden',
        "#value" => array(
          'class_block' => 'block.php.twig',
          'class_derivative' => 'derivative.php.twig'
        ),
      );
      $form['class_derivative'] = array(
        '#type' => 'textfield',
        '#title' => t('Name of the derivative class'),
        '#default_value' => 'ExampleDerivative',
        '#description' => t('Path of Derivative class depends on it and will be placed in module/src/Plugin/Derivative/xxx'),
        '#prefix' => '<div id="config-derivative">',
        '#suffix' => '</div>',
      );
    }
    else {
      $form['twig_file'] = array(
        "#type" => 'hidden',
        "#value" => array(
          'class_block' => 'block.php.twig',
        ),
      );
      $form['class_derivative_hidden'] = array(
        '#type' => 'hidden',
        '#prefix' => '<div id="config-derivative">',
        '#suffix' => '</div>',
      );
    }
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function config_callback(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('config_block') == 0) {
      return $form['simple_block_fieldset'];
    }
    else {
      return $form['config_block_fieldset'];
    }
  }

  public function derivative_callback(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('derivative')) {
      return $form['class_derivative'];
    }
    else {
      return $form['class_derivative_hidden'];
    }
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