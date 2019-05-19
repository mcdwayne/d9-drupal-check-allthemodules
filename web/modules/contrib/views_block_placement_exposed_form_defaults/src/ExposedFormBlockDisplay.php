<?php

namespace Drupal\views_block_placement_exposed_form_defaults;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block;

/**
 * A plugin class that overrides the core views block display plugin class.
 */
class ExposedFormBlockDisplay extends Block {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($form_state->get('section') !== 'allow') {
      return;
    }
    $customized_filters = $this->getOption('customizable_exposed_filters');
    $form['customizable_exposed_filters'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getListOfExposedFilters(),
      '#title' => $this->t('Customizable filters'),
      '#description' => $this->t('Select the filters which users should be able to customize default values for when placing the views block into a layout.'),
      '#default_value' => !empty($customized_filters) ? $customized_filters : [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    if ($form_state->get('section') === 'allow') {
      $this->setOption('customizable_exposed_filters', Checkboxes::getCheckedCheckboxes($form_state->getValue('customizable_exposed_filters')));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $customizable_filters = $this->getOption('customizable_exposed_filters');
    $filter_count = !empty($customizable_filters) ? count($customizable_filters) : 0;
    $options['allow']['value'] .= ', ' . $this->formatPlural($filter_count, '1 customizable filter', '@count customizable filters');
  }

  /**
   * Get a list of exposed filters.
   *
   * @return array
   *   An array of filters keyed by machine name with label values.
   */
  protected function getListOfExposedFilters() {
    $filter_options = [];
    foreach ($this->getHandlers('filter') as $filer_name => $filter_plugin) {
      if ($filter_plugin->isExposed() && $exposed_info = $filter_plugin->exposedInfo()) {
        $filter_options[$filer_name] = $exposed_info['label'];
      }
    }
    return $filter_options;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);

    $form['exposed_filters'] = [
      '#tree' => TRUE,
    ];
    $block_configuration = $block->getConfiguration();
    $exposed_filter_values = !empty($block_configuration['exposed_filter_values']) ? $block_configuration['exposed_filter_values'] : [];

    $subform_state = SubformState::createForSubform($form['exposed_filters'], $form, $form_state);
    $subform_state->set('exposed', TRUE);

    $customizable_filters = $this->getOption('customizable_exposed_filters');
    $filter_plugins = $this->getHandlers('filter');

    foreach ($customizable_filters as $customizable_filter) {
      /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
      $filter = $filter_plugins[$customizable_filter];
      $filter->buildExposedForm($form['exposed_filters'], $subform_state);

      // Set the label and default values of the form element, based on the
      // block configuration.
      $exposed_info = $filter->exposedInfo();
      $form['exposed_filters'][$exposed_info['value']]['#title'] = $exposed_info['label'];
      $form['exposed_filters'][$exposed_info['value']]['#default_value'] = !empty($exposed_filter_values[$exposed_info['value']]) ? $exposed_filter_values[$exposed_info['value']] : [];
    }

    return $form;
  }

  /**
   * Handles form submission for the views block configuration form.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The ViewsBlock plugin.
   * @param mixed $form
   *   The form definition array for the full block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::blockSubmit()
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    parent::blockSubmit($block, $form, $form_state);
    $block->setConfigurationValue('exposed_filter_values', $form_state->getValue('exposed_filters'));
  }

  /**
   * {@inheritdoc}
   */
  public function preBlockBuild(ViewsBlock $block) {
    parent::preBlockBuild($block);

    $block_configuration = $block->getConfiguration();
    $exposed_filter_values = !empty($block_configuration['exposed_filter_values']) ? $block_configuration['exposed_filter_values'] : [];
    $this->view->setExposedInput($exposed_filter_values);
    $this->view->exposed_data = $exposed_filter_values;
  }

}
