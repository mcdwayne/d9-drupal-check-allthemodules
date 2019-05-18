<?php

namespace Drupal\powertagging\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure global settings of the Semantic Connector module..
 */
class PowerTaggingGlobalConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'powertagging_global_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'powertagging.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('powertagging.settings');

    $glossary_items_max_options = range(0, 10);
    $glossary_items_max_options[0] = 'Unlimited';

    $form['powertagging_tag_glossary'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('"PowerTagging Tag Glossary" block configuration'),
    );

    $form['powertagging_tag_glossary']['powertagging_tag_glossary_items_max'] = array(
      '#type' => 'select',
      '#options' => $glossary_items_max_options,
      '#title' => $this->t('Number of tag glossary items'),
      '#description' => $this->t('The maximum number of items to show in the "PowerTagging Tag Glossary" block'),
      '#default_value' => $config->get('tag_glossary_items_max'),
    );

    $form['powertagging_tag_glossary']['powertagging_tag_glossary_use_dbpedia_definition'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Fetch definitions from DBpedia'),
      '#description' => $this->t('If a concept has no definition but an exactMatch-entry to DBpedia, the abstract of the DBpedia entry will be used instead'),
      '#default_value' => $config->get('tag_glossary_use_dbpedia_definition'),
    );

    $form['powertagging_tag_glossary']['powertagging_tag_glossary_definition_max_characters'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum characters of definitions'),
      '#description' => $this->t('The maximum number of characters of a definition that will be displayed initially for each concept. If a definition is longer it will be cut on word-basis and ellipsis will be added.%linebreakLeave this field empty if you don\'t want the number of characters to be limited.', array('%linebreak' => new FormattableMarkup('<br />', array()))),
      '#default_value' => $config->get('tag_glossary_definition_max_characters'),
      '#element_validate' => array('::element_validate_integer_positive'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $this->config('powertagging.settings')
      // Set the submitted configuration setting
      ->set('tag_glossary_items_max', $form_state->getValue('powertagging_tag_glossary_items_max'))
      // You can set multiple configurations at once by making
      // multiple calls to set()
      ->set('tag_glossary_use_dbpedia_definition', $form_state->getValue('powertagging_tag_glossary_use_dbpedia_definition'))
      ->set('tag_glossary_definition_max_characters', $form_state->getValue('powertagging_tag_glossary_definition_max_characters'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  public function element_validate_integer_positive($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value <= 0)) {
      $form_state->setErrorByName($element, t('%name must be a positive integer.', array('%name' => $element['#title'])));
    }
  }
}
