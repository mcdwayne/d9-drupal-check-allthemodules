<?php

namespace Drupal\taxonomy_machine_name\Plugin\views\argument_validator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Plugin\views\argument_validator\Entity;

/**
 * Validates whether a term machine name is a valid term argument.
 *
 * @ViewsArgumentValidator(
 *   id = "taxonomy_term_machine_name",
 *   title = @Translation("Taxonomy term machine name"),
 *   entity_type = "taxonomy_term"
 * )
 */
class TermMachineName extends Entity {

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityManagerInterface $entity_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
    // Not handling exploding term names.
    $this->multipleCapable = FALSE;
    $this->termStorage = $entity_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['transform'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['transform'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Transform dashes in URL to underscores in term name filter values'),
      '#default_value' => $this->options['transform'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    if ($this->options['transform']) {
      $argument = str_replace('-', '_', $argument);
    }
    $properties = ['machine_name' => $argument];
    if ($bundles = array_filter($this->options['bundles'])) {
      $properties['vid'] = $bundles;
    }
    $terms = $this->termStorage->loadByProperties($properties);

    if (!$terms) {
      // Returned empty array no terms with the name.
      return FALSE;
    }

    // Not knowing which term will be used if more than one is returned check
    // each one.
    /** @var Term $term */
    foreach ($terms as $term) {
      if (!$this->validateEntity($term)) {
        return FALSE;
      }
    }

    $this->argument->realField = 'tid';
    $this->argument->argument = $term->id();

    // Property created dynamically.
    if (!$this->argument->validated_title = $term->getName()) {
      $this->argument->validated_title = $this->t('No name');
    }

    return TRUE;
  }
}
