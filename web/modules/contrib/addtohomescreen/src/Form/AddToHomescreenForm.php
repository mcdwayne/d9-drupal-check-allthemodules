<?php

/**
 * @file
 * Contains \Drupal\language\Form\NegotiationUrlForm.
 */

namespace Drupal\addtohomescreen\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Add to Homescreen.
 */
class AddToHomescreenForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'addtohomescreen_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['addtohomescreen.settings'];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('addtohomescreen.settings');

    $form['library'] = array(
      '#type' => 'details',
      '#title' => $this->t('Configuration'),
      '#description' => $this->t('For more information about these options, visit <a href="@addtohomescreen_url">Add to homescreen on Github</a>.',
        array('@addtohomescreen_url' => 'https://github.com/cubiq/add-to-homescreen')
      ),
      '#open' => TRUE,
    );
    $form['library']['debug'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#description' => $this->t('Some of the preliminary checks are skipped and the message is shown on desktop browsers and unsupported devices as well.'),
      '#default_value' => $config->get('debug'),
    );
    $form['library']['modal'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Modal'),
      '#description' => $this->t('Prevents further actions on the website until the message is closed.'),
      '#default_value' => $config->get('modal'),
    );
    $form['library']['mandatory'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Mandatory'),
      '#description' => $this->t('The website is not accessible until the user adds the website to the homescreen.'),
      '#default_value' => $config->get('mandatory'),
    );
    $form['library']['skipfirstvisit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Skip first visit'),
      '#description' => $this->t('Prevent the message from appearing the first time the user visits your website. It is highly recommended to enable this option!'),
      '#default_value' => $config->get('skipfirstvisit'),
    );
    $form['library']['autostart'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Autostart'),
      '#description' => $this->t('The message is not shown automatically and you have to trigger it programmatically.'),
      '#default_value' => $config->get('autostart'),
    );
    $form['library']['icon'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Icon'),
      '#description' => $this->t('Display the touch icon in the pop up message.'),
      '#default_value' => $config->get('icon'),
    );
    $form['library']['startdelay'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Start delay'),
      '#description' => $this->t('Seconds to wait from page load before showing the message.'),
      '#default_value' => $config->get('startdelay'),
      '#size' => 10,
    );
    $form['library']['lifespan'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Lifespan'),
      '#description' => $this->t('Seconds to wait before automatically closing the message. Set to 0 to disable automatic removal.'),
      '#default_value' => $config->get('lifespan'),
      '#size' => 10,
    );
    $form['library']['displaypace'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Display pace'),
      '#description' => $this->t('Minutes before the message is shown again. By default it is set to 1440, meaning the message is shown once per day.'),
      '#default_value' => $config->get('displaypace'),
      '#size' => 10,
    );
    $form['library']['maxdisplaycount'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum display count'),
      '#description' => $this->t('Absolute maximum number of times the call out will be shown. Set to 0 for no maximum.'),
      '#default_value' => $config->get('maxdisplaycount'),
      '#size' => 10,
    );
    $form['library']['use_custom_message'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use a custom message'),
      '#description' => $this->t('Add to homescreen comes with a localized and device specific message. You can override this message with your own.'),
      '#default_value' => $config->get('use_custom_message'),
    );
    $form['library']['message'] = array(
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#default_value' => $config->get('message', t('To add this web app to the home screen: tap %icon and then <strong>Add to homescreen</strong>.')),
      '#description' => $this->t('Available replacements: %icon'),
      '#states' => array(
        'disabled' => array(
          ':input[name="use_custom_message"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['compression_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Library compression settings'),
      '#collapsible' => TRUE,
      '#open' => FALSE,
    );
    $form['compression_settings']['compression_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Choose compression level'),
      '#options' => array(
        'minified' => $this->t('Production (Minified)'),
        'source' => $this->t('Development (Uncompressed Code)'),
      ),
      '#default_value' => $config->get('compression_type', 'minified'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('addtohomescreen.settings')
      ->set('debug', $form_state->getValue('debug'))
      ->set('modal', $form_state->getValue('modal'))
      ->set('mandatory', $form_state->getValue('mandatory'))
      ->set('skipfirstvisit', $form_state->getValue('skipfirstvisit'))
      ->set('autostart', $form_state->getValue('autostart'))
      ->set('icon', $form_state->getValue('icon'))
      ->set('startdelay', $form_state->getValue('startdelay'))
      ->set('lifespan', $form_state->getValue('lifespan'))
      ->set('displaypace', $form_state->getValue('displaypace'))
      ->set('maxdisplaycount', $form_state->getValue('maxdisplaycount'))
      ->set('use_custom_message', $form_state->getValue('use_custom_message'))
      ->set('message', $form_state->getValue('message'))
      ->set('compression_type', $form_state->getValue('compression_type'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
