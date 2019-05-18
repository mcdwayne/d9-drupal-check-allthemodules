<?php

namespace Drupal\elastic_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elastic_search\Elastic\ElasticIndexManager;
use Drupal\elastic_search\Entity\ElasticIndex;
use Drupal\elastic_search\ValueObject\BatchDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UpdateSelectIndicesMappingsForm.
 *
 * @package Drupal\elastic_search\Form
 */
class UpdateSelectIndicesMappingsForm extends FormBase {

  protected $indexManager;

  /**
   * Index chunk size is handled seperately as they cannot be created in bulk and large numbers will cause gateway
   * timeouts
   *
   * @var int
   */
  protected $indexChunkSize;

  /**
   * UpdateSelectIndicesDocumentsForm constructor.
   *
   * @param \Drupal\elastic_search\Elastic\ElasticIndexManager $manager
   */
  public function __construct(ElasticIndexManager $manager,
                              $indexBatchSize) {
    $this->indexManager = $manager;
    $this->indexChunkSize = (int) $indexBatchSize;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    $conf = $container->get('config.factory')->get('elastic_search.server');
    return new static($container->get('elastic_search.indices.manager'),
                      $conf->get('advanced.index_batch_size'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_select_indices_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $indices = ElasticIndex::loadMultiple();
    $ak = array_keys($indices);
    $options = $result = array_combine($ak, $ak);

    $form['warning'] = [
      '#markup' => 'WARNING: Updating too many indices at once may result in excessive memory pressure on your cluster which may result in mapping failures',
    ];

    $form['indices'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Indices'),
      '#description' => $this->t('Select which indices to update mappings for'),
      '#options'     => $options,
      '#size'        => 20,
      '#multiple'    => TRUE,
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $types = $form_state->getValue('indices');
    $entities = ElasticIndex::loadMultiple($types);
    return $this->mappingUpdates($entities);

  }

  /**
   * This duplicates a lot of code from the index controller and perhaps a shared intermediate class would be
   * appropriate.
   *
   * @param array $elasticIndices
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function mappingUpdates(array $elasticIndices = []) {
    $chunks = array_chunk($elasticIndices, $this->indexChunkSize);
    return $this->executeBatch($chunks,
                               '\Drupal\elastic_search\Controller\IndexController::processUpdateMappingBatch',
                               '\Drupal\elastic_search\Controller\IndexController::finishBatch',
                               'mapping');
  }

  /**
   * @param array  $chunks
   * @param string $opCallback
   * @param string $finishCallback
   * @param string $messageKey
   */
  protected function executeBatch(array $chunks, string $opCallback, string $finishCallback, string $messageKey = '') {

    $ops = [];
    foreach ($chunks as $chunkedIndices) {
      $ops[] = [$opCallback, [$chunkedIndices]];
    }
    $batch = new BatchDefinition($ops,
                                 $finishCallback,
                                 $this->t('Processing index ' . $messageKey . ' batch'),
                                 $this->t('Index ' . $messageKey . ' is starting.'),
                                 $this->t('Processed @current out of @total.'),
                                 $this->t('Encountered an error.')
    );
    batch_set($batch->getDefinitionArray());

  }

}
