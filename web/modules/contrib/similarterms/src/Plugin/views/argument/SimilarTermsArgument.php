<?php

namespace Drupal\similarterms\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Argument handler to accept a node id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("similar_terms_arg")
 */
class SimilarTermsArgument extends NumericArgument implements ContainerFactoryPluginInterface {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * Constructs the SimilarTermsArgument object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Connection $connection
   *   The datbase connection.
   * @param VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, VocabularyStorageInterface $vocabulary_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->vocabularyStorage = $vocabulary_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('database'), $container->get('entity.manager')->getStorage('taxonomy_vocabulary')
    );
  }

  /**
   * Define default values for options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['vocabularies'] = array('default' => array());
    $options['include_args'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Build options settings form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    parent::buildOptionsForm($form, $form_state);
    $vocabularies = array();
    $result = $this->vocabularyStorage->loadMultiple();

    foreach ($result as $vocabulary) {
      $vocabularies[$vocabulary->id()] = $vocabulary->label();
    }

    $form['vocabularies'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Limit similarity to terms within these vocabularies'),
      '#description' => $this->t('Choosing any vocabularies here will limit the terms used to calculate similarity. It is usually best NOT to limit the terms, but in some cases this is necessary. Leave all checkboxes unselected to not limit terms.'),
      '#options' => $vocabularies,
      '#default_value' => empty($this->options['vocabularies']) ? array() : $this->options['vocabularies'],
    );

    $form['include_args'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include argument node(s) in results'),
      '#description' => $this->t('If selected, the node(s) passed as the argument will be included in the view results.'),
      '#default_value' => !empty($this->options['include_args']),
    );
  }

  /**
   * Validate this argument works. By default, all arguments are valid.
   */
  public function validateArgument($arg) {

    if (isset($this->argument_validated)) {
      return $this->argument_validated;
    }

    $this->value = array($arg => $arg);
    $vocabulary_vids = empty($this->options['vocabularies']) ? array() : $this->options['vocabularies'];

    foreach ($vocabulary_vids as $key => $val) {
      if ($val === 0) {
        unset($vocabulary_vids[$key]);
      }
    }

    $select = $this->connection->select('taxonomy_index', 'ti')->fields('ti', array('tid'));
    if (count($vocabulary_vids)) {
      $select->join('taxonomy_term_data', 'td', 'ti.tid = td.tid');
      $select->condition('td.vid', $vocabulary_vids, 'IN');
    }
    $select->condition('ti.nid', $this->value, 'IN');
    $result = $select->execute();

    $this->tids = array();
    foreach ($result as $row) {
      $this->tids[$row->tid] = $row->tid;
    }
    $this->view->tids = $this->tids;

    if (count($this->tids) == 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Add filter(s).
   */
  public function query() {
    $this->ensureMyTable();

    $this->query->addTable('taxonomy_index', NULL, NULL, 'similarterms_taxonomy_index');
    $this->query->addWhere(0, "similarterms_taxonomy_index.tid", $this->tids, 'IN');

    // Exclude the current node(s)
    if (empty($this->options['include_args'])) {
      $this->query->addWhere(0, "node.nid", $this->value, 'NOT IN');
    }
    $this->query->addGroupBy('nid');
  }

}
