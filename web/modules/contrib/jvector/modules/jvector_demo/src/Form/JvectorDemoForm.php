<?php
/**
 * @file
 * Contains \Drupal\jvector_demo\Form\JvectorDemoForm.
 */

namespace Drupal\jvector_demo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Contribute form.
 */
class JvectorDemoForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jvector_demo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['demo1_select'] = array(
      '#type' => 'select',
      //'#multiple' => true,
      '#title' => $this->t('In which region of Denmark have you recently been?'),
      '#description' => $this->t('Select as many as you want.'),
      '#jvector' => entity_load('jvector','jvector-demo-denmark'),
      '#jvector_config' => 'default',
      '#jvector_admin' => 'jvector',
      '#options' => entity_load('jvector','jvector-demo-denmark')->getJvectorPathsAsSelect('default'),
    );
    $form['demo2_select'] = array(
      '#type' => 'select',
      '#multiple' => true,
      '#title' => $this->t('In which regions of Austria have you been?'),
      '#description' => $this->t('Select as many as you want.'),
      '#jvector' => entity_load('jvector','jvector-demo-austria'),
      '#jvector_config' => 'default',
      '#jvector_admin' => 'jvector',
      '#options' => entity_load('jvector','jvector-demo-austria')->getJvectorPathsAsSelect('default'),
    );
    $form['demo3_select'] = array(
      '#type' => 'select',
      '#multiple' => true,
      '#title' => $this->t('In which countries have you been?'),
      '#description' => $this->t('Select as many as you want.'),
      '#jvector' => entity_load('jvector','jvector-demo-world-map'),
      '#jvector_config' => 'default',
      '#jvector_admin' => 'jvector',
      '#options' => entity_load('jvector','jvector-demo-world-map')->getJvectorPathsAsSelect('default'),
    );
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
    drupal_set_message('submitted');
  }
}

?>