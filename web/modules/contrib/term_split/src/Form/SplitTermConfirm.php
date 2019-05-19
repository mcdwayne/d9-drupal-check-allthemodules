<?php

namespace Drupal\term_split\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Console\Command\Shared\TranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\term_split\TermSplitterInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Split terms form.
 */
class SplitTermConfirm extends FormBase {

  use TranslationTrait;

  /**
   * The term splitter.
   *
   * @var \Drupal\term_split\TermSplitterInterface
   */
  private $termSplitter;

  /**
   * The private temporary storage factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  private $tempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('term_split.splitter'),
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * SplitTerm constructor.
   *
   * @param \Drupal\term_split\TermSplitterInterface $termSplitter
   *   The term splitter service.
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   The temp store factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(TermSplitterInterface $termSplitter, PrivateTempStoreFactory $tempStoreFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->termSplitter = $termSplitter;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_term_split_form_confirm';
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(camelCase)
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $term = $this->getSourceTerm();
    $settings = $this->getSplitSettings();
    $columnA = 'a';
    $columnB = 'b';

    $args = [
      '%term' => $term->label(),
      '%a' => $settings[$columnA]['name'],
      '%b' => $settings[$columnB]['name'],
    ];
    $form['message'] = [
      '#markup' => $this->t("Pressing confirm will split %term into %a and %b and update any references to it as is described below. This will delete %term and can not be reversed.", $args),
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => [$settings[$columnA]['name'], $settings[$columnB]['name']],
    ];

    $mappedToA = count($settings[$columnA]['nids']);
    $mappedToB = count($settings[$columnB]['nids']);
    $rowCount = $mappedToA > $mappedToB ? $mappedToA : $mappedToB;

    for ($currentRow = 0; $currentRow < $rowCount; $currentRow++) {
      $form['table'][$currentRow][$columnA] = $this->getCellContent($columnA, $currentRow);
      $form['table'][$currentRow][$columnB] = $this->getCellContent($columnB, $currentRow);
    }

    $form['actions']['confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->getCancelUrl($term),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(camelCase)
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->getSplitSettings();

    $term = $this->getSourceTerm();
    $target1Name = $settings['a']['name'];
    $target2Name = $settings['b']['name'];
    $target1Nids = $settings['a']['nids'];
    $target2Nids = $settings['b']['nids'];

    $this->termSplitter->splitInTo($term, $target1Name, $target2Name, $target1Nids, $target2Nids);
  }

  /**
   * A callback for the form title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  public function titleCallback() {
    $arguments = ['%label' => $this->getSourceTerm()->label()];
    return new TranslatableMarkup("Are you sure you wish to split %label?", $arguments);
  }

  /**
   * Creates a cancel url object.
   *
   * @param \Drupal\taxonomy\TermInterface $sourceTerm
   *   The source term.
   *
   * @return \Drupal\Core\Url
   *   The cancel url.
   *
   * @SuppressWarnings(static)
   */
  private function getCancelUrl(TermInterface $sourceTerm) {
    $fallbackUrl = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $sourceTerm->id()]);

    $query = $this->getRequest()->query;
    if (!$query->has('destination')) {
      return $fallbackUrl;
    }

    $options = UrlHelper::parse($query->get('destination'));
    try {
      return Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
    }
    catch (\InvalidArgumentException $e) {
      return $fallbackUrl;
    }
  }

  /**
   * Loads the source term.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The loaded source term.
   */
  protected function getSourceTerm() {
    $settings = $this->getSplitSettings();

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->load($settings['tid']);

    return $term;
  }

  /**
   * Loads all nodes referencing the term to be split.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The loaded nodes.
   */
  protected function loadNodesReferencingSourceTerm() {
    $settings = $this->getSplitSettings();

    $allConfiguredNids = array_merge($settings['a']['nids'], $settings['b']['nids']);
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($allConfiguredNids);
    return $nodes;
  }

  /**
   * Retrieves the contents of a table cell.
   *
   * @param string $currentColumn
   *   Either 'a' or 'b'.
   * @param int $currentRow
   *   The current row.
   *
   * @return array
   *   A renderable array containing the cell's content.
   */
  protected function getCellContent($currentColumn, $currentRow) {
    $settings = $this->getSplitSettings();

    $cellContent = ['#markup' => ''];

    if (isset($settings[$currentColumn]['nids'][$currentRow])) {
      $nid = $settings[$currentColumn]['nids'][$currentRow];
      $nodes = $this->loadNodesReferencingSourceTerm();
      $cellContent = [
        '#type' => 'link',
        '#title' => $nodes[$nid]->label(),
        '#url' => $nodes[$nid]->toUrl(),
      ];
    }

    return $cellContent;
  }

  /**
   * Retrieves the split settings from the temp store.
   *
   * @return array
   *   The split settings.
   */
  protected function getSplitSettings() {
    return $this->tempStoreFactory->get('term_split')->get('term_to_split');
  }

}
