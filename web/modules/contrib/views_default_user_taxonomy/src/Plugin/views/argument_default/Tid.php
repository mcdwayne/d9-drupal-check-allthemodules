<?php

/**
 * @file
 * Contains \Drupal\views_default_user_taxonomy\Plugin\views\argument_default\Tid.
 */

namespace Drupal\views_default_user_taxonomy\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\node\NodeInterface;
use Drupal\node\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\VocabularyStorageInterface;

/**
 * Taxonomy tid default argument.
 *
 * @ViewsArgumentDefault(
 *   id = "taxonomy_tid",
 *   title = @Translation("Taxonomy term ID from URL")
 * )
 */
class Tid extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface.
   */
  protected $vocabularyStorage;

  /**
   * Constructs a new Tid instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, VocabularyStorageInterface $vocabulary_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
    $this->vocabularyStorage = $vocabulary_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity.manager')->getStorage('taxonomy_vocabulary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // @todo Remove the legacy code.
    // Convert legacy vids option to machine name vocabularies.
    if (!empty($this->options['vids'])) {
      $vocabularies = taxonomy_vocabulary_get_names();
      foreach ($this->options['vids'] as $vid) {
        if (isset($vocabularies[$vid], $vocabularies[$vid]->machine_name)) {
          $this->options['vocabularies'][$vocabularies[$vid]->machine_name] = $vocabularies[$vid]->machine_name;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['term_page'] = array('default' => TRUE);
    $options['node'] = array('default' => FALSE);
    $options['user'] = array('default' => FALSE);
    $options['anyall'] = array('default' => ',');
    $options['limit'] = array('default' => FALSE);
    $options['vids'] = array('default' => array());

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['term_page'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Load default filter from term page'),
      '#default_value' => $this->options['term_page'],
    );
    $form['node'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Load default filter from node page, that\'s good for related taxonomy blocks'),
      '#default_value' => $this->options['node'],
    );
    $form['user'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Load default filter from user page, that\'s good for related taxonomy blocks'),
      '#default_value' => $this->options['user'],
    );

    $form['limit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Limit terms by vocabulary'),
      '#default_value' => $this->options['limit'],
      '#states' => array(
        'visible' => array(
          array(
            ':input[name="options[argument_default][taxonomy_tid][node]"]' => array('checked' => TRUE),
            'or',
            ':input[name="options[argument_default][taxonomy_tid][user]"]' => array('checked' => TRUE),
          ),
        ),
      ),
    );

    $options = array();
    $vocabularies = $this->vocabularyStorage->loadMultiple();
    foreach ($vocabularies as $voc) {
      $options[$voc->id()] = $voc->label();
    }

    $form['vids'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $this->options['vids'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[argument_default][taxonomy_tid][limit]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['anyall'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Multiple-value handling'),
      '#default_value' => $this->options['anyall'],
      '#options' => array(
        ',' => $this->t('Filter to items that share all terms'),
        '+' => $this->t('Filter to items that share any term'),
      ),
      '#states' => array(
        'visible' => array(
          array(
            ':input[name="options[argument_default][taxonomy_tid][node]"]' => array('checked' => TRUE),
            'or',
            ':input[name="options[argument_default][taxonomy_tid][user]"]' => array('checked' => TRUE),
          ),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state, &$options = array()) {
    // Filter unselected items so we don't unnecessarily store giant arrays.
    $options['vids'] = array_filter($options['vids']);
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    // Load default argument from taxonomy page.
    if (!empty($this->options['term_page'])) {
      if (($taxonomy_term = $this->routeMatch->getParameter('taxonomy_term')) && $taxonomy_term instanceof TermInterface) {
        return $taxonomy_term->id();
      }
    }

    // Start with a NULL entity to avoid warnings.
    $entity = NULL;

    // Load default argument from node.
    if (!empty($this->options['node'])) {
      // Just check, if a node could be detected.
      if (($node = $this->routeMatch->getParameter('node')) && $node instanceof NodeInterface) {
        $entity = $node;
      }
    }
    // Load default argument from user.
    if (!empty($this->options['user'])) {
      // Just check, if a user could be detected.
      if (($account = $this->routeMatch->getParameter('user')) && $account instanceof UserInterface) {
        $entity = $account;
      }
    }

    // Run the same code for nodes as users. This should work for other entity
    // types as well, as long as they contain an entity_reference field.
    if (!empty($entity)) {
      $taxonomy = array();
      foreach ($entity->getFieldDefinitions() as $field) {
        if ($field->getType() == 'entity_reference' && $field->getSetting('target_type') == 'taxonomy_term') {
          foreach ($entity->get($field->getName()) as $item) {
            if (($handler_settings = $field->getSetting('handler_settings')) && isset($handler_settings['target_bundles'])) {
              $taxonomy[$item->target_id] = reset($handler_settings['target_bundles']);
            }
          }
        }
      }
      if (!empty($this->options['limit'])) {
        $tids = array();
        // filter by vocabulary
        foreach ($taxonomy as $tid => $vocab) {
          if (!empty($this->options['vids'][$vocab])) {
            $tids[] = $tid;
          }
        }
        return implode($this->options['anyall'], $tids);
      }
      // Return all tids.
      else {
        return implode($this->options['anyall'], array_keys($taxonomy));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    foreach ($this->vocabularyStorage->loadMultiple(array_keys($this->options['vids'])) as $vocabulary) {
      $dependencies[$vocabulary->getConfigDependencyKey()][] = $vocabulary->getConfigDependencyName();
    }
    return $dependencies;
  }

}
