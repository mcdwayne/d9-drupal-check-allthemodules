<?php

namespace Drupal\bibcite_crossref\Form;

use Drupal\bibcite\Plugin\BibciteFormat;
use Drupal\bibcite_entity\Form\MappingForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Mapping form for Crossref pseudo format.
 */
class CrossrefMappingForm extends MappingForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bibcite_format = NULL) {
    $config_name = sprintf('bibcite_entity.mapping.%s', 'crossref');
    $this->config = $this->configFactory()->getEditable($config_name);

    $types = $this->config->get('types');
    $type_keys = array_keys($types);
    $fields = $this->config->get('fields');
    $fields_keys = array_keys($fields);


    $definition = [
      'id' => 'crossref',
      'label' => $this->t('Crossref'),
      'types' => $type_keys,
      'fields' => $fields_keys,
      'provider' => 'bibcite_entity',
    ];

    $bibcite_format = new BibciteFormat([], 'json', $definition);

    return parent::buildForm($form, $form_state, $bibcite_format);
  }

}
