<?php

namespace Drupal\user_chooser\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.context.free')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'uchoo_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('user_chooser.config');

    $form['threshhold'] = array(
      '#title' => t('Autocomplete threshhold'),
      '#description' => t('Below this number of items, a select box is shown, above it, an autocomplete field'),
      '#type' => 'number',
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $config->get('threshhold'),
      '#weight' => 1,
      '#min' => 0,
    );
    $form['sort'] = array(
      '#title' => t('Select widget - order by'),
      '#description' => t('The select field will show results in what order'),
      '#type' => 'radios',
      '#options' => array(
        0 => t('User ID'),
        1 => t('Alphabetical')
      ),
      '#default_value' => $config->get('sort'),
      '#weight' => 2
    );
    $form['matching'] = array(
      '#title' => t('Autocomplete matching'),
      '#type' => 'fieldset',
      'user_chooser_matching' => array(
        '#title' => t('Autocomplete widget - match against'),
        '#description' => t('The autocomplete widget will get matches based on which fields?') .' '. t('Only applies to permission role and conditions based widgets.'),
        '#type' => 'checkboxes',
        '#options' => array(
          'u.uid' => t('user ID'),
          'u.name' => t('username'),
          'u.mail' => t('email'),
        ),
        '#default_value' => $config->get('matching'),
        '#weight' => 3
      ),
      'user_chooser_match_offset' => array(
        '#title' => t('Offset'),
        '#type' => 'radios',
        '#options' => array(
          '' => 'string%',
          '%' => '%string%',
        ),
        '#default_value' => variable_get('user_chooser_match_offset', '')
      ),
      '#weight' => 3
    );
    $tokens = array('[user:name], [user:uid]');
    //TODO add other user tokens. no need to use the tokens module here
    $form['format'] = array(
      '#title' => t('Display format'),
      '#description' => t('Use at least one of the following tokens: @tokens', array('@tokens' => implode(',  ', $tokens))),
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('format'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('user_chooser.config')
      ->set('threshhold', $form_state['values']['threshhold'])
      ->set('sort', $form_state['values']['sort'])
      ->set('matching', array_filter($form_state['values']['matching']))
      ->set('format', $form_state['values']['format'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
