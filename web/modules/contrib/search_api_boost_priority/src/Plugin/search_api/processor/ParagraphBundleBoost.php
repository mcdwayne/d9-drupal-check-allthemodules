<?php

namespace Drupal\search_api_boost_priority\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\Utility;

/**
 * Adds a boost to indexed items based on Paragraph Bundle.
 *
 * @SearchApiProcessor(
 *   id = "search_api_boost_priority_paragraphbundle",
 *   label = @Translation("Paragraph Bundle specific boosting"),
 *   description = @Translation("Adds a boost to indexed items based on Paragraph Bundle."),
 *   stages = {
 *     "preprocess_index" = 0,
 *   }
 * )
 */
class ParagraphBundleBoost extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The available boost factors.
   *
   * @var string[]
   */
  protected static $boostFactors = [
    '0.0' => '0.0',
    '0.1' => '0.1',
    '0.2' => '0.2',
    '0.3' => '0.3',
    '0.5' => '0.5',
    '0.8' => '0.8',
    '1.0' => '1.0',
    '2.0' => '2.0',
    '3.0' => '3.0',
    '5.0' => '5.0',
    '8.0' => '8.0',
    '13.0' => '13.0',
    '21.0' => '21.0',
  ];

  /**
   * Can only be enabled for an index that indexes paragraph.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $hasBundles = self::hasBundles();

    foreach ($index->getDatasources() as $datasource) {
      $allowedEntityTypes = self::allowedEntityTypes();
      $entityType = $datasource->getEntityTypeId();

      if (in_array($entityType, $allowedEntityTypes) && $hasBundles) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Whitelist of allowed entity types.
   *
   * @return array
   *   Whitelist of allowed entity types.
   */
  private static function allowedEntityTypes() {
    return [
      'node',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'boost_table' => [
        'weight' => '0.0',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form['boost_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Role'),
        $this->t('Boost'),
      ],
    ];

    // Get a list of available bundle_types defined for paragraphs.
    if ($bundles = $this->getBundles()) {

      // Make a dummy array to add custom weight.
      foreach ($bundles as $bundle_id => $bundle_name) {
        if (isset($this->configuration['boost_table'][$bundle_id]['weight'])) {
          $weight = $this->configuration['boost_table'][$bundle_id]['weight'];
        }
        elseif (isset($this->configuration['boost_table']['weight'])) {
          $weight = $this->configuration['boost_table']['weight'];
        }

        $bundle_weight[$bundle_id]['bundle_id'] = $bundle_id;
        $bundle_weight[$bundle_id]['bundle_name'] = $bundle_name;
        $bundle_weight[$bundle_id]['weight'] = $weight;
      }

      // Sort weights.
      uasort($bundle_weight, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

      // Loop over each bundle and create a form row.
      foreach ($bundle_weight as $bundle_id => $bundle) {
        $weight = $bundle['weight'];
        $bundle_name = $bundle['bundle_name'];

        // Table columns containing raw markup.
        $form['boost_table'][$bundle_id]['label']['#plain_text'] = $bundle_name;

        // Weight column element.
        $form['boost_table'][$bundle_id]['weight'] = [
          '#type' => 'select',
          '#title' => t('Weight for @title', ['@title' => $bundle_name]),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#options' => static::$boostFactors,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setValues($values);
    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    foreach ($items as $item) {
      $boost = NULL;
      $entityTypeId = $item->getDatasource()->getEntityTypeId();

      // TODO Extend for other entities.
      switch ($entityTypeId) {
        case 'node':
          $paragraphBundles = $this->getParagraphBundles($item->getOriginalObject());
          $this->getStatBoost($paragraphBundles);
          break;
      }

      if ($boost) {
        $item->setBoost($boost);
      }
    }
  }

  /**
   * Retrieves the paragraph bundles related to an indexed search object.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   A search object that is being indexed.
   *
   * @return array
   *   Array of paragraph bundles related to that search object.
   */
  protected function getParagraphBundles(ComplexDataInterface $item) {
    $item = $item->getValue();
    $paragraphBundles = [];
    $allBundles = array_keys($this->getBundles());

    // Only check nodes,
    // paragraphs embeded in other entity types not yet supported.
    if ($item instanceof NodeInterface) {
      // Get all the fields for this entity and loop over field definitions.
      foreach ($item->getFieldDefinitions() as $fieldDefinition) {
        // Only interested in the ones that are paragraph entity ref fields.
        if ($fieldDefinition->getType() == 'entity_reference_revisions' && $fieldDefinition->getSetting('target_type') == 'paragraph') {

          // Load up the specific paragraph entity ref fields.
          $paragraphField = $fieldDefinition->getName();

          // There might be multiple entity refs per field, loop over each
          // entity ref and load up the referenced paragraph entity.
          foreach ($item->$paragraphField->referencedEntities() as $paragraphReferencedEntity) {

            // Each paragraph entity will have its own fields FFS,
            // So loop over each field and get the
            // paragraph bundle to which these field belong.
            foreach ($paragraphReferencedEntity->getFieldDefinitions() as $paraFieldDefinition) {
              $currentBundle = $paraFieldDefinition->getTargetBundle();

              // Only interested in fields that have a bundle
              // Also ensure the bundle is one of the paragraph defined bundles.
              if (!empty($currentBundle) && in_array($currentBundle, $allBundles)) {
                $paragraphBundles[] = $currentBundle;
              }
            }
          }
        }
      }

      // Finally, remove the dupe bundles.
      $paragraphBundles = array_unique($paragraphBundles);
    }

    // YAAY what a pickle.
    return $paragraphBundles;
  }

  /**
   * Retrieves the boost related to a Paragraph Bundle.
   *
   * @param array $paragraphBundles
   *   Paragraph Bundles.
   *
   * @return float
   *   Boost Value.
   */
  protected function getStatBoost(array $paragraphBundles) {
    // Get configured stats.
    $boosts = $this->configuration['boost_table'];

    // Construct array for bundle sorting.
    foreach ($paragraphBundles as $paragraphBundle) {
      $bundleWeights[] = (double) $boosts[$paragraphBundle]['weight'];
    }

    // Get highest weight for this bundle.
    $boost = max($bundleWeights);
    return $boost;
  }

  /**
   * Get paragraph bundles.
   *
   * @return array
   *   array of paragraph bundles
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getBundles() {
    if ($this->hasBundles()) {
      $entity_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('paragraph');
      $bundles = [];

      foreach ($entity_bundles as $bundle_id => $bundle_info) {
        $bundles[$bundle_id] = isset($bundle_info['label']) ? Utility::escapeHtml($bundle_info['label']) : $bundle_id;
      }

      return $bundles;
    }
  }

  /**
   * Does paragraph entity have any bundles?
   *
   * @return bool
   *   Does it have bundles?
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function hasBundles() {
    return \Drupal::entityTypeManager()->getDefinition('paragraph')->hasKey('bundle');
  }

}
