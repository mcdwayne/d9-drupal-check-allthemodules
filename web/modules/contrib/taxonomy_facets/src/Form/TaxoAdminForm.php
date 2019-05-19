<?php
/**
 * @file
 * Contains Drupal\taxonomy_facets\Form\TaxoAdminForm.
 */

namespace Drupal\taxonomy_facets\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TaxoAdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'taxonomy_facets.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_facets_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('taxonomy_facets.settings');

    $form['page_title'] = [
        '#type' => 'textfield',
        '#size' => 20,
        '#title' => $this->t('Page title'),
        '#description' => $this->t('Optionaly set the title of the node listing page'),
        '#default_value' => $config->get('page_title'),
    ];

    $form['number_of_nodes_per_page'] = [
      '#type' => 'textfield',
      '#size' => 4,
      '#title' => $this->t('Number of nodes per page'),
      '#description' => $this->t('Maximum number of nodes on a listing page, pager will be displayed at the bottom so user can view more nodes'),
      '#default_value' => $config->get('number_of_nodes_per_page'),
    ];

    $form['show_nodes_if_no_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show all nodes if no filters applied'),
      '#description' => $this->t('When user first lands on listing page, and no filters are applied, you can choose to show all node, or none. 
      To show none deselect this check box'),
      '#default_value' => $config->get('show_nodes_if_no_filters'),
    ];

    $form['cascade_terms'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Cascade terms'),
      '#description' => $this->t("Check this box if you want the node to appear in the result set when parent terms are selected. 
       for example if node is associated with location London, but you want a node to appear in the result set if the filter applied 
       is Europe, or UK, you can do it in two ways:").
        "</br>" . $this->t("1. Manually associate the node with parent terms, when you edit node select, for example, UK and Europe.
         (In which case leave all above checkboxes unchecked)") . "</br>" .
        $this->t("2. Ckeck the checkbox for each Vocabulary that you want to cascade.
        Nodes will automaticaly be associated with all parent terms. Check only Vocabularies that you use for Taxo Faceted filtering") . "</br>" .
       $this->t("NOTE: Thois only makes sense for Taxonomy with hierarchical values, for flat taxonomies leave these boxes unchecked") ."</br>",
    );

    $vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
    foreach($vocabularies as $vocabulary){
      $form['cascade_terms'][$vocabulary->id()] = [
        '#type' => 'checkbox',
        '#title' => $vocabulary->label(),
        '#default_value' => $config->get($vocabulary->id()),
      ];
    }

    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

    $form['node_types'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Node types'),
      '#description' => $this->t("Select which node types you want to appear on the Taxonomy Faceted listing page.").
        "</br>" . $this->t("Selected content types will appear on the listings page and will be available for filtering,
         rest of the nodes will be ignored. ") ."</br>" .
       $this->t("NOTE: If you want all content types to appear in the search than leave all boxes unselected. 
       This will have better performance that selecting all content types") ."</br>" ,
    );

    foreach($contentTypes as $contentType){
      $form['node_types']['ct_' . $contentType->id()] = [
        '#type' => 'checkbox',
        '#title' => $contentType->label(),
        '#default_value' => $config->get('ct_' . $contentType->id()),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $config = \Drupal::configFactory()->getEditable('taxonomy_facets.settings');
    $config->set('page_title', $form_state->getValue('page_title'));
    $config->set('show_nodes_if_no_filters', $form_state->getValue('show_nodes_if_no_filters'));
    $config->set('number_of_nodes_per_page', $form_state->getValue('number_of_nodes_per_page'));

    // Deal with vocabularies
    $vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
    foreach($vocabularies as $vocabulary){
      $config->set($vocabulary->id(), $form_state->getValue($vocabulary->id()));
    }

    // Deal with content types
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    foreach($contentTypes as $contentType){
      $config->set('ct_' . $contentType->id(), $form_state->getValue('ct_' . $contentType->id()));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
