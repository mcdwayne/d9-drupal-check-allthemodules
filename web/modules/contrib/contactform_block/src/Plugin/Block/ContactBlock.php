<?php

/**
 * @file
 * Add a contact form to a block.
 */

namespace Drupal\drupalcreatecontactblock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add a contact form to a block.
 *
 * @Block(
 *   id = "Contact_Block",
 *   admin_label = @Translation("Contact Block"),
 * )
 */
class ContactBlock extends BlockBase {

  /**
   * Build the block with the content.
   *
   * @return mixed
   *   The Form based on the config.
   */
  public function build() {
    // Get the configuration.
    $config = $this->getConfiguration();
    // If the form_name is defined, use it. Otherwise use default 'feedback'.
    if (!empty($config['form_name'])) {
      $form_name = $config['form_name'];
    }
    else {
      $form_name = 'feedback';
    }
    // Load the form.
    $form = $this->loadForm($form_name);

    return $form;
  }

  /**
   * Show a config form for the specific block.
   *
   * @param array $form
   *   The Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State.
   *
   * @return array
   *   The Form with all it's properties.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    // Load the configuration, used in default_value.
    $config = $this->getConfiguration();
    // Load all available forms and add them as 'options'.
    // For each found form, add it as an option.
    $available_forms = \Drupal::entityManager()
                              ->getStorage('contact_form')
                              ->loadMultiple();
    $options         = array();
    foreach ($available_forms as $available_form) {
      if ($available_form->id() != 'personal') {
        $options[$available_form->id()] = $available_form->label();
      }
    }
    // Add a selectfield to the backend form.
    $form['contact_block_form_name'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Form'),
      '#description'   => $this->t('Select the Contact-form you want to show'),
      '#default_value' => isset($config['form_name']) ? $config['form_name'] : '',
      '#options'       => $options,
    );

    return $form;
  }

  /**
   * Save the value from the input to the form config.
   *
   * @param array $form
   *   The Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Set the 'form_name' to the value from 'contact_block_form_name'
    // (defined in blockForm())
    $this->setConfigurationValue('form_name',
                                 $form_state->getValue('contact_block_form_name'));
  }

  /**
   * Load the form based on the form name selected in the config page.
   *
   * @param string $form_name
   *   The name of the form.
   *
   * @return mixed
   *   Load the Form.
   */
  public function loadForm($form_name) {
    // Load the form with a specific $form_name.
    // Create the view for the form.
    $entity  = \Drupal::entityManager()
                      ->getStorage('contact_form')
                      ->load($form_name);
    $message = \Drupal::entityManager()
                      ->getStorage('contact_message')
                      ->create(array('contact_form' => $entity->id()));
    // Get the form based on the view defined above.
    $form = \Drupal::service('entity.form_builder')->getForm($message);

    return $form;
  }

}
