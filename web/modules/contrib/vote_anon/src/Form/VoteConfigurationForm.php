<?php

namespace Drupal\vote_anon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Class VoteConfigurationForm.
 */
class VoteConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vote_anon.voteconfiguration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vote_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_types = NodeType::loadMultiple();
    $options = ['- None -'];
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }
    $config = $this->config('vote_anon.voteconfiguration');
    $form['message_after_voting'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message After Voting'),
      '#description' => $this->t('Show a thank you message after voting'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => ($config->get('message_after_voting')) ? $config->get('message_after_voting') : $this->t('Thank you for your vote.'),
    ];
    $form['warning_for_duplicate_voting'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Warning for duplicate voting'),
      '#description' => $this->t('Show a warning message that you have already vote'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => ($config->get('warning_for_duplicate_voting')) ? $config->get('warning_for_duplicate_voting') : $this->t('You have already submitted.'),
    ];
    $form['voting_cookie'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Voting Session Cookie'),
      '#description' => $this->t('Voting cookie is stored in temporary memory and is not retained after the browser is closed.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => ($config->get('voting_cookie')) ? $config->get('voting_cookie') : $this->t('VOTEANON'),
    ];
    $form['single_node_voting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Single Node Voting'),
      '#default_value' => $config->get('single_node_voting'),
    ];
    $form['diable_vote_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable link after vote'),
      '#default_value' => $config->get('diable_vote_link'),
    ];
    $form['vote_content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#options' => $options,
      '#default_value' => $config->get('vote_content_type'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('vote_anon.voteconfiguration')
      ->set('message_after_voting', $form_state->getValue('message_after_voting'))
      ->set('warning_for_duplicate_voting', $form_state->getValue('warning_for_duplicate_voting'))
      ->set('voting_cookie', $form_state->getValue('voting_cookie'))
      ->set('single_node_voting', $form_state->getValue('single_node_voting'))
      ->set('diable_vote_link', $form_state->getValue('diable_vote_link'))
      ->set('vote_content_type', $form_state->getValue('vote_content_type'))
      ->save();
  }

}
