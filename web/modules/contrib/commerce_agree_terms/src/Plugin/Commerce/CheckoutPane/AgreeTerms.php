<?php

namespace Drupal\commerce_agree_terms\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Link;

/**
 * Provides the completion message pane.
 *
 * @CommerceCheckoutPane(
 *   id = "agree_terms",
 *   label = @Translation("Agree to the terms and conditions"),
 *   default_step = "review",
 * )
 */
class AgreeTerms extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'nid' => NULL,
        'link_text' => 'Terms and Conditions',
        'prefix_text' => 'I agree with the %terms',
        'invalid_text' => 'You must agree with the %terms before continuing',
        'new_window' => 1,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    $prefix = $this->configuration['prefix_text'];
    $link_text = $this->configuration['link_text'];
    $invalid_text = $this->configuration['invalid_text'];
    $new_window = $this->configuration['new_window'];
    $nid = $this->configuration['nid'];
    $summary = '';
    if (!empty($prefix)) {
      $summary = $this->t('Prefix text: @text', ['@text' => $prefix]) . '<br/>';
    }
    if (!empty($link_text)) {
      $summary .= $this->t('Link text: @text', ['@text' => $link_text]) . '<br/>';
    }
    if (!empty($invalid_text)) {
      $summary .= $this->t('Error text: @text', ['@text' => $invalid_text]) . '<br/>';
    }
    if (!empty($window_target)) {
      $window_text = ($new_window === 1) ? $this->t('New window') : $this->t('Same window');
      $summary .= $this->t('Window opens in: @opens', ['@text' => $window_text]) . '<br/>';
    }
    if (!empty($nid)) {
      $node = Node::load($nid);
      if ($node) {
        $summary .= $this->t('Terms page: @title', ['@title' => $node->getTitle()]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['prefix_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix text'),
      '#default_value' => $this->configuration['prefix_text'],
    ];
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $this->configuration['link_text'],
      '#required' => TRUE,
    ];
    $form['invalid_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invalid text'),
      '#default_value' => $this->configuration['invalid_text'],
    ];
    $form['new_window'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open window link in new window'),
      '#default_value' => $this->configuration['new_window'],
    ];
    if ($this->configuration['nid']) {
      $node = Node::load($this->configuration['nid']);
    }
    else {
      $node = NULL;
    }
    $form['nid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#default_value' => $node,
      '#required' => TRUE,
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
      $this->configuration['prefix_text'] = $values['prefix_text'];
      $this->configuration['link_text'] = $values['link_text'];
      $this->configuration['invalid_text'] = $values['invalid_text'];
      $this->configuration['new_window'] = $values['new_window'];
      $this->configuration['nid'] = $values['nid'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $prefix_text = $this->configuration['prefix_text'];
    $link_text = $this->configuration['link_text'];
    $nid = $this->configuration['nid'];
    if ($nid) {
      $node = Node::load($nid);
      $attributes = [];
      if ($this->configuration['new_window']) {
        $attributes = ['attributes' => ['target' => '_blank']];
      }
      $link = Link::createFromRoute(
        $this->t($link_text),
        'entity.node.canonical',
        ['node' => $nid],
        $attributes
      )->toString();
      if ($prefix_text) {
        $pane_form['terms_and_conditions'] = [
          '#type' => 'checkbox',
          '#default_value' => FALSE,
          '#title' => $this->t($prefix_text, ['%terms' => $link]),
          '#required' => TRUE,
          '#weight' => $this->getWeight(),
        ];
      }
      else {
        $pane_form['terms_and_conditions'] = [
          '#type' => 'checkbox',
          '#default_value' => FALSE,
          '#title' => $link,
          '#required' => TRUE,
          '#weight' => $this->getWeight(),
        ];
      }
    }
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    if (!$values['terms_and_conditions']) {
      $form_state->setError($pane_form, $this->configuration['invalid_text']);
    }
  }

}
