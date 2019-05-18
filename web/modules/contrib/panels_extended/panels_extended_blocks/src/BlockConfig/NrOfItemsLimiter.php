<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

use Drupal\Core\Form\FormStateInterface;
use Drupal\panels_extended\BlockConfig\AdminInfoInterface;
use Drupal\panels_extended\BlockConfig\BlockConfigBase;
use Drupal\panels_extended\BlockConfig\BlockFormInterface;
use Drupal\panels_extended_blocks\NodeListBlockBase;

/**
 * Adds the configuration for the maximum number of items.
 */
class NrOfItemsLimiter extends BlockConfigBase implements AdminInfoInterface, AlterQueryRangeInterface, BlockFormInterface {

  /**
   * Name of the configuration field.
   */
  const CFG_NAME = 'number_of_items';

  /**
   * The block.
   *
   * @var \Drupal\panels_extended_blocks\NodeListBlockBase
   */
  protected $block;

  /**
   * The allowed values to choose from.
   *
   * If empty array, the user can choose freely.
   *
   * @var int[]
   */
  protected $allowedValues;

  /**
   * Constructor.
   *
   * @param \Drupal\panels_extended_blocks\NodeListBlockBase $block
   *   The block.
   * @param int[] $allowedValues
   *   Limit the allowed values or [] for free.
   */
  public function __construct(NodeListBlockBase $block, array $allowedValues = []) {
    parent::__construct($block);

    $this->allowedValues = $allowedValues;
  }

  /**
   * {@inheritdoc}
   */
  public function modifyBlockForm(array &$form, FormStateInterface $form_state) {
    $form[self::CFG_NAME] = [
      '#title' => t('Number of items'),
      '#default_value' => isset($this->configuration[self::CFG_NAME]) ? $this->configuration[self::CFG_NAME] : $this->block->getNumberOfItems(),
    ];
    if (empty($this->allowedValues)) {
      $form[self::CFG_NAME] += [
        '#type' => 'number',
        '#min' => '0',
        '#step' => '1',
        '#description' => t('Use 0 to show all items.'),
      ];
    }
    else {
      $options = array_combine($this->allowedValues, $this->allowedValues);
      if (isset($options[0])) {
        $options[0] = t('Show all items');
      }
      $form[self::CFG_NAME] += [
        '#type' => 'select',
        '#options' => $options,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitBlockForm(array &$form, FormStateInterface $form_state) {
    $this->block->setConfigurationValue(self::CFG_NAME, (int) $form_state->getValue(self::CFG_NAME));
  }

  /**
   * {@inheritdoc}
   */
  public function alterQueryRangeDelta(&$start, &$length) {
    if (isset($this->configuration[self::CFG_NAME]) && $this->configuration[self::CFG_NAME] > 0) {
      $length += $this->configuration[self::CFG_NAME] - $this->block->getNumberOfItems();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPrimaryInfo() {
    $value = isset($this->configuration[self::CFG_NAME]) ? $this->configuration[self::CFG_NAME] : $this->block->getNumberOfItems();
    return $value > 0 ? (t('Number of items') . ': ' . $value) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminSecondaryInfo() {
    return NULL;
  }

}
