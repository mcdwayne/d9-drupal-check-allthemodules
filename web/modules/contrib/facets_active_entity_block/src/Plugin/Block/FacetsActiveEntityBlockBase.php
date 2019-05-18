<?php

namespace Drupal\facets_active_entity_block\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets\Plugin\facets\facet_source\SearchApiDisplay;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginManager;

abstract class FacetsActiveEntityBlockBase extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['enabled_facets'] = [
      '#type' => 'select',
      '#title' => $this->t('Enabled facets'),
      '#description' => $this->t('Select the facets to use with this block.'),
      '#options' => $this->getFacetOptions(),
      '#multiple' => TRUE,
      '#default_value' => isset($config['enabled_facets']) ? $config['enabled_facets'] : [],
    ];

    return $form;
  }

  /**
   * Initializes enabled facets.
   *
   * In this method all pre-query processors get called and their contents are
   * executed.
   *
   * @param FacetInterface[] $facets
   */
  protected function initFacets($facets) {
    /** @var ProcessorPluginManager $processorManager */
    $processorManager = \Drupal::service('plugin.manager.facets.processor');

    foreach ($facets as $facet) {
      foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_PRE_QUERY) as $processor) {
        /** @var PreQueryProcessorInterface $pre_query_processor */
        $pre_query_processor = $processorManager->createInstance($processor->getPluginDefinition()['id'], ['facet' => $facet]);
        if ($pre_query_processor instanceof PreQueryProcessorInterface) {
          $pre_query_processor->preQuery($facet);
        }
      }
    }


  }

  /**
   * @param bool $activeOnly
   * @param bool $enabledOnly
   * @return FacetInterface[]
   */
  protected function getFacets($activeOnly = FALSE, $enabledOnly = FALSE) {
    /** @var \Drupal\facets\FacetManager\DefaultFacetManager $facetManager */
    $facetManager = \Drupal::service('facets.manager');
    $facets = $facetManager->getEnabledFacets();
    $config = $this->getConfiguration();

    $include = [];
    if ($enabledOnly) {
      $include = isset($config['enabled_facets']) ? $config['enabled_facets'] : [];
    }

    $activeFacets = [];

    if ($activeOnly) {
      $this->initFacets($facets);
    }

    /**
     * @var string $id
     * @var FacetInterface $facet
     */
    foreach ($facets as $id => $facet) {
      if (!$activeOnly || !empty($facet->getActiveItems())) {
        if (empty($include) || array_key_exists($id, $include)) {
          $activeFacets[$id] = $facet;
        }
      }
    }

    return $activeFacets;
  }

  /**
   * Gets an array of facet options.
   *
   * @return array
   *  The facet options.
   */
  protected function getFacetOptions() {
    $facets = \Drupal::entityTypeManager()->getStorage('facets_facet')->loadMultiple();

    $options = [];

    /**
     * @var string $id
     * @var FacetInterface $facet
     */
    foreach ($facets as $id => $facet) {
      $entityType = $this->getEntityType($facet);

      if ($entityType) {
        $options[$id] = $facet->getName() . ' (' . $facet->getFacetSourceId() . ')';
      }
    }

    return $options;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('enabled_facets', $form_state->getValue('enabled_facets'));
  }

  /**
   * Tries to retrieve the associated entity type from the field configuration.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *  The facet to get the entity type from.
   *
   * @return bool|string
   *  The entity type identifier, or FALSE.
   */
  protected function getEntityType(FacetInterface $facet) {
    $entity_type = FALSE;
    $source = $facet->getFacetSource();

    // Support multiple entity types when using Search API.
    if ($source instanceof SearchApiDisplay) {
      $data_definition = $source->getIndex()
        ->getField($facet->getFieldIdentifier())->getDataDefinition();

      $entity_type = $data_definition->getSetting('target_type');

      if (empty($entity_type) && $data_definition instanceof FieldItemInterface) {
        $entity_type = $data_definition->getFieldDefinition()->getTargetEntityTypeId();
      }
    }

    return $entity_type;
  }
}
