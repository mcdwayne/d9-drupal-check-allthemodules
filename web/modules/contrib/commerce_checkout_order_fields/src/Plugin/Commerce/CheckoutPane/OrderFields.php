<?php

namespace Drupal\commerce_checkout_order_fields\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exposes the "Checkout" form view mode during checkout.
 *
 * @CommerceCheckoutPane(
 *   id = "order_fields",
 *   label = @Translation("Order fields"),
 *   deriver = "\Drupal\commerce_checkout_order_fields\Plugin\Derivative\OrderFieldsPaneDeriver"
 * )
 */
class OrderFields extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'wrapper_element' => 'container',
      'display_label' => $this->pluginDefinition['display_label'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    $summary[] = $this->t('<p>Wrapper element: :type</p>', [
      ':type' => ucfirst($this->configuration['wrapper_element']),
    ]);
    $summary[] = $this->t('<p>Display label: :label</p>', [
      ':label' => ucfirst($this->configuration['display_label']),
    ]);

    return implode('', $summary);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['wrapper_element'] = [
      '#type' => 'radios',
      '#title' => $this->t('Wrapper element'),
      '#options' => [
        'container' => $this->t('Container'),
        'fieldset' => $this->t('Fieldset'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['wrapper_element'],
    ];
    $form['display_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display label'),
      '#description' => $this->t('This is the display used when the wrapper element is a fieldset'),
      '#default_value' => $this->configuration['display_label'],
      '#states' => [
        'visible' => [
          ':input[name="configuration[panes][' . $this->getPluginId() . '][configuration][wrapper_element]"]' => ['value' => 'fieldset'],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['wrapper_element'] = $values['wrapper_element'];
      $this->configuration['display_label'] = $values['display_label'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWrapperElement() {
    return $this->configuration['wrapper_element'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    return $this->configuration['display_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $this->getFormDisplay()->buildForm($this->order, $pane_form, $form_state);
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $this->getFormDisplay()->validateFormValues($this->order, $pane_form, $form_state);
    parent::validatePaneForm($pane_form, $form_state, $complete_form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $this->getFormDisplay()->extractFormValues($this->order, $pane_form, $form_state);
    parent::submitPaneForm($pane_form, $form_state, $complete_form);
  }

  /**
   * Gets the form.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The form display.
   */
  private function getFormDisplay() {
    $display = EntityFormDisplay::collectRenderDisplay($this->order, $this->getDerivativeId());
    $display->removeComponent('coupons');
    return $display;
  }

}
