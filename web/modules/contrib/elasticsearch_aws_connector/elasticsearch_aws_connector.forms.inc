<?php

/**
 * @file
 * Elasticsearch AWS Connector form functionality.
 */

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Using hook_form_elasticsearch_cluster_form_alter().
 */
function elasticsearch_aws_connector_form_elasticsearch_cluster_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  $cluster = $form['cluster']['#value'];

  // Add our AWS Signed Requests authentication type to the form.
  if (isset($form['options']['authentication_type'])) {
    $form['options']['authentication_type']['#options'][ELASTICSEARCH_AWS_CONNECTOR_AWS_SR_KEY] = t('Amazon Web Services - signed requests');
  }

  // Hide username field when the "AWS signed requests" options is selected.
  $form['options']['username']['#states']['invisible'][':input[name="options[authentication_type]"]'] = ['value' => ELASTICSEARCH_AWS_CONNECTOR_AWS_SR_KEY];
  // Hide password field when the "AWS signed requests" options is selected.
  $form['options']['password']['#states']['invisible'][':input[name="options[authentication_type]"]'] = ['value' => ELASTICSEARCH_AWS_CONNECTOR_AWS_SR_KEY];

  // Show AWS region field when the "AWS signed requests" options is selected.
  $form['options']['elasticsearch_aws_connector_aws_region'] = [
    '#type'          => 'textfield',
    '#title'         => t('Amazon Web Services - region'),
    '#description'   => t('The region of the AWS cluster.'),
    '#default_value' => (!empty($cluster->options['elasticsearch_aws_connector_aws_region']) ? $cluster->options['elasticsearch_aws_connector_aws_region'] : ''),
    '#states'        => [
      'visible' => [
        ':input[name="options[authentication_type]"]' => ['value' => ELASTICSEARCH_AWS_CONNECTOR_AWS_SR_KEY],
      ],
    ],
    '#weight'        => 5.4,
  ];

  $form['options']['elasticsearch_aws_connector_aws_authentication_type'] = [
    '#type'          => 'select',
    '#title'         => t('AWS authentication type'),
    '#description'   => t('Fallback to "AWS IAM Role".'),
    '#default_value' => (!empty($cluster->options['elasticsearch_aws_connector_aws_authentication_type']) ? $cluster->options['elasticsearch_aws_connector_aws_authentication_type'] : 'aws_role'),
    '#options'       => [
      'aws_credentials' => t('AWS credentials'),
      'aws_role'        => t('AWS IAM Role'),
    ],
    '#states'        => [
      'visible' => [
        ':input[name="options[authentication_type]"]' => ['value' => ELASTICSEARCH_AWS_CONNECTOR_AWS_SR_KEY],
      ],
    ],
    '#weight'        => 5.5,
  ];

  $form['options']['elasticsearch_aws_connector_aws_credentials_key'] = [
    '#type'          => 'textfield',
    '#title'         => t('AWS credentials key'),
    '#default_value' => (!empty($cluster->options['elasticsearch_aws_connector_aws_credentials_key']) ? $cluster->options['elasticsearch_aws_connector_aws_credentials_key'] : ''),
    '#states'        => [
      'visible' => [
        ':input[name="options[authentication_type]"]'                                 => ['value' => ELASTICSEARCH_AWS_CONNECTOR_AWS_SR_KEY],
        ':input[name="options[elasticsearch_aws_connector_aws_authentication_type]"]' => ['value' => 'aws_credentials'],
      ],
    ],
    '#weight'        => 5.5,
  ];

  $form['options']['elasticsearch_aws_connector_aws_credentials_secret'] = [
    '#type'          => 'textfield',
    '#title'         => t('AWS credentials secret'),
    '#default_value' => (!empty($cluster->options['elasticsearch_aws_connector_aws_credentials_secret']) ? $cluster->options['elasticsearch_aws_connector_aws_credentials_secret'] : ''),
    '#states'        => [
      'visible' => [
        ':input[name="options[authentication_type]"]'                                 => ['value' => ELASTICSEARCH_AWS_CONNECTOR_AWS_SR_KEY],
        ':input[name="options[elasticsearch_aws_connector_aws_authentication_type]"]' => ['value' => 'aws_credentials'],
      ],
    ],
    '#weight'        => 5.5,
  ];

  $form['#validate'][] = 'elasticsearch_aws_connector_form_elasticsearch_cluster_form_validate';
}

/**
 * Form validation for Elasticsearch AWS Connector form.
 *
 * @see elasticsearch_aws_connector_form_elasticsearch_cluster_form_alter()
 */
function elasticsearch_aws_connector_form_elasticsearch_cluster_form_validate(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $options = $form_state->getValue('options');

  if (!empty($options['elasticsearch_aws_connector_aws_region'])) {
    // Lowercase & remove whitespace from AWS region.
    $aws_region = strtolower(trim($options['elasticsearch_aws_connector_aws_region']));
    $options['elasticsearch_aws_connector_aws_region'] = $aws_region;
  }

  $form_state->setValue('options', $options);
}
