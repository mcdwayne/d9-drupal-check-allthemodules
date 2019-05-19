<?php

namespace Drupal\social_stats\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SocialStatsContentTypeForm
 * @package Drupal\social_stats\Form
 *
 * Social stats content types settings form.
 */
class SocialStatsContentTypeForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['social_stats.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_stats_content_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_types = NodeType::loadMultiple();
    $config_social_stats = $this->configFactory->get('social_stats.settings');

    // Loop over all the content types to build the config array per content-type.
    foreach ($node_types as $type) {
      $config = $config_social_stats->get('social_stats.content.types.' . $type->id());
      // error_log(print_r($config, TRUE));
      $form['social_stats'][$type->id()] = array(
        '#type' => 'details',
        '#title' => $type->label(),
        '#open' => TRUE,
      );
      $form['social_stats'][$type->id()]['social_stats_' . $type->id()] = array(
        '#type' => 'checkboxes',
        '#options' => array(
          'fb' => t('Facebook'),
          'twitter' => t('Twitter'),
          'gplus' => t('Google Plus'),
          'linkedin' => t('LinkedIn'),
        ),
        '#default_value' => empty($config)? array() : $config,
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $node_types = NodeType::loadMultiple();

    $config = $this->config('social_stats.settings');

    // Add new index to the config variable per content type.
    foreach ($node_types as $type) {
      error_log(print_r($values['social_stats_' . $type->id()], TRUE));

      $config->set('social_stats.content.types.' . $type->id(), $values['social_stats_' . $type->id()]);
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
