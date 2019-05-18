<?php

namespace Drupal\uc_dropdown_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Testing for Drodown Attributes UI.
 */
class DropdownTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_dropdown_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['prefix'] = array(
      '#markup' => '<p>This is used for testing only and should not be enabled on a production website.  Since simpletest has limited Javascript capabilities this tests appearance of hidden attributes and removal of options when the attributes are deselected. Products, product classes and product kits can all be tested for four different types of input fields. For each one, a test node is created for the test.  These nodes will need to be removed manually after testing.</p><p>The customer option tests a customer choosing attributes.  The store option test the order edit form where a store employee changes or creates an order.</p>',
    );
    $options = array(
      'customer' => t('Customer'),
      'store' => t('Store'),
    );
    $form['user'] = array(
      '#type' => 'radios',
      '#title' => t('User'),
      '#options' => $options,
      '#required' => TRUE,
    );
    $options = array(
      'select' => t('Select'),
      'radios' => t('Radios'),
      'checkboxes' => t('Checkboxes'),
      'textfield' => t('Textfield'),
    );
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('Type'),
      '#options' => $options,
      '#required' => TRUE,
    );
    $form['product'] = array(
      '#type' => 'button',
      '#value' => t('Product'),
    );
    $form['class'] = array(
      '#type' => 'button',
      '#value' => t('Class'),
    );
    $form['kit'] = array(
      '#type' => 'button',
      '#value' => t('Product Kit'),
    );
    $form['#attached']['library'][] = 'uc_dropdown_test/drupal.uc_dropdown_test';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
