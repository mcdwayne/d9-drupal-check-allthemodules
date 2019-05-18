<?php

namespace Drupal\carto_sync\Plugin\views\display;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The plugin that handles CARTO Sync.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "carto_sync",
 *   theme = "views_view",
 *   title = @Translation("CARTO Sync"),
 *   help = @Translation("Publish Drupal data to CARTO."),
 *   uses_route = FALSE,
 *   admin = @Translation("CARTO Sync"),
 *   returns_response = FALSE
 * )
 */
class CartoSync extends DisplayPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAJAX.
   */
  protected $usesAJAX = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesPager.
   */
  protected $usesPager = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesMore.
   */
  protected $usesMore = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAreas.
   */
  protected $usesAreas = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesOptions.
   */
  protected $usesOptions = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'carto_sync';
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();

    if (!$this->hasGeoField()) {
      $errors[] = $this->t('Display "@display" requires at least one field of type "Geofield".', ['@display' => $this->display['display_title']]);
    }

    if (!$this->getOption('dataset_name')) {
      $errors[] = $this->t('Display "@display" requires a dataset name.', ['@display' => $this->display['display_title']]);
    }

    if (empty($this->plugins['style']['carto_sync']->options['primary_key'])) {
      $errors[] = $this->t('Display "@display" requires a primary key.', ['@display' => $this->display['display_title']]);
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    parent::execute();

    return $this->view->render();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = $this->view->style_plugin->render($this->view->result);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#markup' => $this->t('Carto Sync display is not available on preview mode'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultableSections($section = NULL) {
    $sections = parent::defaultableSections($section);

    if (in_array($section, ['style', 'row'])) {
      return FALSE;
    }

    // Tell views our sitename_title option belongs in the title section.
    if ($section == 'title') {
      $sections[] = 'sitename_title';
    }
    elseif (!$section) {
      $sections['title'][] = 'sitename_title';
    }
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['displays'] = ['default' => []];

    // Overrides for standard stuff.
    $options['style']['contains']['type']['default'] = 'carto_sync';
    $options['style']['contains']['options']['default'] = ['description' => ''];
    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['access'] = FALSE;

    $options['access']['contains'] = [
      'type' => [
        'default' => 'perm',
      ],
      'options' => [
        'default' => [
          'perm' => 'administer carto_sync',
        ],
      ],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function newDisplay() {
    parent::newDisplay();

    // Set the default row style. Ideally this would be part of the option
    // definition, but in this case it's dependent on the view's base table,
    // which we don't know until init().
    if (empty($this->options['row']['type']) || $this->options['row']['type'] === 'rss_fields') {
      $row_plugins = Views::fetchPluginNames('row', $this->getType(), [$this->view->storage->get('base_table')]);
      $default_row_plugin = key($row_plugins);

      $options = $this->getOption('row');
      $options['type'] = $default_row_plugin;
      $this->setOption('row', $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // Hide access options, once are hardcoded.
    unset($options['access']);

    $dataset = strip_tags($this->getOption('dataset_name'));
    if (!$dataset) {
      $dataset = $this->t('None');
    }

    $options['dataset_name'] = [
      'category' => 'access',
      'title' => $this->t('Dataset Name'),
      'value' => views_ui_truncate($dataset, 32),
      'desc' => $this->t('Change the dataset name to create in CARTO.'),
    ];

    $categories['access'] = [
      'title' => $this->t('CARTO settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // It is very important to call the parent function here.
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'dataset_name':
        $form['#title'] .= $this->t('The CARTO dataset name');
        $form['dataset_name'] = [
          '#title' => $this->t('Dataset name'),
          '#type' => 'machine_name',
          '#description' => $this->t('This will be the name of the daaset generated when synchronizing your data with CARTO.'),
          '#default_value' => $this->getOption('dataset_name'),
          '#required' => TRUE,
          '#maxlength' => 255,
        ];
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $section = $form_state->get('section');
    switch ($section) {
      case 'dataset_name':
        if ($form_state->getValue('dataset_name')) {
          if (preg_match('/[^a-z0-9_]/', $form_state->getValue('dataset_name'))) {
            $form_state->setError($form['dataset_name'], $this->t('Dataset name must be letters, numbers, or underscores only.'));
          }

          foreach ($this->view->displayHandlers as $id => $display) {
            if ($id != $this->view->current_display && ($form_state->getValue('dataset_name') == $id || (isset($display->new_id) && $form_state->getValue('dataset_name') == $display->new_id))) {
              $form_state->setError($form['dataset_name'], $this->t('Dataset name should be unique.'));
            }
          }
          // @TODO: Validate uniqueness across the whole system.
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'dataset_name':
        $this->setOption('dataset_name', $form_state->getValue('dataset_name'));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function usesLinkDisplay() {
    return FALSE;
  }

  /**
   * Checks whether the current display has any Geo field or not.
   *
   * @return bool
   *   TRUE if the current display has at least one GeoField item.
   */
  protected function hasGeoField() {
    $available = FALSE;
    $fields = $this->getHandlers('field');
    foreach ($fields as $field) {
      if (isset($field->definition['field_name'])) {
        $entity_type_id = $field->definition['entity_type'];
        $def = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type_id);
        if ($def[$field->definition['field_name']]->getType() == 'geofield') {
          $available = TRUE;
          break;
        }
      }
    }
    return $available;
  }

}
