<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\generator_ui\Controller\GeneratorController;

class GeneratorWatchDog extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_watchdog';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your watchdog in D8' . '</h2>'),
      '#weight' => -3
    );

      $form['twig_file'] = array(
        "#type" => 'hidden',
        "#value" => array(
          'logger.module.yml.twig',
        ),
      );
      $form['method_watchdog'] = array(
        '#type' => 'radios',
        '#options' => array('notice' => 'notice', 'error' => 'error'),
        '#title' => t('The method which you want to use'),
        '#description' => t('This example wil be visible <b style="color: #ff0000">after running a cron</b>, you can watch your recent log messages!'),
        '#required' => TRUE,
      );
    // dpm($form_state->getValue('options_action'));
    $form = parent::buildForm($form, $form_state);
    return $form;
  }



}