<?php

namespace Drupal\events_logging\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'events_logging.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('events_logging.config');
    $potential_entities = $this->getPotentialEntities();
    $form['enabled_content_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled content entities'),
      '#description' => $this->t('Choose the content entities you want to log'),
      '#options' => $potential_entities['content_entities'],
      '#default_value' => $config->get('enabled_content_entities') ? $config->get('enabled_content_entities') : [],
    ];
    $form['enabled_config_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled config entities'),
      '#description' => $this->t('Choose the config entities you want to log'),
      '#options' => $potential_entities['config_entities'],
      '#default_value' => $config->get('enabled_config_entities') ? $config->get('enabled_config_entities') : [],
    ];
    $form['enabled_events_logging_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled events logging plugins'),
      '#description' => $this->t('Choose the events logging plugin'),
      '#options' => $this->getPotentialPlugins(),
      '#default_value' => $config->get('enabled_events_logging_plugins') ? $config->get('enabled_events_logging_plugins') : [],
    ];
    $form['events_logging_max_records'] = [
      '#type' => 'select',
      '#title' => t('Eventlog messages to keep'),
      '#description' => $this->t('The maximum number of messages to keep in the database event log. Requires a cron maintenance task.'),
      '#options' => array(
        0 => t('All'),
        1000 => '1000',
        2500 => '2500',
        5000 => '5000',
        10000 => '10000',
        25000 => '25000',
        50000 => '50000',
        100000 => '100000',
        250000 => '250000',
        500000 => '500000',
        1000000 => '1000000',
      ),
      '#default_value' => $config->get('max_records') ? $config->get('max_records') : 0,
      '#required' => FALSE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('events_logging.config')
      ->set('enabled_content_entities', $form_state->getValue('enabled_content_entities'))
      ->set('enabled_config_entities', $form_state->getValue('enabled_config_entities'))
      ->set('max_records', $form_state->getValue('events_logging_max_records'))
      ->set('enabled_events_logging_plugins',$form_state->getValue('enabled_events_logging_plugins'))
      ->save();
  }

  /**
   * Obtains potential content entities to log
   * @return array
   */
  private function getPotentialEntities(){
    $content_entity_types = [];
    $config_entity_types = [];
    $entity_type_definitions = \Drupal::entityTypeManager()->getDefinitions();
    /* @var $definition EntityTypeInterface */
    foreach ($entity_type_definitions as $definition) {
      $id = $definition->id();
      if($id == 'events_logging'){
        continue;
      }
      if ($definition instanceof ContentEntityType) {
        $content_entity_types[$id] = $definition->getLabel();
      } else {
        $config_entity_types[$id] = $definition->getLabel();
      }
    }
    return [
      'content_entities' => $content_entity_types,
      'config_entities' => $config_entity_types
    ];
  }

  private function getPotentialPlugins(){
    $definitions = [];
    $type = \Drupal::service('plugin.manager.events_logging_storage_backend');
    $plugin_definitions = $type->getDefinitions();
    foreach($plugin_definitions as $machine_name => $definition){
        $definitions[$machine_name] = $definition['label'];
    }
    return $definitions;
  }
}
