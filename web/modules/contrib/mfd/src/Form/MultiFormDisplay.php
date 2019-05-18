<?php
/**
 * Created by PhpStorm.
 * User: wilco
 * Date: 2017-10-23
 * Time: 5:05 PM
 */

/**
 * @file
 * Contains \Drupal\mfd\Form\MultiFormDisplay.
 */
namespace Drupal\mfd\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MultiFormDisplay extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mfd_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = array()) {


    $form['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}