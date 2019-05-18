<?php

namespace Drupal\access_conditions_commerce;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides friendly methods for access conditions for commerce checkout panes.
 */
trait AccessConditionsCommerceCheckoutPaneTrait {

  use StringTranslationTrait;

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration() {
    return [
      'access_models' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * Commerce checkout pane configuration form constructor.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   *
   * @return array
   *   The form structure.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['access_models'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Visible to certain access models'),
      '#description' => $this->t('Leave blank for all users.'),
      '#target_type' => 'access_model',
      '#tags' => TRUE,
      '#default_value' => empty($this->configuration['access_models']) ? NULL : \Drupal::entityTypeManager()->getStorage('access_model')->loadMultiple($this->configuration['access_models']),
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['access_models'] = empty($values['access_models']) ? [] : array_column($values['access_models'], 'target_id');
    }
  }

  /**
   * Builds a summary of the pane configuration.
   *
   * @return string
   *   An HTML summary of the pane configuration.
   */
  public function buildConfigurationSummary() {
    $summary = parent::buildConfigurationSummary();

    if (!empty($this->configuration['access_models'])) {
      $summary .= '<br>' . $this->t('Visible to certain access models');
    }

    return $summary;
  }

  /**
   * Determines whether the pane is visible.
   *
   * @return bool
   *   TRUE if the pane is visible, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isVisible() {
    // If there are configured access models, we should evaluate them.
    if (count($this->configuration['access_models'])) {
      /** @var \Drupal\access_conditions\AccessChecker $access_checker */
      $access_checker = \Drupal::service('access_conditions.access_checker');

      /** @var \Drupal\access_conditions\Entity\AccessModelInterface[] $access_models */
      $access_models = \Drupal::entityTypeManager()->getStorage('access_model')->loadMultiple($this->configuration['access_models']);
      foreach ($access_models as $access_model) {
        // Return TRUE if access is granted.
        if ($access_checker->checkAccess($access_model)) {
          return TRUE;
        }
      }

      // If there are not granted access models, return FALSE.
      return FALSE;
    }

    // Otherwise just return the parent pane visibility value.
    return parent::isVisible();
  }

}
