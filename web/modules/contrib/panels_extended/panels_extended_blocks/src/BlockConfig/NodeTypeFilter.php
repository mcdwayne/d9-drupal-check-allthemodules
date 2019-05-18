<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\panels_extended\BlockConfig\AdminInfoInterface;
use Drupal\panels_extended\BlockConfig\BlockConfigBase;
use Drupal\panels_extended\BlockConfig\BlockFormInterface;
use Drupal\panels_extended_blocks\Form\AdminSettingsForm;
use Drupal\panels_extended_blocks\NodeListBlockBase;

/**
 * Adds the configuration for the node types.
 */
class NodeTypeFilter extends BlockConfigBase implements AdminInfoInterface, AlterQueryInterface, BlockFormInterface {

  /**
   * Name of the configuration field.
   */
  const CFG_NAME = 'node_types';

  /**
   * Default selected node types.
   *
   * If empty, the list from the settings are fetched.
   *
   * @var array
   *
   * @see \Drupal\panels_extended_blocks\Form\AdminSettingsForm::CFG_DEFAULT_CONTENT_TYPES
   */
  protected $defaultTypes;

  /**
   * The allowed node types to choose from.
   *
   * If NULL, no selection can be made.
   * If empty array, the list from the settings are fetched.
   *
   * @var array|null
   *
   * @see \Drupal\panels_extended_blocks\Form\AdminSettingsForm::CFG_ALLOWED_CONTENT_TYPES
   */
  protected $allowedTypes;

  /**
   * Constructor.
   *
   * @param \Drupal\panels_extended_blocks\NodeListBlockBase $block
   *   The block.
   * @param array $defaultNodeTypes
   *   The default node types, [] for the values from the settings.
   * @param array|null $allowedNodeTypes
   *   Limited the allowed node types, [] for default (from settings), NULL for no selection.
   *
   * @see \Drupal\panels_extended_blocks\Form\AdminSettingsForm::CFG_ALLOWED_CONTENT_TYPES
   */
  public function __construct(NodeListBlockBase $block, array $defaultNodeTypes = [], $allowedNodeTypes = []) {
    parent::__construct($block);

    $this->defaultTypes = $defaultNodeTypes;
    $this->allowedTypes = $allowedNodeTypes;
  }

  /**
   * Allow the user to select the node types?
   *
   * @return bool
   *   TRUE for selectable, FALSE otherwise.
   */
  protected function userSelectable() {
    return $this->allowedTypes !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function modifyBlockForm(array &$form, FormStateInterface $form_state) {
    if (!$this->userSelectable()) {
      return;
    }

    $form[self::CFG_NAME] = [
      '#title' => t('Content types'),
      '#description' => t('Select the content types you want to include in the block.'),
      '#type' => 'checkboxes',
      '#options' => empty($this->allowedTypes) ? static::getDefaultAllowedNodeTypes() : $this->allowedTypes,
      '#default_value' => $this->getSelectedNodeTypes(),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitBlockForm(array &$form, FormStateInterface $form_state) {
    if (!$this->userSelectable()) {
      return;
    }
    $nodeTypes = [];
    foreach ($form_state->getValue(self::CFG_NAME) as $nodeType => $checked) {
      if ($checked) {
        $nodeTypes[] = $nodeType;
      }
    }
    $this->block->setConfigurationValue(self::CFG_NAME, $nodeTypes);
  }

  /**
   * Gets the list of node types to be used by this block.
   *
   * @return array
   *   The list of node types.
   */
  public function getSelectedNodeTypes() {
    if (isset($this->configuration[self::CFG_NAME])) {
      return $this->configuration[self::CFG_NAME];
    }
    elseif (empty($this->defaultTypes)) {
      return static::getDefaultSelectedNodeTypes();
    }
    return $this->defaultTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function alterQuery(SelectInterface $query, $isCountQuery) {
    $nodeTypes = $this->getSelectedNodeTypes();
    if (empty($nodeTypes)) {
      return;
    }
    $query->condition('nfd.type', $nodeTypes, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPrimaryInfo() {
    if (!$this->userSelectable()) {
      return NULL;
    }
    $selectedTypes = array_intersect_key(node_type_get_names(), array_flip($this->getSelectedNodeTypes()));
    return t('Content types') . ': ' . implode(', ', $selectedTypes);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminSecondaryInfo() {
    return NULL;
  }

  /**
   * Gets the default selected node types.
   *
   * @return array
   *   The default selected node types.
   */
  public static function getDefaultSelectedNodeTypes() {
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['pe_blocks_default_node_types'] = &drupal_static(__FUNCTION__);
    }
    $selectedNodeTypes = &$drupal_static_fast['pe_blocks_default_node_types'];
    if (!isset($selectedNodeTypes)) {
      $selectedNodeTypes = [];
      $config = \Drupal::config(AdminSettingsForm::CONFIG_NAME)->get(AdminSettingsForm::CFG_DEFAULT_CONTENT_TYPES);
      if (is_array($config)) {
        foreach ($config as $key => $value) {
          if ($value) {
            $selectedNodeTypes[] = $key;
          }
        }
      }
    }
    return $selectedNodeTypes;
  }

  /**
   * Gets the default allowed node types.
   *
   * @return array
   *   The default allowed node types.
   */
  public static function getDefaultAllowedNodeTypes() {
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['pe_blocks_allowed_node_types'] = &drupal_static(__FUNCTION__);
    }
    $allowedNodeTypes = &$drupal_static_fast['pe_blocks_allowed_node_types'];
    if (!isset($allowedNodeTypes)) {
      $allNodeTypes = node_type_get_names();

      $allowedNodeTypes = [];
      $config = \Drupal::config(AdminSettingsForm::CONFIG_NAME)->get(AdminSettingsForm::CFG_ALLOWED_CONTENT_TYPES);
      if (is_array($config)) {
        foreach ($config as $key => $value) {
          if ($value && isset($allNodeTypes[$key])) {
            $allowedNodeTypes[$key] = $allNodeTypes[$key];
          }
        }
      }
    }
    return $allowedNodeTypes;
  }

}
