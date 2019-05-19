<?php
namespace Drupal\taxonomy_facets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Taxonomy Facets Menu' block.
 *
 * @Block(
 *   id = "taxonomy_facets_block4",
 *   admin_label = @Translation("Taxonomy Facets block 4"),
 * )
 */
class TaxonomyFacetsBlock4 extends BlockBase implements BlockPluginInterface {

  // Access  method here ...

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();

    return [
      '#markup' => taxonomy_facets_get_menu_tree($config['taxo_vocabulary_id']),
      '#cache'=> ['max-age' => 0],
    ];
  }

  /**
   * {@inheritdoc}
   */
  //  protected function blockAccess(AccountInterface $account) {
  //  return AccessResult::allowedIfHasPermission($account, 'access content');
  //}


  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    $form['vocab_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select vocabulary. This block will display filters based on the taxonomy terms from the selected vocabulary'),
      '#options' => taxonomy_vocabulary_get_names(),
      '#default_value' => isset($config['taxo_vocabulary_id']) ? $config['taxo_vocabulary_id'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('taxo_vocabulary_id', $form_state->getValue('vocab_select'));
  }
}