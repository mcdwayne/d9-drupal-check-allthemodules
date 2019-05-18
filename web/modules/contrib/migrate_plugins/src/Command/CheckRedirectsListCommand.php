<?php

namespace Drupal\migrate_plugins\Command;

use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate_source_csv\CSVFileObject;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckRedirectsListCommand.
 *
 * @DrupalCommand (
 *     extension="migrate_plugins",
 *     extensionType="module"
 * )
 */
class CheckRedirectsListCommand extends ContainerAwareCommand {

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Redirect repository service.
   *
   * @var \Drupal\redirect\RedirectRepository
   */
  protected $redirectRepository;

  /**
   * CheckRedirectsListCommand constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\redirect\RedirectRepository $redirectRepository
   *   The redirect repository service.
   */
  public function __construct(
    FileSystemInterface $fileSystem,
    RedirectRepository $redirectRepository
  ) {
    parent::__construct();
    $this->fileSystem = $fileSystem;
    $this->redirectRepository = $redirectRepository;
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    $this->io = new DrupalStyle($input, $output);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('migrate_plugins:check_redirects')
      ->setDescription($this->trans('commands.migrate_plugins.check_redirects.description'))
      ->addOption(
        'csv-file',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.check_redirects.csv_file')
      )
      ->addOption(
        'redirect-column',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.check_redirects.redirect_column')
      )
      ->setAliases(['mpcr']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $csv_file = $input->getOption('csv-file');
    $redirect_column = $input->getOption('redirect-column');

    if (!$csv_file) {
      throw new InvalidOptionException('Redirects csv-file option is required.');
    }

    if (!$redirect_column) {
      throw new InvalidOptionException('CSV redirect-column option is required.');
    }

    // @var \Drupal\migrate_source_csv\CSVFileObject $csv
    $csv = $this->openSourceCsv($csv_file);
    $colum_names = $csv->getColumnNames();
    // Transform to single dimension array.
    $colum_names = array_map('reset', $colum_names);

    if (!in_array($redirect_column, $colum_names)) {
      throw new InvalidOptionException(sprintf('The redirect column name "%s" was not found in the CSV headers.', $redirect_column));
    }

    // Set report headers.
    $headers = [
      'Source',
      'Destination',
    ];

    // Get the total rows.
    $csv->seek(PHP_INT_MAX);
    $count_rows = $csv->key() + 1;

    $this->io->comment("Generating redirect rows report.");
    // Initialize a progress bar to show status of report generation.
    $progress = new ProgressBar($output, $count_rows);
    $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% ');

    // Init counter and report rows.
    $total_redirects_count = 0;
    $redirects_match_count = 0;
    $redirects_miss_count = 0;
    $rows = [];

    // Go to the first row.
    $csv->rewind();
    $csv->seek($csv->getHeaderRowCount());

    // Iterate all CSV rows to check redirects.
    while ($csv->valid()) {
      $destination_url = '<error>NULL</error>';
      // We start with next row due first correspond to headers.
      $csv->next();
      $row = $csv->current();
      // Redirect source URI to check.
      $uri = $row[$redirect_column];
      // Checks if current URI has an associated redirect.
      // @var \Drupal\redirect\Entity\Redirect $redirect
      $redirect = $this->redirectRepository->findBySourcePath($uri);

      // Report URIs without redirect.
      if ($redirect) {
        $redirect = reset($redirect);
        // @var \Drupal\Core\Url $url
        $url = $redirect->getRedirectUrl();
        $destination_url = $url->toString();
        $redirects_match_count++;
      }
      else {
        $redirects_miss_count++;
      }

      // Build the report rows.
      $rows[] = ['/' . $uri, $destination_url];

      $total_redirects_count++;
      $progress->advance();
    }

    // Show the report.
    $this->io->table($headers, $rows);
    $this->io->simple(sprintf("A total of %d redirects was processed.", $total_redirects_count));
    $this->io->simple(sprintf("%d has matched redirect.", $redirects_match_count));
    $this->io->simple(sprintf("%d missed redirect.", $redirects_miss_count));
  }

  /**
   * Load a CSV file using CSVFileObject class.
   *
   * @param string $file_path
   *   The child products catalog CSV file path.
   *
   * @return \Drupal\migrate_source_csv\CSVFileObject
   *   Returns a CSV object that allows easy access to data.
   */
  protected function openSourceCsv(string $file_path) {
    // Covert relative to full path.
    $index_count = [];
    // Expand tilde to HOME directory.
    $info = posix_getpwuid(posix_getuid());
    $file_path = preg_replace('/^~/', $info['dir'], $file_path);
    $real_path = $this->fileSystem->realpath($file_path);
    $this->io->simple(sprintf('<info>Opening source CSV file: "%s".</info>', $real_path));

    if (!is_file($real_path)) {
      throw new InvalidOptionException(sprintf('File %s not found, you must specific a valid CSV file path.', $file_path));
    }

    // Load the CSV file.
    $csv = new CSVFileObject($real_path);
    $csv->setFlags(\SplFileObject::READ_CSV);
    $csv->setCsvControl(',');
    $csv->setHeaderRowCount(1);

    $column_names = [];
    $csv->rewind();
    $csv->seek($csv->getHeaderRowCount() - 1);
    $row = $csv->current();

    foreach ($row as $header) {
      $header = trim($header);

      if (isset($index_count[$header])) {
        $index_count[$header]++;
        $header .= "-{$index_count[$header]}";
      }
      else {
        $index_count[$header] = 0;
      }

      $column_names[] = [$header => $header];
    }

    $csv->setColumnNames($column_names);
    $csv->rewind();

    return $csv;
  }

}
