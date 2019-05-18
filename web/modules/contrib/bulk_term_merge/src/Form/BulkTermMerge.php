<?php

namespace Drupal\bulk_term_merge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\bulk_term_merge\DuplicateFinderService;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkTermMerge.
 */
class BulkTermMerge extends FormBase {
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
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  private $vocabulary;

  /**
   * EntityTypeInfoController constructor.
   *
   * @param \Drupal\bulk_term_merge\DuplicateFinderService
   *   The duplicate finder service.
   */
  public function __construct(DuplicateFinderService $duplicate_finder, PrivateTempStoreFactory $tempStoreFactory) {
    $this->duplicate_finder = $duplicate_finder;
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bulk_term_merge.duplicate_finder'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_bulk_merge_terms';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL) {
    $this->vocabulary = $taxonomy_vocabulary;
    $duplicates = $this->duplicate_finder->findDuplicates($taxonomy_vocabulary->get('vid'));
    $options = [];

    foreach ($duplicates as $duplicate) {
      $options[$duplicate->name] = [
        'name' => $duplicate->name,
        'count' => $duplicate->count,
      ];
    }

    $form['term_names'] = [
      '#type' => 'tableselect',
      '#header' => [
        'name' => $this->t('Name'),
        'count' => $this->t('Count'),
      ],
      '#empty' => $this->t('No duplicates, hurray!'),
      '#options' => $options,
      '#multiple' => FALSE,
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
    $term_name = $form_state->getValue('term_names');
    $termStore = $this->tempStoreFactory->get('term_merge');
    $termStore->set('active_term_name', $term_name);

    $routeName = 'entity.taxonomy_vocabulary.bulk_merge_active';
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
