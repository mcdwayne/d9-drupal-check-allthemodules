<?php

namespace Drupal\yamlform_mailchimp\Plugin\YamlFormHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\YamlFormHandlerBase;
use Drupal\yamlform\YamlFormSubmissionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form submission to MailChimp handler.
 *
 * @YamlFormHandler(
 *   id = "mailchimp",
 *   label = @Translation("MailChimp"),
 *   category = @Translation("MailChimp"),
 *   description = @Translation("Sends a form submission to a MailChimp list."),
 *   cardinality = \Drupal\yamlform\YamlFormHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\yamlform\YamlFormHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class YamlFormMailChimpHandler extends YamlFormHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('yamlform.mailchimp')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $lists = mailchimp_get_lists();
    return [
      '#theme' => 'markup',
      '#markup' => '<strong>' . $this->t('List') . ': </strong>' . (!empty($lists[$this->configuration['list']]) ? $lists[$this->configuration['list']]->name : ''),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'list' => '',
      'email' => '',
      'double_optin' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $lists = mailchimp_get_lists();

    $options = array();
    $options[''] = $this->t('- Select an option -');
    foreach ($lists as $list) {
      $options[$list->id] = $list->name;
    }

    $form['list'] = [
      '#type' => 'select',
      '#title' => $this->t('List'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['list'],
      '#options' => $options,
    ];

    $fields = $this->getYamlForm()->getElementsFlattenedAndHasValue();
    $options = array();
    $options[''] = $this->t('- Select an option -');
    foreach ($fields as $field_name => $field) {
      if ($field['#type'] == 'email') {
        $options[$field_name] = $field['#title'];
      }
    }

    $form['email'] = [
      '#type' => 'select',
      '#title' => $this->t('Email field'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['email'],
      '#options' => $options,
    ];

    $form['double_optin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Double opt-in'),
      '#default_value' => $this->configuration['double_optin'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(YamlFormSubmissionInterface $yamlform_submission, $update = TRUE) {
    if (!$update) {
      $fields = $yamlform_submission->toArray(TRUE);
      $sendFields = array();
      foreach ($fields['data'] as $field_name => $field) {
        $sendFields[strtoupper($field_name)] = $field;
      }
      $email = $fields['data'][$this->configuration['email']];
      mailchimp_subscribe($this->configuration['list'], $email, $sendFields, array(), $this->configuration['double_optin']);
    }
  }

}
