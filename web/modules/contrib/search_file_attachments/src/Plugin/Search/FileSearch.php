<?php

namespace Drupal\search_file_attachments\Plugin\Search;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\Search\SearchQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes a keyword search for files against {file_managed} database table.
 *
 * @SearchPlugin(
 *   id = "file_search",
 *   title = @Translation("File")
 * )
 */
class FileSearch extends SearchPluginBase implements AccessibleInterface, SearchIndexingInterface {

  /**
   * A database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * A config object for 'search.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchSettings;

  /**
   * A config object for 'search_file_attachments.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $moduleSettings;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Drupal account to use for checking for access to advanced search.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * An array of additional rankings from hook_ranking().
   *
   * @var array
   */
  protected $rankings;

  /**
   * An array of file mimetypes that should be included in the index.
   *
   * @var array
   */
  protected $includedMimetypes;

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('config.factory')->get('search.settings'),
      $container->get('config.factory')->get('search_file_attachments.settings'),
      $container->get('language_manager'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a \Drupal\node\Plugin\Search\NodeSearch object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   An entity manager object.
   * @param \Drupal\Core\Config\Config $search_settings
   *   A config object for 'search.settings'.
   * @param \Drupal\Core\Config\Config $module_settings
   *   A config object for 'search_file_attachments.settings'.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The $account object to use for checking for access to advanced search.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityManagerInterface $entity_manager, Config $search_settings, Config $module_settings, LanguageManagerInterface $language_manager, AccountInterface $account = NULL) {
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->searchSettings = $search_settings;
    $this->moduleSettings = $module_settings;
    $this->languageManager = $language_manager;
    $this->account = $account;

    $this->setIncludedMimetypes();

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'search files');
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if ($this->isSearchExecutable()) {
      $results = $this->findResults();

      if ($results) {
        return $this->prepareResults($results);
      }
    }

    return array();
  }

  /**
   * Queries to find search results, and sets status messages.
   *
   * This method can assume that $this->isSearchExecutable() has already been
   * checked and returned TRUE.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Results from search query execute() method, or NULL if the search
   *   failed.
   */
  protected function findResults() {
    $keys = $this->keywords;

    $query = $this->database
      ->select('search_index', 'i', array('target' => 'replica'))
      ->extend('Drupal\search\SearchQuery')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->join('file_managed', 'f', 'f.fid = i.sid');
    $query->join('search_dataset', 'sd', 'sd.sid = i.sid AND sd.type = i.type');
    $query->searchExpression($keys, $this->getPluginId());

    // Run the query.
    $find = $query
      // Add the language code of the indexed item to the result of the query.
      ->fields('i', array('langcode'))
      ->fields('sd', array('data'))
      // And since SearchQuery makes these into GROUP BY queries, if we add
      // a field, for PostgreSQL we also need to make it an aggregate or a
      // GROUP BY. In this case, we want GROUP BY.
      ->groupBy('i.langcode')
      ->groupBy('sd.data')
      ->limit(10)
      ->execute();

    // Check query status and set messages if needed.
    $status = $query->getStatus();

    if ($status & SearchQuery::EXPRESSIONS_IGNORED) {
      drupal_set_message($this->t('Your search used too many AND/OR expressions. Only the first @count terms were included in this search.', array('@count' => $this->searchSettings->get('and_or_limit'))), 'warning');
    }

    if ($status & SearchQuery::LOWER_CASE_OR) {
      drupal_set_message($this->t('Search for either of the two terms with uppercase <strong>OR</strong>. For example, <strong>cats OR dogs</strong>.'), 'warning');
    }

    if ($status & SearchQuery::NO_POSITIVE_KEYWORDS) {
      drupal_set_message($this->formatPlural($this->searchSettings->get('index.minimum_word_size'), 'You must include at least one positive keyword with 1 character or more.', 'You must include at least one positive keyword with @count characters or more.'), 'warning');
    }

    return $find;
  }

  /**
   * Prepares search results for rendering.
   *
   * @param \Drupal\Core\Database\StatementInterface $found
   *   Results found from a successful search query execute() method.
   *
   * @return array
   *   Array of search result item render arrays (empty array if no results).
   */
  protected function prepareResults(StatementInterface $found) {
    $results = array();

    $file_storage = $this->entityManager->getStorage('file');
    $keys = $this->keywords;

    foreach ($found as $item) {
      $file = $file_storage->load($item->sid)->getTranslation($item->langcode);

      $result = array(
        'link' => file_create_url($file->getFileUri()),
        'title' => Html::escape($file->getFilename()),
        'snippet' => search_excerpt($keys, $item->data, $item->langcode),
        'langcode' => $file->language()->getId(),
      );

      $results[] = $result;
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    $total = $this->database->query('SELECT COUNT(*) FROM {file_managed} WHERE status = 1')->fetchField();
    $remaining = $this->database->query("SELECT COUNT(*) FROM {file_managed} f LEFT JOIN {search_dataset} sd ON sd.sid = f.fid AND sd.type = :type WHERE f.status = 1 AND sd.sid IS NULL OR sd.reindex <> 0", array(':type' => $this->getPluginId()))->fetchField();

    return array('remaining' => $remaining, 'total' => $total);
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    // Interpret the cron limit setting as the maximum number of files to index
    // per cron run.
    $limit = (int) $this->searchSettings->get('index.cron_limit');

    $result = $this->database->queryRange("SELECT f.fid, MAX(sd.reindex) FROM {file_managed} f LEFT JOIN {search_dataset} sd ON sd.sid = f.fid AND sd.type = :type WHERE sd.sid IS NULL OR sd.reindex <> 0 GROUP BY f.fid ORDER BY MAX (sd.reindex) is null DESC, MAX (sd.reindex) ASC, f.fid ASC", 0, $limit, array(':type' => $this->getPluginId()), array('target' => 'replica'));
    $fids = $result->fetchCol();
    if (!$fids) {
      return;
    }

    $file_storage = $this->entityManager->getStorage('file');
    foreach ($file_storage->loadMultiple($fids) as $file) {
      $this->indexFile($file);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function markForReindex() {
    // All NodeSearch pages share a common search index "type" equal to
    // the plugin ID.
    search_mark_for_reindex($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function indexClear() {
    // All NodeSearch pages share a common search index "type" equal to
    // the plugin ID.
    search_index_clear($this->getPluginId());
  }

  /**
   * Indexes a single file.
   *
   * @param \Drupal\Core\Entity\EntityInterface $file
   *   The file to index.
   */
  protected function indexFile(EntityInterface $file) {
    if (!in_array($file->getMimeType(), $this->includedMimetypes)) {
      return;
    }

    $languages = $file->getTranslationLanguages();
    foreach ($languages as $language) {
      $translation_options = array('langcode' => $language->getId());
      $content = $this->t('Filename', array(), $translation_options) . ': ' . $file->getFilename() . ' - ' . $this->t('Content', array(), $translation_options) . ': ';

      // Extract the file content and add it to the drupal search index.
      $extracted_content = SafeMarkup::checkPlain($this->getFileContent($file));
      $content .= $extracted_content;

      // Update index, using search index "type" equal to the plugin ID.
      search_index($this->getPluginId(), $file->id(), $language->getId(), $content);
    }
  }

  /**
   * Extract the content of the given file.
   *
   * @param \Drupal\Core\Entity\EntityInterface $file
   *   The file that should be indexed.
   *
   * @return string
   *   A string with th extracted content from the file.
   */
  protected function getFileContent(EntityInterface $file) {
    $file_path = file_create_url($file->getFileUri());

    $image_mimetypes = array('image/jpeg', 'image/jpg', 'image/tiff');

    if ($file->getMimeType() == 'text/plain' || $file->getMimeType() == 'text/x-diff') {
      $content = $this->extractContentSimple($file, $file_path);
    }
    elseif (in_array($file->getMimeType(), $image_mimetypes)) {
      $content = $this->extractContentExif($file, $file_path);
    }
    else {
      $content = $this->extractContentTika($file, $file_path);
    }

    return $content;
  }

  /**
   * Extract simple text.
   *
   * @param \Drupal\Core\Entity\EntityInterface $file
   *   The file object.
   * @param string $file_path
   *   The path to the file.
   *
   * @return string
   *   The extracted text.
   */
  protected function extractContentSimple(EntityInterface $file, $file_path) {
    $content = file_get_contents($file_path);
    $content = iconv("UTF-8", "UTF-8//IGNORE", $content);
    $content = htmlspecialchars(html_entity_decode($content, ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES, 'UTF-8');
    $content = trim($content);

    return $content;
  }

  /**
   * Extract IPTC metadata from image.
   *
   * @param \Drupal\Core\Entity\EntityInterface $file
   *   The file object.
   * @param string $file_path
   *   The path to the file.
   *
   * @return string
   *   The extracted text.
   */
  protected function extractContentExif(EntityInterface $file, $file_path) {
    $content = '';
    $size = getimagesize($file_path, $info);
    if (isset($info['APP13'])) {
      $iptc_raw = iptcparse($info['APP13']);
      if (empty($iptc_raw)) {
        return $content;
      }

      $tagmarker = $this->getExifTagmarker();

      $iptc = array();
      foreach ($iptc_raw as $key => $value) {
        // Add only values from the defined iptc fields.
        if (array_key_exists($key, $tagmarker)) {
          $iptc_field_value = array();
          foreach ($value as $innerkey => $innervalue) {
            $innervalue = trim($innervalue);
            if (!empty($innervalue)) {
              $iptc_field_value[] = $innervalue;
            }
          }

          if (!empty($iptc_field_value)) {
            $iptc[$tagmarker[$key]] = implode(', ', $iptc_field_value);
          }
        }
      }

      foreach ($iptc as $key => $value) {
        $content .= " <strong>{$key}:</strong> {$value}";
      }
      $content = trim($content);
    }

    return $content;
  }

  /**
   * Extract file content with Apache Tika.
   *
   * @param \Drupal\Core\Entity\EntityInterface $file
   *   The file object.
   * @param string $file_path
   *   The path to the file.
   *
   * @return string
   *   The extracted text.
   *
   * @throws \Drupal\search_file_attachments\Plugin\Search\Exception
   */
  protected function extractContentTika(EntityInterface $file, $file_path) {
    $tika_path = realpath($this->moduleSettings->get('tika.path'));
    $tika = realpath($tika_path . '/' . $this->moduleSettings->get('tika.jar'));

    if (!$tika || !is_file($tika)) {
      throw new Exception($this->t('Invalid path or filename for tika application jar.'));
    }

    // UTF-8 multibyte characters will be stripped by escapeshellargs().
    // So temporarily set the locale to UTF-8 so that the filepath remain valid.
    $backup_locale = setlocale(LC_CTYPE, '0');
    setlocale(LC_CTYPE, 'en_US.UTF-8');

    $java_service = \Drupal::service('search_file_attachments.java');
    if ($this->moduleSettings->get('java_path')) {
      $java_service->setJavaPath($this->moduleSettings->get('java_path'));
    }
    $java_path = $java_service->getJavaPath();

    $param = '';

    if ($file->filemime != 'audio/mpeg') {
      $param = ' -Dfile.encoding=UTF8 -cp ' . escapeshellarg($tika_path);
    }

    if (DIRECTORY_SEPARATOR == '\\') {
      // If we on windows, use an other methode to escape the file path strings,
      // to prevent problems with paths that contains spaces. Because the
      // PHP escapeshellarg() function handle these correct.
      $cmd = $java_path . $param . ' -jar "' . str_replace('"', '\\"', $tika) . '" -t "' . str_replace('"', '\\"', $file_path) . '"';
    }
    else {
      $cmd = $java_path . $param . ' -jar ' . escapeshellarg($tika) . ' -t ' . escapeshellarg($file_path);
    }

    // Support utf-8 commands:
    // http://www.php.net/manual/pt_BR/function.shell-exec.php#85095
    $cmd = "LANG=en_US.utf-8; $cmd";
    // Restore the locale.
    setlocale(LC_CTYPE, $backup_locale);

    // Debug print.
    if ($this->moduleSettings->get('debug')) {
      $result = shell_exec($cmd . ' 2>&1');

      \Drupal::logger('search_file_attachments')->notice('<p><strong>Tika Command:</strong> <code>%command</code></p><br /> <p><strong>Result:</strong> %result</p>', array(
        '%command' => $cmd,
        '%result' => $result,
      ));

      // Empty the result, if it contains an error message, so that the error
      // is not in the index.
      if (strpos($result, 'Exception in thread') !== FALSE) {
        $result = FALSE;
      }

      return $result;
    }

    return shell_exec($cmd);
  }

  /**
   * Return the array of included mimetypes.
   *
   * @return array
   *   The array of mimetypes.
   */
  protected function getIncludedMimetypes() {
    return $this->includedMimetypes;
  }

  /**
   * Set the included mimetypes.
   *
   * Maps the included file types (file extensions) from the settings with
   * the correponding mimetypes.
   */
  protected function setIncludedMimetypes() {
    $mimetype_service = \Drupal::service('search_file_attachments.mimetype');
    $included_filetypes = $this->moduleSettings->get('files.include');

    $this->includedMimetypes = $mimetype_service->extensionsToMimetypes($included_filetypes);
  }

  /**
   * Defines the IPTC fields to be used for the search index.
   *
   * @return array
   *   A array of IPTC fields.
   */
  protected function getExifTagmarker() {
    $tagmarker = array(
      '2#005' => t('Object Name'),
      '2#015' => t('Category'),
      '2#020' => t('Supplementals'),
      '2#025' => t('Keywords'),
      '2#040' => t('Special Instructions'),
      '2#080' => t('By Line'),
      '2#085' => t('By Line Title'),
      '2#090' => t('City'),
      '2#092' => t('Sublocation'),
      '2#095' => t('Province State'),
      '2#100' => t('Country Code'),
      '2#101' => t('Country Name'),
      '2#105' => t('Headline'),
      '2#110' => t('Credits'),
      '2#115' => t('Source'),
      '2#116' => t('Copyright'),
      '2#118' => t('Contact'),
      '2#120' => t('Caption'),
      '2#122' => t('Caption Writer'),
    );

    // Allow other modules to alter defined IPTC fields.
    return \Drupal::moduleHandler()->alter('search_file_attachments_exif_tagmarker', $tagmarker);
  }

}
