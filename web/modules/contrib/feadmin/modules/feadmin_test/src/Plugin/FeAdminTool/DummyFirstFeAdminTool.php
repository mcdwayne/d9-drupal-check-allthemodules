<?php

/**
 * @file
 * Contains \Drupal\feadmin_block\Plugin\FeAdminTool\BlockFeAdminTool.
 * 
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin_test\Plugin\FeAdminTool;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\feadmin\FeAdminTool\FeAdminToolBase;

/**
 * Provides a first dummy front-end administration tool.
 *
 * @FeAdminTool(
 *   id = "feadmin_dummy_first",
 *   label = @Translation("Dummy tool 1"),
 *   description = @Translation("Dummy 1 Front-End Administration tool, used for testing purpose.")
 * )
 */
class DummyFirstFeAdminTool extends FeAdminToolBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      'dummy_label' => array(
        '#type' => 'select',
        '#title' => $this->t('Dummy select'),
        '#options' => array($this->t('-- empty --')),
        '#description' => $this->t('A dummy select configuration.'),
      ),
    );
  }

  /**
   * Form constructor.
   *
   * Plugin forms are embedded in other forms. In order to know where the plugin
   * form is located in the parent form, #parents and #array_parents must be
   * known, but these are not available during the initial build phase. In order
   * to have these properties available when building the plugin form's
   * elements, let this method return a form element that has a #process
   * callback and build the rest of the form in the callback. By the time the
   * callback is executed, the element's #parents and #array_parents properties
   * will have been set by the form API. For more documentation on #parents and
   * #array_parents, see \Drupal\Core\Render\Element\FormElement.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = \Drupal::config('feadmin_test.dummy.feadmin_dummy_first');
    $form = array(
      'dummy_label' => array(
        '#type' => 'textfield',
        '#title' => $this->t('Dummy label'),
        '#default_value' => $settings->get('dummy_label'),
        '#description' => $this->t('A dummy label configuration.'),
      ),
      'dummy_option' => array(
        '#type' => 'checkbox',
        '#title' => $this->t('Dummy option'),
        '#default_value' => $settings->get('dummy_option'),
        '#description' => $this->t('A dummy option configuration.'),
      ),
    );
    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('feadmin_dummy_first');
    if (stripos($values['dummy_label'], 'dummy') === FALSE) {
      $form_state->setErrorByName('dummy_label', 'Dummy tool 1: Your label must contain the word dummy.');
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $settings = \Drupal::configFactory()->getEditable('feadmin_test.dummy.feadmin_dummy_first');

    // Retrieve values.
    $values = $form_state->getValue('feadmin_dummy_first');

    // Set values.
    $settings
      ->set('dummy_label', $values['dummy_label'])
      ->set('dummy_option', $values['dummy_option'])
      ->save();
  }
}
