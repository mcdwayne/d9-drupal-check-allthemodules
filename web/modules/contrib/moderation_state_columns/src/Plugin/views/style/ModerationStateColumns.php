<?php

namespace Drupal\moderation_state_columns\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Renders entities in the columns associated with their moderation state.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "moderation_state_columns",
 *   title = @Translation("Moderation state columns"),
 *   help = @Translation("Renders entities in columns representing their
 *   moderation states."), theme = "views_view_moderation_state_columns",
 *   display_types = { "normal" }
 * )
 */
class ModerationStateColumns extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to its output.
   *
   * @var bool
   */
  protected $usesFields = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Returns the workflow states as options.
   *
   * @param \Drupal\workflows\Entity\Workflow $workflow
   *   The workflow entity.
   *
   * @return array
   */
  public static function getWorkflowStatesOptions(Workflow $workflow) {
    $options = [];
    $stateDefinitions = $workflow->get('type_settings')['states'];
    uasort($stateDefinitions, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    foreach ($stateDefinitions as $id => $state) {
      $options[$id] = $state['label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['workflow'] = array('default' => '');
    $options['states'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $workflows = ['' => $this->t('- Select -')];
    /** @var Workflow $workflow */
    foreach (Workflow::loadMultiple() as $workflow) {
      $workflows[$workflow->id()] = $workflow->label();
    }

    $form['workflow'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Workflow'),
      '#description' => $this->t('The workflow to use.'),
      '#options' => $workflows,
      '#default_value' => $this->options['workflow'],
      '#ajax' => [
        'callback' => [get_called_class(), 'updateStates'],
        'wrapper' => 'moderation-state-columns-states',
      ],
    ];

    $workflowId = NULL;
    $currentStateOptions = $form_state->getValue('style_options');
    if (!empty($currentStateOptions)) {
      $workflowId  = $currentStateOptions['workflow'];
    } else {
      $workflowId = $this->options['workflow'];
    }

    $form['states'] = [
      '#prefix' => '<div id="moderation-state-columns-states">',
      '#suffix' => '</div>',
    ];

    if (!empty($workflowId)) {
      $workflow = Workflow::load($workflowId);
      $states = $currentStateOptions['states'] ? $currentStateOptions['states'] : $this->options['states'];
      $form['states'] += [
        '#title' => $this->t('States'),
        '#description' => $this->t('Only show columns for the states chosen here. <strong>The filters should still be added to the view.</strong>'),
        '#type' => 'select',
        '#multiple' => TRUE,
        '#options' => self::getWorkflowStatesOptions($workflow),
        '#default_value' => $states,
      ];
    } else {
      $form['states']['#type'] = 'hidden';
    }
  }

  /**
   * Ajax callback that triggers when the workflow changes.
   *
   * @param array $form
   * @param \Drupal\core\form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function updateStates(array &$form, FormStateInterface $form_state) : array {
    return $form['options']['style_options']['states'];
  }

}
