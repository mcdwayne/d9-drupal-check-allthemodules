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


class GeneratorInstall extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'install';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create module.install file in D8' . '</h2>'),
      "#weight" => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('generator.install.yml.twig'),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['hook_requirements'] = array(
      '#type' => 'checkbox',
      '#title' => t('Generate hook_requirements'),
      '#default_value' => '0',
      '#description' => t('Shows general error or warnings on the Drupal status page or during module installation.'),
    );
    $form['hook_schema'] = array(
      '#type' => 'checkbox',
      '#title' => t('Generate hook_schema '),
      '#default_value' => '1',
      '#description' => t('Declares specific tables in the database for your module.'),
    );
    $form['hook_install'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Generate hook_install '),
      '#default_value' => '0',
      '#description' => t('Performs some action against the database during module installation.'),
    );
    $form['hook_install_detail'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('hook_install'),
      '#collapsible' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="hook_install"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['hook_install_detail']['weight_module'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Change Module\'Weight'),
      '#default_value' => '0',
      '#description' => $this->t('Blocks are used for inheritance and act as placeholders and replacements at the same time.Ex.  {% block head %}   {% endblock %}'),
    );
    $form['hook_uninstall'] = array(
      '#type' => 'checkbox',
      '#title' => t('Generate hook_uninstall'),
      '#default_value' => '1',
      '#description' => t('Performs some action against the database while uninstalling the module.'),
    );
    $form['hook_update_N'] = array(
      '#type' => 'checkbox',
      '#title' => t('Generate hook_update_N'),
      '#default_value' => '0',
      '#description' => t('Performs database updates when upgrading Drupal to a new version.'),
    );
    $form = parent::buildForm($form, $form_state);
    return $form;
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
