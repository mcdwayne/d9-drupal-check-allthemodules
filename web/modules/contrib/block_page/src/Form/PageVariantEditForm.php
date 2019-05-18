<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\PageVariantEditForm.
 */

namespace Drupal\block_page\Form;

use Drupal\block_page\BlockPageInterface;
use Drupal\Component\Serialization\Json;

/**
 * Provides a form for editing a page variant.
 */
class PageVariantEditForm extends PageVariantFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_page_page_variant_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Update page variant');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, BlockPageInterface $block_page = NULL, $page_variant_id = NULL) {
    $form = parent::buildForm($form, $form_state, $block_page, $page_variant_id);

    // Set up the attributes used by a modal to prevent duplication later.
    $attributes = array(
      'class' => array('use-ajax'),
      'data-accepts' => 'application/vnd.drupal-modal',
      'data-dialog-options' => Json::encode(array(
        'width' => 'auto',
      )),
    );

    // Build a table of all blocks used by this page variant.
    $form['block_section'] = array(
      '#type' => 'details',
      '#title' => $this->t('Blocks'),
      '#open' => TRUE,
    );
    $form['block_section']['add'] = array(
      '#type' => 'link',
      '#title' => $this->t('Add new block'),
      '#route_name' => 'block_page.page_variant_select_block',
      '#route_parameters' => array(
        'block_page' => $this->blockPage->id(),
        'page_variant_id' => $this->pageVariant->id(),
      ),
      '#attributes' => $attributes,
      '#attached' => array(
        'library' => array(
          'core/drupal.ajax',
        ),
      ),
    );
    $form['block_section']['blocks'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Label'),
        $this->t('Plugin ID'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ),
      '#empty' => $this->t('There are no regions for blocks.'),
    );
    // Loop through the blocks per region.
    foreach ($this->pageVariant->getRegionAssignments() as $region => $blocks) {
      // Add a section for each region and allow blocks to be dragged between
      // them.
      $form['block_section']['blocks']['#tabledrag'][] = array(
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region,
        'hidden' => FALSE,
      );
      $form['block_section']['blocks']['#tabledrag'][] = array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region,
      );
      $form['block_section']['blocks'][$region] = array(
        '#attributes' => array(
          'class' => array('region-title', 'region-title-' . $region),
          'no_striping' => TRUE,
        ),
      );
      $form['block_section']['blocks'][$region]['title'] = array(
        '#markup' => $this->pageVariant->getRegionName($region),
        '#wrapper_attributes' => array(
          'colspan' => 5,
        ),
      );
      $form['block_section']['blocks'][$region . '-message'] = array(
        '#attributes' => array(
          'class' => array(
            'region-message',
            'region-' . $region . '-message',
            empty($blocks) ? 'region-empty' : 'region-populated',
          ),
        ),
      );
      $form['block_section']['blocks'][$region . '-message']['message'] = array(
        '#markup' => '<em>' . t('No blocks in this region') . '</em>',
        '#wrapper_attributes' => array(
          'colspan' => 5,
        ),
      );

      /** @var $blocks \Drupal\block\BlockPluginInterface[] */
      foreach ($blocks as $block_id => $block) {
        $row = array(
          '#attributes' => array(
            'class' => array('draggable'),
          ),
        );
        $row['label']['#markup'] = $block->label();
        $row['id']['#markup'] = $block->getPluginId();
        // Allow the region to be changed for each block.
        $row['region'] = array(
          '#title' => $this->t('Region'),
          '#title_display' => 'invisible',
          '#type' => 'select',
          '#options' => $this->pageVariant->getRegionNames(),
          '#default_value' => $this->pageVariant->getRegionAssignment($block_id),
          '#attributes' => array(
            'class' => array('block-region-select', 'block-region-' . $region),
          ),
        );
        // Allow the weight to be changed for each block.
        $configuration = $block->getConfiguration();
        $row['weight'] = array(
          '#type' => 'weight',
          '#default_value' => isset($configuration['weight']) ? $configuration['weight'] : 0,
          '#title' => t('Weight for @block block', array('@block' => $block->label())),
          '#title_display' => 'invisible',
          '#attributes' => array(
            'class' => array('block-weight', 'block-weight-' . $region),
          ),
        );
        // Add the operation links.
        $operations = array();
        $operations['edit'] = array(
          'title' => $this->t('Edit'),
          'route_name' => 'block_page.page_variant_edit_block',
          'route_parameters' => array(
            'block_page' => $this->blockPage->id(),
            'page_variant_id' => $this->pageVariant->id(),
            'block_id' => $block_id,
          ),
          'attributes' => $attributes,
        );
        $row['operations'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );
        $form['block_section']['blocks'][$block_id] = $row;
      }
    }

    // Selection conditions.
    $form['selection_section'] = array(
      '#type' => 'details',
      '#title' => $this->t('Selection Conditions'),
      '#open' => TRUE,
    );
    $form['selection_section']['add'] = array(
      '#type' => 'link',
      '#title' => $this->t('Add new selection condition'),
      '#route_name' => 'block_page.selection_condition_select',
      '#route_parameters' => array(
        'block_page' => $this->blockPage->id(),
        'page_variant_id' => $this->pageVariant->id(),
      ),
      '#attributes' => $attributes,
      '#attached' => array(
        'library' => array(
          'core/drupal.ajax',
        ),
      ),
    );
    $form['selection_section']['table'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Operations'),
      ),
      '#empty' => $this->t('There are no selection conditions.'),
    );

    $form['selection_section']['selection_logic'] = array(
      '#type' => 'radios',
      '#options' => array(
        'and' => $this->t('All conditions must pass'),
        'or' => $this->t('Only one condition must pass'),
      ),
      '#default_value' => $this->pageVariant->getSelectionLogic(),
    );

    $selection_conditions = $this->pageVariant->getSelectionConditions();
    $form['selection_section']['selection'] = array(
      '#tree' => TRUE,
    );
    foreach ($selection_conditions as $selection_id => $selection_condition) {
      $row = array();
      $row['label']['#markup'] = $selection_condition->getPluginDefinition()['label'];
      $row['description']['#markup'] = $selection_condition->summary();
      $operations = array();
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'route_name' => 'block_page.selection_condition_edit',
        'route_parameters' => array(
          'block_page' => $this->blockPage->id(),
          'page_variant_id' => $this->pageVariant->id(),
          'condition_id' => $selection_id,
        ),
        'attributes' => $attributes,
      );
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'route_name' => 'block_page.selection_condition_delete',
        'route_parameters' => array(
          'block_page' => $this->blockPage->id(),
          'page_variant_id' => $this->pageVariant->id(),
          'condition_id' => $selection_id,
        ),
        'attributes' => $attributes,
      );
      $row['operations'] = array(
        '#type' => 'operations',
        '#links' => $operations,
      );
      $form['selection_section']['table'][$selection_id] = $row;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    // If the blocks were rearranged, update their values.
    if (!empty($form_state['values']['blocks'])) {
      foreach ($form_state['values']['blocks'] as $block_id => $block_values) {
        $this->pageVariant->updateBlock($block_id, $block_values);
      }
    }

    // Save the block page.
    $this->blockPage->save();
    drupal_set_message($this->t('The %label page variant has been updated.', array('%label' => $this->pageVariant->label())));
    $form_state['redirect_route'] = $this->blockPage->urlInfo('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  protected function preparePageVariant($page_variant_id) {
    // Load the page variant directly from the block page.
    return $this->blockPage->getPageVariant($page_variant_id);
  }

}
