<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Settings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * User register settings check
 *
 * @ProdCheck(
 *   id = "user_register",
 *   title = @Translation("User registration"),
 *   category = "settings",
 * )
 */
class UserRegister extends ProdCheckBase {

  /**
   * The currently selected option
   */
  protected $current;

  /**
   * All the possible options
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function init() {
    $this->current = $this->configFactory->get('user.settings')->get('register');

    $this->options = [
      USER_REGISTER_ADMINISTRATORS_ONLY => $this->t('Administrators only'),
      USER_REGISTER_VISITORS => $this->t('Visitors'),
      USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL => $this->t('Visitors, but administrator approval is required'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    $options = $this->configuration['options'];
    return !empty($options[$this->current]);
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->options[$this->current],
      'description' => $this->generateDescription(
        $this->title(),
        'entity.user.admin_form'
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    $link_array = $this->generateLinkArray($this->title(), 'entity.user.admin_form');

    return [
      'value' => $this->options[$this->current],
      'description' => $this->t(
        'Your %link settings are set to "@current". Are you sure this is what you want and did not mean to use @options? With improperly setup access rights, this can be dangerous...',
        [
          '%link' => implode($link_array),
          '@current' => $this->options[$this->current],
          '@options' => '"' . implode('" ' . t('or') . ' "', $this->getSelectedOptions()) . '"',
        ]
      )
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['options'] = [
      USER_REGISTER_ADMINISTRATORS_ONLY => USER_REGISTER_ADMINISTRATORS_ONLY,
      USER_REGISTER_VISITORS => 0,
      USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL => USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['options'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Valid user registration options'),
      '#default_value' => $this->configuration['options'],
      '#options' => $this->options,
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['options'] = $form_state->getValue('options');
  }

  /**
   * Fetches all the selected options.
   */
  protected function getSelectedOptions() {
    $selected_options = [];
    foreach ($this->configuration['options'] as $option) {
      if (!empty($option)) {
        $selected_options[] = $this->options[$option];
      }
    }

    return $selected_options;
  }

}
