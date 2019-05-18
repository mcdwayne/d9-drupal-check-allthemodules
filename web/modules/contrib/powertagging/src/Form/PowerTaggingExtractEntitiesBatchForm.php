<?php
/**
 * @file
 * Contains \Drupal\powertagging\Form\PowerTaggingExtractEntitiesBatchForm.
 */

namespace Drupal\powertagging\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\powertagging\PowerTagging;
use Drupal\semantic_connector\SemanticConnector;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PowerTaggingExtractEntitiesBatchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'powertagging_extract_entities_batch_form';
  }

  /**
   * A form to update the PP server connection of a PowerTagging configuration.
   *
   * This form is used when creating a completely new PowerTagging configuration
   * or when the PoolParty server connection needs to be changed or a different
   * project shell be used for an existing PowerTagging configuration.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface &$form_state
   *   The form_state array.
   * @param string $entity_type
   *   The type of content to extract entities for.
   * @param string $bundle
   *   The bundle to extract entities for.
   *
   * @return array
   *   The Drupal form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = '', $bundle = '') {
    $extraction_config = PowerTagging::getEntityExtractionSettings($entity_type, $bundle);
    if ($extraction_config['enabled']) {
      $form['entity_type'] = [
        '#type' => 'value',
        '#value' => $entity_type,
      ];
      $form['bundle'] = [
        '#type' => 'value',
        '#value' => $bundle,
      ];

      /** @var \Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection $pp_server_connection */
      $pp_server_connection = SemanticConnector::getConnection('pp_server', $extraction_config['connection_id']);
      // Add information about the planned extraction.
      $info_markup = '<p id="powertagging-extraction-info">';
      $info_markup .= t('PoolParty server to use') . ': <b>' . $pp_server_connection->getTitle() . ' (' . $pp_server_connection->getUrl() . ')</b><br />';

      $all_entity_type_labels = [
        'location' => t('Locations'),
        'organization' => t('Organizations'),
        'person' => t('People'),
      ];
      $entity_type_labels = [];
      foreach ($extraction_config['types'] as $type) {
        $entity_type_labels[] = $all_entity_type_labels[$type];
      }
      $info_markup .= t('Entity types to extract') . ': <b>' . implode(', ', $entity_type_labels) . '</b><br />';

      $field_instances = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
      $field_labels = [];
      foreach ($extraction_config['fields'] as $field_id) {
        /** @var \Drupal\field\Entity\FieldConfig $field_instance */
        $field_instance = $field_instances[$field_id];
        $field_labels[] = $field_instance->get('label');
      }
      $info_markup .= t('Fields to extract entities for') . ': <b>' . implode(', ', $field_labels) . '</b><br />';

      // Build nodes counts.
      $total_nodes_query = \Drupal::database()->select('node', 'n');
      $total_nodes_query->fields('n', ['nid'])
        ->condition('n.type', $bundle);
      $total_count = $total_nodes_query->countQuery()->execute()->fetchField();

      $extraction_nodes_query = \Drupal::database()->select('node', 'n');
      $extraction_nodes_query->fields('n', ['nid'])
        ->condition('n.type', $bundle);
      $extraction_nodes_query->join('powertagging_entity_extraction_cache', 'c', 'c.entity_id = n.nid AND c.entity_type = \'node\'');
      $extraction_nodes_query->distinct();
      $extracted_count = $extraction_nodes_query->countQuery()->execute()->fetchField();

      $info_markup .= '<br />' . t('Nodes of this type with extracted entities') . ': <b>' . $extracted_count . ' / ' . $total_count . '</b>';

      $info_markup .= '</p>';
      $form['info_markup'] = array(
        '#type' => 'markup',
        '#markup' => $info_markup,
      );

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Extract entities',
      );
      if (\Drupal::request()->query->has('destination')) {
        $destination = \Drupal::request()->get('destination');
        $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . $destination);
      }
      else {
        $url = Url::fromRoute('');
      }
      $form['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $url,
        '#attributes' => [
          'class' => ['button'],
        ],
      ];

    }
    else {
      $form['error_markup'] = array(
        '#type' => 'markup',
        '#markup' => '<div class="messages error">' . t('Entity extraction is not enabled for the selected entity type / bundle.') . '</div>',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type');
    $bundle = $form_state->getValue('bundle');

    $nid_query = \Drupal::database()->select('node', 'n');
    $nid_query->fields('n', ['nid'])
      ->condition('n.type', $bundle);
    $nids = $nid_query->execute()->fetchCol();
    // Each node is one batch operation.
    $operations = [];
    foreach ($nids as $nid) {
      $operations[] = array(
        [$this, 'extractEntitiesBatchProcess'],
        array($entity_type, $nid),
      );
    }

    if (!empty($operations)) {
      // Delete old entries first.
      \Drupal::database()->delete('powertagging_entity_extraction_cache')
        ->condition('entity_type', $entity_type)
        ->condition('bundle', $bundle)
        ->execute();

      $node_type_names = node_type_get_names();
      $batch = array(
        'title' => t('Extracting entities for node type "@nodetype"', ['@nodetype' => $node_type_names[$bundle]]),
        'operations' => $operations,
        'init_message' => t('Starting the extraction of the entities.'),
        'progress_message' => 'Extracting entities.',
        'finished' => [$this, 'extractEntitiesBatchFinished'],
      );

      // Start the batch to extract new entities.
      batch_set($batch);
    }
    else {
      \Drupal::messenger()->addMessage(t('There is no content available to extract entities for.'), 'warning');
      if (\Drupal::request()->query->has('destination')) {
        $destination = \Drupal::request()->get('destination');
        $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . $destination);
      }
      else {
        $url = Url::fromRoute('<front>', []);
      }

      return new RedirectResponse($url->toString());
    }

    return TRUE;
  }

  /**
   * The batch job to extract entities for content.
   *
   * @param string $entity_type
   *   The type of content to extract entities for.
   * @param int $entity_id
   *   The ID of the content to extract entities for
   * @param array $context
   *   The Batch context to transmit data between different calls.
   */
  public function extractEntitiesBatchProcess($entity_type, $entity_id, &$context) {
    $entity_manager = \Drupal::entityTypeManager();
    $entity = $entity_manager->getStorage($entity_type)->load($entity_id);
    if ($entity) {
      PowerTagging::buildEntityExtractionCache($entity_type, $entity);
    }
  }

  /**
   * Batch 'finished' callback used by PowerTagging bulk entitiy extraction.
   */
  public function extractEntitiesBatchFinished($success, $results, $operations) {
    \Drupal::messenger()->addMessage(t('Successfully finished extracting entities for the selected content.'), 'status');
    if (\Drupal::request()->query->has('destination')) {
      $destination = \Drupal::request()->get('destination');
      $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . $destination);
    }
    else {
      $url = Url::fromRoute('<front>', []);
    }

    return new RedirectResponse($url->toString());
  }
}