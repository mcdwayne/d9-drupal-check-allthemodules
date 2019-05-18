<?php

namespace Drupal\auto_index\Plugin\Search;
use Drupal\node\Plugin\Search\NodeSearch;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;


/**
 * The method we wish to call on the original object IndexNodeSearch has a protected visibility applied, and therefore
 * we are left with 2 options. Copy and paste the current functionality into this module or extend the existing class.
 * We are extending the existing functionality, implementing it this way will ensure we inherrit
 * all fixes applied to the original plugin.
 */
class AutoIndexNodeSearch extends NodeSearch {
  
  /**
   * Add a public method to allow us to target single nodes outside of this class.
   * @param NodeInterface $node
   */
  public function indexSingleNode(NodeInterface $node) {
    
    // Ensure the
    $query = db_select('node', 'n', array('target' => 'replica'));
    $query->addField('n', 'nid');
    $query->leftJoin('search_dataset', 'sd', 'sd.sid = n.nid AND sd.type = :type', array(':type' => $this->getPluginId()));
    $query->condition(
      $query->andConditionGroup()
        ->condition('n.nid', $node->id())
        ->condition(
          $query->orConditionGroup()
            ->where('sd.sid IS NULL')
            ->condition('sd.reindex', 0, '<>')
          )
    );
    
    $num_rows = $query->countQuery()->execute()->fetchField();
    if ($num_rows < 1) {
      return;
    }
    
    // Use the implementation of indexNode from the plugin.
    $this->indexNode($node);
    
    // Register a shutdown hook to ensure the totals get recalculated.
    drupal_register_shutdown_function('search_update_totals');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    
    $form['autoindex'] = array(
      '#type' => 'details',
      '#title' => $this->t('Auto Indexing'),
      '#open' => TRUE
    );
    
    $form['autoindex']['automatic_indexing'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically index on Create/Update of content.'),
      '#default_value' => $this->configuration['automatic_indexing']
    );
    
    return array_merge($form, parent::buildConfigurationForm($form, $form_state));

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['automatic_indexing'] = $form_state->getValue(['automatic_indexing']);
    parent::buildConfigurationForm($form, $form_state);
  }
}