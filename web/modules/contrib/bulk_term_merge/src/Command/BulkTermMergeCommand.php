<?php

namespace Drupal\bulk_term_merge\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\bulk_term_merge\DuplicateFinderService;
use Drupal\term_merge\TermMerger;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class FindCommand.
 *
 * @DrupalCommand (
 *     extension="bulk_term_merge",
 *     extensionType="module"
 * )
 */
class BulkTermMergeCommand extends ContainerAwareCommand {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\bulk_term_merge\DuplicateFinderService
   */
  protected $duplicate_finder;

  /**
   * @var \Drupal\term_merge\TermMerger
   */
  protected $term_merger;

  /**
   * Vocabulary ids.
   *
   * @var array
   */
  protected $vocabularies;

  /**
   * Constructs a new FindCommand object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, DuplicateFinderService $duplicate_finder, TermMerger $term_merger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->duplicate_finder = $duplicate_finder;
    $this->term_merger = $term_merger;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('taxonomy:bulk_term_merge')
      ->setDescription($this->trans('commands.bulk_term_merge.default.description'))
      ->setHelp($this->trans('commands.bulk_term_merge.default.help'))
      ->setAliases(['btm'])
      ->addArgument(
        'vocabulary',
        InputArgument::REQUIRED,
        $this->trans('commands.bulk_term_merge.default.arguments.vocabulary'),
        null
      )
      ->addArgument(
        'term',
        InputArgument::OPTIONAL,
        $this->trans('commands.bulk_term_merge.default.arguments.term'),
        null
      )
      ->addOption(
        'dry-run',
        'dry',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.bulk_term_merge.default.options.dry'),
        FALSE
      );
  }

 /**
  * {@inheritdoc}
  */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    $this->setVocabularyIds();
  }

 /**
  * {@inheritdoc}
  */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $vocabulary = $input->getArgument('vocabulary');
    $term = $input->getArgument('term');

    if (!$vocabulary || !in_array($vocabulary, $this->vocabularies)) {
      $v_question = new Question($this->trans('commands.bulk_term_merge.default.questions.vocabulary'));
      $v_question->setAutocompleterValues($this->vocabularies);
      $vocabulary = $this->getIo()->askQuestion($v_question);
      $input->setArgument('vocabulary', $vocabulary);
    }

    if (!$term) {
      $duplicates = $this->duplicate_finder->findDuplicates($vocabulary);
      if (count($duplicates) > 0) {
        $rows = [];
        $terms = [];
        foreach ($duplicates as $duplicate) {
          $terms[] = $duplicate->name;
          $rows[] = [
            'name' => $duplicate->name,
            'count' => $duplicate->count,
          ];
        }
        $this->getIo()->table(
          ['name', 'count'],
          $rows
        );
        $t_question = new Question($this->trans('commands.bulk_term_merge.default.questions.term'));
        $t_question->setAutocompleterValues($terms);
        $term = $this->getIo()->askQuestion($t_question);
        $input->setArgument('term', $term);
      } else {
        $this->getIo()->comment($this->trans('commands.bulk_term_merge.default.messages.no_duplicates'));
        // @todo is this the best way to terminate from a command?
        exit();
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $vocabulary = $input->getArgument('vocabulary');
    $term = $input->getArgument('term');
    $dry = $input->getOption('dry-run');

    if ($vocabulary && $term) {
      $results = $this->duplicate_finder->getTermIds($term, $vocabulary);
      $tids = [];
      foreach ($results as $result) {
        $tids[] = $result->tid;
      }

      $question = new ChoiceQuestion($this->trans('commands.bulk_term_merge.default.questions.primary_term'), $tids);
      $primary = $this->getIo()->askQuestion($question);
      $rest = array_diff($tids, [$primary]);

      $confirm = new ConfirmationQuestion($this->trans('commands.bulk_term_merge.default.questions.continue'), false);
      if ($this->getIo()->askQuestion($confirm)) {
        $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
        $termsToMerge = $termStorage->loadMultiple($rest);
        $targetTerm = $termStorage->load($primary);
        $this->term_merger->mergeIntoTerm($termsToMerge, $targetTerm);
      }
    }
  }

  /**
   * Sets values for $vocabularies with vocabulary ids.
   *
   * This array is used in autocomplete as well as input validation.
   */
  private function setVocabularyIds() {
    $vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabularies = $vocabularyStorage->loadMultiple();

    foreach ($vocabularies as $vocabulary) {
      $this->vocabularies[] = $vocabulary->id();
    }
  }
}
