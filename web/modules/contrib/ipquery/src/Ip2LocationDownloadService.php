<?php

namespace Drupal\ipquery;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Ip2LocationDownloadService.
 */
class Ip2LocationDownloadService extends BaseService {

  /**
   * The maximum amount of time to wait for a download.
   */
  const MAX_TIMEOUT = 600;

  /**
   * Number of rows to import at a time.
   */
  const BULK_IMPORT = 5000;

  /**
   * The download URL.
   */
  const URL = 'https://www.ip2location.com/download';

  /**
   * Count the import as successful if this percentage of data imports.
   */
  const SUCCESS_THRESHOLD = 0.99;

  /**
   * The temporary export directory.
   */
  const EXPORT_DIR = 'temporary://ip2location';

  /**
   * The zip file name.
   */
  const ZIP_FILE = 'download.zip';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The ipquery.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The Drupal state service.
   *
   * @var \State
   */
  protected $state;

  /**
   * The locking service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $filesystem;

  /**
   * The IP version being processed, either 4 or 6.
   *
   * @var int
   */
  protected $processingVersion;

  /**
   * Boolean on whether processingOptions['output'] is set.
   *
   * @var bool
   */
  protected $processingOptionsOutput;

  /**
   * The edition being processed.
   *
   * @var string
   */
  protected $processingEdition;

  /**
   * The CSV File being processed.
   *
   * @var string
   */
  protected $processingCsvFile;

  /**
   * Ip2LocationDownloadService constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \GuzzleHttp\Client $client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger factory.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, LockBackendInterface $lock, StateInterface $state, TimeInterface $time, DateFormatterInterface $date_formatter, ClientFactory $client_factory, LoggerChannelFactory $logger_factory, FileSystemInterface $filesystem) {
    $this->database = $database;
    $this->config = $config_factory->get('ipquery.settings');
    $this->lock = $lock;
    $this->state = $state;
    $this->time = $time;
    $this->dateFormatter = $date_formatter;
    $this->clientFactory = $client_factory;
    $this->logger = $logger_factory->get('ipquery');
    $this->filesystem = $filesystem;
  }

  /**
   * Download and import the ip2location data file.
   *
   * @param array $options
   *   The options to use while processing:
   *   - drush: called from drush, displays output to terminal.
   *   - cron: called from cron, checks times.
   */
  public function process($context = []) {
    // Allow only one process at a time.
    if (!$this->lock->acquire('ipquery', 3600)) {
      if (isset($context['drush'])) {
        if (!drush_confirm(dt('Processing is locked by another process. Do you want to continue anyways?'))) {
          return;
        }
      }
      else {
        $this->logger->warning('ipquery lock busy.');
        return;
      }
    }

    // Process everything.
    $versions = [];
    if (empty($context['ipv6only'])) {
      $versions[] = 4;
    }
    if (empty($context['ipv4only']) && $this->isIpv6Supported()) {
      $versions[] = 6;
    }
    foreach ($versions as $version) {
      try {
        if ($this->setup($version, $context)) {
          $this
            ->download()
            ->extract()
            ->import()
            ->cleanup();
        }
        $status = TRUE;
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
        $this->cleanup();
        $status = FALSE;
      }
    }

    // We are done processing, allow new imports.
    $this->lock->release('ipquery');

    return $status;
  }

  /**
   * Setup the temporary directory.
   *
   * @param int $verson
   *   The IP version, either 4 or 6.
   * @param array $context
   *   The context, see ::process().
   *
   * @return $bool
   *   Return TRUE to continue, FALSE to stop.
   */
  protected function setup($version = 4, $context = []) {
    // Set class/state variables.
    $this->processingVersion = $version;
    $this->processingOptionsOutput = isset($context['drush']);
    $this->processingEdition = $this->getEdition($version);
    $this->processingCsvFile = $this->getCsvFile($this->processingEdition);

    // Determine if the file needs to be updated.
    if (isset($context['cron'])) {
      $now = $this->time->getRequestTime();
      $last = $this->getLast($this->processingEdition);
      if ($last) {
        $first_wed = strtotime('first Wednesday', $now);
        if ($first_wed > strtotime('midnight', $now) || $first_wed <= strtotime('midnight', $last)) {
          return FALSE;
        }
      }
    }
    elseif (isset($context['drush'])) {
      $now = $this->time->getRequestTime();
      $last = $this->getLast($this->processingEdition);
      if ($last >= $now - 86400) {
        if (!drush_confirm(dt('Do you you want to re-process !edition, last downloaded !last ago?', [
          '!edition' => $this->processingEdition,
          '!last' => $this->dateFormatter->formatTimeDiffSince($last)
        ]))) {
          return FALSE;
        }
      }
    }

    // Create the temporary directory.
    $dir = self::EXPORT_DIR;
    file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

    return TRUE;
  }

  /**
   * Remove the temporary files.
   *
   * @return $this
   */
  protected function cleanup() {
    // Remove the temporary directory and files.
    file_unmanaged_delete_recursive(self::EXPORT_DIR);

    return $this;
  }

  /**
   * Download the file and save it.
   *
   * @return $this
   *
   * @throws \Exception
   */
  protected function download() {
    // Skip re-downloading recent downloads. This is mainly for testing, when
    // import fails. ip2location limits downloads to 5 per hour.
    $full_zip_file = self::EXPORT_DIR . '/' . self::ZIP_FILE;
    if (file_exists($full_zip_file) && filemtime($full_zip_file) > $this->time->getRequestTime() - 86400) {
      return $this;
    }

    // Make sure there is a token.
    $token = $this->config->get('ip2location_token');
    if (!$token) {
      throw new \Exception('Configure an ip2location_token before downloading.');
    }

    // Display output.
    if ($this->processingOptionsOutput) {
      drush_print(dt('Downloading !edition...', [
        '!edition' => $this->processingEdition,
      ]));
    }

    // Create the URI to download.
    $url = Url::fromUri(self::URL, [
      'query' => [
        'token' => $token,
        'file' => $this->processingEdition,
      ],
    ])->toString();

    // Download the file.
    $response = $this->clientFactory->fromOptions([
      'timeout' => self::MAX_TIMEOUT,
      'headers' => [
        'User-Agent' => 'Drupal/' . \Drupal::VERSION . ' (+https://www.drupal.org/) ' . \GuzzleHttp\default_user_agent(),
        'Content-Type' => 'application/octet-stream',
      ],
    ])->get($url);
    if (!$response) {
      throw new \Exception('ip2location download failure for ' . $this->processingEdition);
    }
    $status = $response->getStatusCode();

    // Check that the HTTP gave a valid response.
    if ($status != 200) {
      throw new \Exception('ip2location download error for ' . $this->processingEdition . ' response: ' . $status);
    }

    // Sanity check that the file is a reasonable size. Small files contain
    // error messages.
    $data = $response->getBody();
    if ($data->getSize() < 256) {
      // @todo: sanitize data.
      throw new \Exception('ip2location invalid download for ' . $this->processingEdition . ': ' . $data);
    }

    // Save the file.
    if (file_unmanaged_save_data($data, $full_zip_file, FILE_EXISTS_REPLACE) === FALSE) {
      throw new \Exception('ip2location could not save zip file ' . $full_zip_file . '.');
    }

    return $this;
  }

  /**
   * Extract the CSV file from the download.
   *
   * @return $this
   *
   * @throws \Exception
   */
  protected function extract() {
    // Skip extracting if the file already exists.
    $full_csv_file = self::EXPORT_DIR . '/' . $this->processingCsvFile;
    if (file_exists($full_csv_file)) {
      return $this;
    }

    // Display output.
    if ($this->processingOptionsOutput) {
      drush_print(dt('Extracting !file...', [
        '!file' => $this->processingCsvFile,
      ]));
    }

    // Extract the CSV file.
    $zip = new \ZipArchive;
    $dir = self::EXPORT_DIR;
    $full_zip_file = $dir . '/' . self::ZIP_FILE;
    if (!$zip->open($this->filesystem->realpath($full_zip_file))) {
      throw new \Exception('ip2location could not open zip file: ' . $full_zip_file . '.');
    }
    if (!$zip->extractTo($this->filesystem->realpath($dir), [$this->processingCsvFile])) {
      throw new \Exception('ip2location could not extract CSV file: ' . $this->processingCsvFile . '.');
    }

    return $this;
  }

  /**
   * Import the CSV file.
   *
   * @todo: handle IPV6 addresses.
   *
   * @return $this
   *
   * @throws \Exception
   */
  protected function import() {
    // Open the CSV file.
    $full_csv_file = SELF::EXPORT_DIR . '/' . $this->processingCsvFile;
    $handle = fopen($full_csv_file, "r");
    if ($handler === FALSE) {
      throw new \Exception('ip2location could not read CSV file.');
    }

    // Delete the existing data.
    $this
      ->database
      ->truncate($this->processingVersion == 6 ? 'ipquery6' : 'ipquery')
      ->execute();

    // Display output.
    if ($this->processingOptionsOutput) {
      drush_print(dt('Importing !file...', [
        '!file' => $this->processingCsvFile,
      ]));
    }

    // Import the new data.
    $size = filesize($full_csv_file);
    $done = '';
    if ($this->processingVersion == 6) {
      $fields = [
        'ip_low_left',
        'ip_low_right',
        'ip_high_left',
        'ip_high_right',
      ];
    }
    else {
      $fields = [
        'ip_low',
        'ip_high',
      ];
    }
    $fields = array_merge($fields, [
      'country',
      'timezone',
      'region',
      'city',
    ]);
    $count = 0;
    $values = [];
    if ($this->processingOptionsOutput) {
      print "0.0%";
    }
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      if ($this->processingVersion == 6) {
        list($ip_low_left, $ip_low_right) = $this->numberToLong($data[0]);
        list($ip_high_left, $ip_high_right) = $this->numberToLong($data[1]);
        $value = [
          'ip_low_left' => $ip_low_left,
          'ip_low_right' => $ip_low_right,
          'ip_high_left' => $ip_high_left,
          'ip_high_right' => $ip_high_right,
        ];
      }
      else {
        $value = [
          'ip_low' => $data[0],
          'ip_high' => $data[1],
        ];
      }
      $value += [
        'country' => $data[2],
        'timezone' => isset($data[9]) ? substr($data[9], 0, 6) : NULL,
        'region' => isset($data[4]) ? substr($data[4], 0, 32) : NULL,
        'city' => isset($data[5]) ? substr($data[5], 0, 32) : NULL,
      ];
      $values[] = $value;
      if (count($values) >= self::BULK_IMPORT) {
        // Load the row values.
        $count += $this->loadRowValues($fields, $values);
        $values = [];

        // Prevent timeouts and print progress message.
        $pos = ftell($handle);
        $percent = sprintf("%.1f", 100 * $pos / $size);
        if ($percent != $done) {
          // Keep the process alive by increasing the process time limit.
          set_time_limit(60);

          // Display progress message.
          if ($this->processingOptionsOutput) {
            print str_repeat(chr(13), strlen($done) + 1)  . "$percent%";
          }

          $done = $percent;
        }
      }
    }
    if ($values) {
      // Load the last rows.
      $count += $this->loadRowValues($fields, $values);
    }
    if ($this->processingOptionsOutput) {
      // Finalize the progress message.
      print ' - ' . t('@count loaded', ['@count' => $count]) . "\n";
    }
    fclose($handle);

    // Set the last import time.
    $this->setLast($this->processingEdition);

    return $this;
  }

  /**
   * Load all of the rows.
   *
   * @param array $fields
   *   The fields to insert.
   * @param array $rows
   *   The rows to insert.
   *
   * @return int
   *   The number of rows inserted.
   */
  protected function loadRowValues(array $fields, array $rows) {
    // Bulk insert the rows.
    $insert = $this->database
      ->insert($this->processingVersion == 6 ? 'ipquery6' : 'ipquery')
      ->fields($fields);
    foreach ($rows as $row) {
      $insert->values($row);
    }
    try {
      $insert->execute();
    }
    catch (\Exception $e) {
      // Log a shortened message.
      $message = $e->getMessage();
      $pos = strpos($message, ': INSERT INTO');
      $pos = $pos === FALSE || $pos > 512 ? 512 : $pos;
      $message = substr($message, 0, $pos);
      $this->logger->error($message);
      return 0;
    }
    return count($rows);
  }

  /**
   * Return the timestamp the edition was last updated.
   *
   * @param string $edition
   *   The edition.
   *
   * @return int
   *   The timestamp the edition was last updated.
   */
  public function getLast($edition = NULL) {
    if (!$edition) {
      $edition = $this->getEdition(4);
    }
    return $this->state->get("ip2location_{$edition}_time");
  }

  /**
   * Set the timestamp of when the edition was last updated.
   *
   * @param string $edition
   *   The edition.
   * @param int $now
   *   The timestamp.
   */
  public function setLast($edition, $now = NULL) {
    if ($now === NULL) {
      $now = $this->time->getRequestTime();
    }
    $this->state->set("ip2location_{$edition}_time", $now);
  }

  /**
   * Get the edition to download.
   *
   * @return string
   *   The edition to download.
   */
  public function getEdition($version) {
    $edition = $this->config->get('ip2location_edition');
    if ($version == 6) {
      $edition .= 'IPV6';
    }
    return $edition;
  }

  /**
   * Get the CSV file to extract and import.
   *
   * @param string $edition
   *   The edition.
   *
   * @return string
   *   The CSV file to extract and import.
   */
  protected function getCsvFile($edition) {
    switch ($edition) {
      case 'DB1':
        return 'IP2LOCATION-COUNTRY.CSV';
      case 'DB11':
        return 'IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE-ZIPCODE-TIMEZONE.CSV';
      case 'DB11IPV6':
        return 'IPV6-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE-ZIPCODE-TIMEZONE.CSV';
    }
  }

}

