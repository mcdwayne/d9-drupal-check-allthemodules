<?php

namespace Drupal\ingredient\Plugin\views\wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Wizard plugin for creating Ingredient views.
 *
 * @ViewsWizard(
 *   id = "ingredient",
 *   base_table = "ingredient_field_data",
 *   title = @Translation("Ingredient")
 * )
 */
class Ingredient extends WizardPluginBase {

  /**
   * @var string
   *
   * Set the created column.
   */
  protected $createdColumn = 'ingredient_field_data-created';

  /**
   * Overrides Drupal\views\Plugin\views\wizard\WizardPluginBase::getAvailableSorts().
   *
   * @return array
   *   An array whose keys are the available sort options and whose
   *   corresponding values are human readable labels.
   */
  public function getAvailableSorts() {
    // You can't execute functions in properties, so override the method.
    return [
      'ingredient_field_data-name:ASC' => $this->t('Name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function rowStyleOptions() {
    $options = [];
    $options['full_posts'] = $this->t('full posts');
    $options['names'] = $this->t('names');
    $options['names_linked'] = $this->t('names (linked)');
    $options['fields'] = $this->t('fields');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'view ingredient';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: Ingredient: Name */
    $display_options['fields']['name']['id'] = 'name';
    $display_options['fields']['name']['table'] = 'ingredient_field_data';
    $display_options['fields']['name']['field'] = 'name';
    $display_options['fields']['name']['entity_type'] = 'ingredient';
    $display_options['fields']['name']['entity_field'] = 'name';
    $display_options['fields']['name']['label'] = '';
    $display_options['fields']['name']['alter']['alter_text'] = 0;
    $display_options['fields']['name']['alter']['make_link'] = 0;
    $display_options['fields']['name']['alter']['absolute'] = 0;
    $display_options['fields']['name']['alter']['trim'] = 0;
    $display_options['fields']['name']['alter']['word_boundary'] = 0;
    $display_options['fields']['name']['alter']['ellipsis'] = 0;
    $display_options['fields']['name']['alter']['strip_tags'] = 0;
    $display_options['fields']['name']['alter']['html'] = 0;
    $display_options['fields']['name']['hide_empty'] = 0;
    $display_options['fields']['name']['empty_zero'] = 0;
    $display_options['fields']['name']['type'] = 'string';
    $display_options['fields']['name']['settings']['link_to_entity'] = 1;
    $display_options['fields']['name']['plugin_id'] = 'field';

    return $display_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function pageDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = parent::pageDisplayOptions($form, $form_state);
    $row_plugin = $form_state->getValue(['page', 'style', 'row_plugin']);
    $row_options = $form_state->getValue(['page', 'style', 'row_options'], []);
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockDisplayOptions(array $form, FormStateInterface $form_state) {
    $display_options = parent::blockDisplayOptions($form, $form_state);
    $row_plugin = $form_state->getValue(['block', 'style', 'row_plugin']);
    $row_options = $form_state->getValue(['block', 'style', 'row_options'], []);
    $this->display_options_row($display_options, $row_plugin, $row_options);
    return $display_options;
  }

  /**
   * Set the row style and row style plugins to the display_options.
   */
  protected function display_options_row(&$display_options, $row_plugin, $row_options) {
    switch ($row_plugin) {
      case 'full_posts':
        $display_options['row']['type'] = 'entity:ingredient';
        $display_options['row']['options']['view_mode'] = 'full';
        break;

      case 'names_linked':
      case 'names':
        $display_options['row']['type'] = 'fields';
        $display_options['fields']['name']['id'] = 'name';
        $display_options['fields']['name']['table'] = 'ingredient_field_data';
        $display_options['fields']['name']['field'] = 'name';
        $display_options['fields']['name']['settings']['link_to_entity'] = $row_plugin === 'names_linked';
        $display_options['fields']['name']['plugin_id'] = 'field';
        break;
    }
  }

}
