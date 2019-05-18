<?php

namespace Drupal\bulk_term_merge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\bulk_term_merge\DuplicateFinderService;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkTermMerge.
 */
class BulkTermMergeActive extends FormBase {
  /**
   * The duplicate finder service.
   *
   * @var \Drupal\bulk_term_merge\DuplicateFinderService
   */
  protected $duplicate_finder;

  /**
   * The private temporary storage factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  private $tempStoreFactory;

  /**
   * The term storage handler.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  private $vocabulary;

  /**
   * EntityTypeInfoController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\bulk_term_merge\DuplicateFinderService
   *   The duplicate finder service.
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   The private temporary storage factory.
   */
  public function __construct(DuplicateFinderService $duplicate_finder, EntityTypeManagerInterface $entityTypeManager, PrivateTempStoreFactory $tempStoreFactory) {
    $this->duplicate_finder = $duplicate_finder;
    $this->entityTypeManager = $entityTypeManager;
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bulk_term_merge.duplicate_finder'),
      $container->get('entity_type.manager'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_bulk_merge_active';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL) {
    $this->vocabulary = $taxonomy_vocabulary;
    $duplicates = $this->duplicate_finder->findDuplicates($taxonomy_vocabulary->get('vid'));
    $options = [];

    $termStore = $this->tempStoreFactory->get('term_merge');
    $active_term_name = $termStore->get('active_term_name');

    $terms = $this->duplicate_finder->getTermIds($active_term_name, $taxonomy_vocabulary->get('vid'));

    $term_ids = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $active_term_name . ' (' . $term->tid . ')';
      $term_ids[] = $term->tid;
    }

    $termStore->set('terms', $term_ids);

    $form['term_ids'] = [
      '#type' => 'radios',
      '#title' => $this->t('Merge all to'),
      '#description' => $this->t('Select the term id that all duplicates should be merged into.'),
      '#options' => $options,
    ];

    $form['process'] = [
      '#type' => 'submit',
      '#value' => $this->t('Process'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term_id = $form_state->getValue('term_ids');
    $termStore = $this->tempStoreFactory->get('term_merge');
    $term = $this->termStorage->load($term_id);
    $termStore->set('target', $term);

    // Remove target id from list of terms.
    $terms = $termStore->get('terms');
    if (($key = array_search($term_id, $terms)) !== FALSE) {
      unset($terms[$key]);
    }
    $termStore->set('terms', $terms);

    $routeName = 'entity.taxonomy_vocabulary.merge_confirm';
    $routeParameters['taxonomy_vocabulary'] = $this->vocabulary->id();
    $form_state->setRedirect($routeName, $routeParameters);
  }

  /**
   * Returns the page title.
   *
   * @param $taxonomy_vocabulary
   *
   * @return string
   */
  public function getTitle(VocabularyInterface $taxonomy_vocabulary) {
    return $this->t('Duplicates in %vid', [
      '%vid' => $taxonomy_vocabulary->get('vid'),
    ]);
  }

}
