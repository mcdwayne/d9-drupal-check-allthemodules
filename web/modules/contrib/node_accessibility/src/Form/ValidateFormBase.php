<?php

namespace Drupal\node_accessibility\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node_accessibility\TypeSettingsStorage;
use Drupal\node_accessibility\PerformValidation;
use Drupal\node_accessibility\ProblemsStorage;
use Drupal\quail_api\QuailApiSettings;

/**
 * Base Form controller for the node_accessibility entity validate forms.
 *
 * @ingroup node_accessibility
 */
abstract class ValidateFormBase extends FormBase {

  /**
   * The node validation settings.
   *
   * @var \Drupal\node_accessibility\TypeSettingsStorage
   */
  protected $nodeSettings;

  /**
   * The node ID.
   *
   * @var int
   */
  protected $nodeId;

  /**
   * The node revision ID.
   *
   * @var int
   */
  protected $nodeRevisionId;

  /**
   * Class Constructor.
   */
  public function __construct() {
    $this->nodeSettings = NULL;
    $this->nodeId = NULL;
    $this->nodeRevisionId = NULL;
  }

  /**
   * Class Destructor.
   */
  public function __destruct() {
    $this->nodeSettings = NULL;
    $this->nodeId = NULL;
    $this->nodeRevisionId = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL, $node_revision = NULL) {
    $this->nodeId = $node;
    $this->nodeRevisionId = $node_revision;
    $this->nodeSettings = TypeSettingsStorage::loadByNode($this->nodeId);

    $form['fieldset_results'] = [
      '#type' => 'details',
      '#title' => $this->t('Validation Results'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['fieldset_results']['value_results'] = [
      '#markup' => '',
    ];

    $existing_value_results = $form_state->get('fieldset_results][value_results');
    if (is_null($existing_value_results)) {
      $form['fieldset_results']['value_results']['#markup'] = 'There are no validation results available for this node.';

      $settings = TypeSettingsStorage::loadByNodeAsArray($node);
      $method = QuailApiSettings::get_validation_methods($settings['method']);

      if (is_array($method) && $method['database']) {
        $node_object = Node::load($node);
        if (!is_null($node_revision) && $node_object->vid->value != $node_revision) {
          $entity_type = $node_object->getEntityTypeId();
          $node_object = \Drupal::entityManager()->getStorage($entity_type)->loadRevision($node_revision);
          unset($entity_type);
        }

        $existing_database_results = ProblemsStorage::load_problems(['nid' => $node, 'vid' => $node_object->vid->value]);

        if (!empty($existing_database_results)) {
          unset($form['fieldset_results']['value_results']['#markup']);

          $severitys = QuailApiSettings::get_severity();

          $restructured_results = ProblemsStorage::restructure_results($node, $node_object->vid->value, $severitys);
          foreach ($restructured_results as $severity => $severity_results) {
            $form['fieldset_results']['value_results'][$severity] = [
              '#theme' => 'quail_api_results',
              '#quail_severity_id' => $severity,
              '#quail_severity_array' => $severitys[$severity],
              '#quail_severity_results' => $severity_results,
              '#quail_markup_format' => $settings['format_results'],
              '#quail_title_block' => $settings['title_block'],
              '#quail_display_title' => TRUE,
              '#attached' => [
                'library' => [
                  'node_accessibility/results-theme',
                ],
              ],
            ];
          }
        }
      }
    }
    else {
      $form['fieldset_results']['value_results']['#markup'] = $existing_value_results;
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    if (\Drupal::currentUser()->hasPermission('perform node accessibility validation')) {
      $form['actions']['submit_validate'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Validate'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!\Drupal::currentUser()->hasPermission('perform node accessibility validation')) {
      $form_state->setErrorByName('actions][submit_validate', $this->t('You are not authorized to perform validation at this time.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->nodeId)) {
      return;
    }

    if (is_null($this->nodeSettings)) {
      $this->nodeSettings = TypeSettingsStorage::loadByNode($this->nodeId);
    }

    if (is_null($this->nodeRevisionId)) {
      $results = PerformValidation::nodes([$this->nodeId], NULL, NULL, $this->nodeSettings->getStandards());
    }
    else {
      $results = PerformValidation::node_revisions([$this->nodeRevisionId => $this->nodeId], NULL, NULL, $this->nodeSettings->getStandards());
    }

    if (empty($this->nodeSettings->getNodeType())) {
      return;
    }

    $enabled = $this->nodeSettings->getEnabled();
    if (empty($enabled) || $enabled == 'disabled') {
      return;
    }

    if (array_key_exists($this->nodeId, $results) && !empty($results[$this->nodeId])) {
      $severitys = QuailApiSettings::get_severity();
      $methods = QuailApiSettings::get_validation_methods();

      $result = reset($results[$this->nodeId]);
      unset($results);

      if (empty($result['report'])) {
        unset($result);

        $markup = $this->t('No accessibility violations have been detected.');
      }
      else {
        $reports = $result['report'];
        $total = $result['total'];
        unset($result);

        $format_results = $this->nodeSettings->getFormatResults();
        if (empty($format_results)) {
          $format_results = \Drupal::config('quail_api.settings')->get('filter_format');
        }

        $title_block = $this->nodeSettings->getFormatResults();
        if (empty($title_block)) {
          $title_block = \Drupal::config('quail_api.settings')->get('title_block');
        }

        if (empty($title_block)) {
          $title_block = 'h3';
        }

        // the reason this is converted to markup is because the generated
        // markup is intended to be saved to the database. This is not a
        // cache, but a renderred copy of the data for archival and
        // validation purposes.
        $markup = '';
        foreach ($reports as $severity => $severity_results) {
          $theme_array = [
            '#theme' => 'quail_api_results',
            '#quail_severity_id' => $severity,
            '#quail_severity_array' => $severitys[$severity],
            '#quail_severity_results' => $severity_results,
            '#quail_markup_format' => $format_results,
            '#quail_title_block' => $title_block,
            '#quail_display_title' => TRUE,
            '#attached' => [
              'library' => [
                'node_accessibility/results-theme',
              ],
            ],
          ];

          $markup .= \Drupal::service('renderer')->render($theme_array, FALSE);
        }
      }

      $form_state->set('fieldset_results][value_results', $markup);
    }

    $form_state->setRebuild(TRUE);
    $form_state->setSubmitted(TRUE);
    $form_state->setExecuted(TRUE);
  }
}
