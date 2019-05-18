<?php

namespace Drupal\jira_rest\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\key\Entity\Key;

/**
 * Class JiraRestConfigForm.
 *
 * @package Drupal\jira_rest\Form
 */
class JiraRestConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jira_rest_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('jira_rest.settings');

    $form['instanceurl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL of the JIRA instance'),
      '#default_value' => $config->get('jira_rest.instanceurl'),
      '#description' => $this->t("Enter the URL of your JIRA instance (e.g. https://yourjira.com:8443)"),
      '#required' => TRUE,
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username of the default user to connect to JIRA'),
      '#default_value' => $config->get('jira_rest.username'),
      '#description' => $this->t("Enter the username used as default to connect to you JIRA instance (e.g. admin)"),
    ];

    $keys = [];
    /** @var \Drupal\key\Entity\Key $key */
    foreach (Key::loadMultiple() as $key) {
      $keys[$key->id()] = $key->label();
    }

    $form['password'] = [
      '#type' => 'select',
      '#title' => $this->t('Password Key of the default user to connect to JIRA'),
      '#options' => $keys,
      '#default_value' => $config->get('jira_rest.password'),
      '#description' => $this->t('Choose an available key. If the desired key is not listed, <a href=":link">create a new key</a>.', [
        ':link' => Url::fromRoute('entity.key.add_form')
          ->toString(),
      ]),
    ];

    $form['close_issue_transition_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('the default transition ID to close an issue'),
      '#default_value' => $config->get('jira_rest.close_issue_transition_id'),
      '#size' => 4,
      '#description' => $this->t("Enter the default transition ID to close an issue with jira_rest_closeissuefixed()"),
      '#required' => TRUE,
    ];

    $form['resolve_issue_transition_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('default transition ID to resolve an issue'),
      '#default_value' => $config->get('jira_rest.resolve_issue_transition_id'),
      '#size' => 4,
      '#description' => $this->t("Enter the default transition ID to resolve an issue with jira_rest_resolveissuefixed()"),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('jira_rest.settings');

    $formValues = $form_state->getValues();

    $jira_url = $formValues['instanceurl'];
    if ((strpos(strrev($jira_url), strrev('/')) === 0)) {
      $form_state->setErrorByName('instanceurl', $this->t('URL must not end with "/"'));
    }

    if (!is_numeric($formValues['close_issue_transition_id'])) {
      $form_state->setErrorByName('close_issue_transition_id', $this->t('Transition id must be a numeric value'));
    }

    if (!is_numeric($formValues['resolve_issue_transition_id'])) {
      $form_state->setErrorByName('resolve_issue_transition_id', $this->t('Transition id must be a numeric value'));
    }

    // CHECK may not be needed for d8, unsets userdata if username left empty.
    if (empty($formValues['username'])) {
      unset($formValues['username']);
      $config->clear('username');
      unset($formValues['password']);
      $config->clear('password');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('jira_rest.settings');

    $config->set('jira_rest.instanceurl', $form_state->getValue('instanceurl'));
    $config->set('jira_rest.username', $form_state->getValue('username'));
    if (!empty($form_state->getValue('password'))) {
      $config->set('jira_rest.password', $form_state->getValue('password'));
    }
    $config->set('jira_rest.close_issue_transition_id', $form_state->getValue('close_issue_transition_id'));
    $config->set('jira_rest.resolve_issue_transition_id', $form_state->getValue('resolve_issue_transition_id'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jira_rest.settings',
    ];
  }

}
