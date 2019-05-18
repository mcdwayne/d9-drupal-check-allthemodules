<?php

namespace Drupal\file_ownage;

use Drupal\file\Entity\File;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;

/**
 * Find manager.
 *
 * A service to look for a named file in several places in several ways.
 */
class FindManager implements FindManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $client, FileSystemInterface $file_system) {
    $this->config = $config_factory->get('file_ownage.settings');
    $this->client = $client;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function find(&$relative_path, array $options = []) {

    // Assert we only get rel_path by now. Being given a uri would be an error.
    $scheme = file_uri_scheme($relative_path);
    if ($scheme != 'file') {
      // Auto-correct it, chop off the scheme and end up with the real rel path.
      $relative_path = file_uri_target($relative_path);
      // trigger_error('Invalid input to ' . __FUNCTION__ . "().  \$relative_path should be relative, '$relative_path'' given.");.
    }

    try {
      $success = $this->searchForLostFiles($relative_path, $options);
      if (!$success) {
        \Drupal::logger('file_ownage')->error('@path could not be found in the search locations.', ['@path' => $relative_path]);
      }
      return $success;
    }
    catch (ClientException $e) {
      // Do nothing.
    }
    return FALSE;
  }

  /**
   * All-in-one wrapper to the main task. If an image is missing, fix it!
   *
   * @param $uri
   *
   * @return bool
   */
  public function repair(&$uri) {
    $original_uri = $uri;
    $strings['@original_uri'] = $original_uri;
    $searched_status = $this->find($uri);
    if (!$searched_status) {
      \Drupal::logger('file_ownage')->error('@original_uri could not be found in the search locations.', $strings);
      return FALSE;
    }
    $strings['@new_uri'] = $uri;
    $success = $this->fetch($uri, $original_uri);
    if (!$success) {
      \Drupal::logger('file_ownage')->error('Failed to copy file @new_uri to its expected home at @original_uri.', $strings);
      return FALSE;
    }
    \Drupal::logger('file_ownage')->info('File copied to where it should have been at @original_uri.', $strings);
    return $success;
  }

  /**
   * @param $source
   * @param $destination
   * @return bool
   */
  public function fetch($source, $destination, $status = NULL) {
    // Copy it down.
    // $source must already be a found, verified, usable source.
    try {
      // Prepare local target directory and save downloaded file.
      $strings['@source'] = $source;
      $strings['@destination'] = $destination;

      // These checks should be superfluous by now, but paranoia is fine.
      if (!$status) {
        $status = $this->pathStatus($source);
      }
      if (!$status) {
        \Drupal::logger('file_ownage')->error('Unable to find @source.', $strings);
        return FALSE;
      }

      // Fetch content using whatever method works.
      if ($status == FILE_OWNAGE_IS_REMOTE) {
        $response_data = $this->fetchRemote($source);
      }
      elseif ($status == FILE_OWNAGE_IS_ON_FILESYSTEM) {
        $response_data = file_get_contents($source);
      }
      if (empty($response_data)) {
        \Drupal::logger('file_ownage')->error('No content retrieved from @source. not saving to @destination.', $strings);
        return FALSE;
      }
      if ($this->writeFile($destination, $response_data)) {
        \Drupal::logger('file_ownage')->info('Fetched and copied file from @source to @destination.', $strings);
        return TRUE;
      }
      \Drupal::logger('file_ownage')->error('@source could not be saved to @destination.', $strings);
      return FALSE;
    }
    catch (ClientException $e) {
      // Do nothing.
      $strings['@error'] = $e->getMessage();
      \Drupal::logger('file_ownage')->error('Failed fetching file. @error.', $strings);
    }
    return FALSE;
  }

  /**
   * Fetch remote file content.
   *
   * Beware, this loads the file into PHP memory instead of piping it.
   */
  public function fetchRemote($url) {
    $options['Connection'] = 'close';
    $response = $this->client->get($url, $options);
    $result = $response->getStatusCode();
    if ($result != 200) {
      \Drupal::logger('stage_file_proxy')->error('HTTP error @errorcode occurred when trying to fetch @remote.', [
        '@errorcode' => $result,
        '@remote' => $url,
      ]);
      return FALSE;
    }
    $response_headers = $response->getHeaders();
    $content_length = array_shift($response_headers['Content-Length']);
    $response_data = $response->getBody()->getContents();
    if (isset($content_length) && strlen($response_data) != $content_length) {
      \Drupal::logger('stage_file_proxy')->error('Incomplete download. Was expecting @content-length bytes, actually got @data-length.', [
        '@content-length' => $content_length,
        '@data-length' => $content_length,
      ]);
      return FALSE;
    }
    return $response_data;
  }

  /**
   * Fetch file content.
   *
   * This stub only here for consistent function names.
   * To compliment fetchRemote().
   */
  public function fetchLocal($url) {
    return file_get_contents($url);
  }

  /**
   * Use write & rename instead of write.
   *
   * Stolen from stage_file_proxy FetchManager.
   *
   * Perform the replace operation. Since there could be multiple processes
   * writing to the same file, the best option is to create a temporary file in
   * the same directory and then rename it to the destination. A temporary file
   * is needed if the directory is mounted on a separate machine; thus ensuring
   * the rename command stays local.
   *
   * @param string $destination
   *   A string containing the destination location.
   * @param string $data
   *   A string containing the contents of the file.
   *
   * @return bool
   *   True if write was successful. False if write or rename failed.
   */
  protected function writeFile($destination, $data) {
    // Get a temporary filename in the destination directory.
    $dir = $this->fileSystem->dirname($destination) . '/';
    $temporary_file = $this->fileSystem->tempnam($dir, 'file_ownage_');
    $temporary_file_copy = $temporary_file;

    // Get the extension of the original filename and append it to the temp file
    // name. Preserves the mime type in different stream wrapper
    // implementations.
    $parts = pathinfo($destination);
    $extension = '.' . $parts['extension'];
    if ($extension === '.gz') {
      $parts = pathinfo($parts['filename']);
      $extension = '.' . $parts['extension'] . $extension;
    }
    // Move temp file into the destination dir if not in there.
    // Add the extension on as well.
    $temporary_file = str_replace(substr($temporary_file, 0, strpos($temporary_file, 'stage_file_proxy_')), $dir, $temporary_file) . $extension;

    if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      \Drupal::logger('file_ownage')->error('Unable to prepare local directory @dir.', ['@dir' => $dir]);
      return FALSE;
    }

    // Preform the rename, adding the extension to the temp file.
    if (!@rename($temporary_file_copy, $temporary_file)) {
      // Remove if rename failed.
      @unlink($temporary_file_copy);
      return FALSE;
    }

    // Save to temporary filename in the destination directory.
    $filepath = file_unmanaged_save_data($data, $temporary_file, FILE_EXISTS_REPLACE);

    // Perform the rename operation if the write succeeded.
    if ($filepath) {
      if (!@rename($filepath, $destination)) {
        // Unlink and try again for windows. Rename on windows does not replace
        // the file if it already exists.
        @unlink($destination);
        if (!@rename($filepath, $destination)) {
          // Remove temporary_file if rename failed.
          @unlink($filepath);
        }
      }
    }

    // Final check; make sure file exists & is not empty.
    $result = FALSE;
    if (file_exists($destination) & filesize($destination) != 0) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Search in alternative locations for the expected file.
   *
   * Was previously file_ownage_search_for_lost_files()
   *
   * If a better location is found, $filepath will be updated by reference.
   *
   * The settings should define a list of paths - these can be
   * remote URLs,
   * local filesystem paths either inside or outside webroot,
   * or local SEARCH paths to try scanning from.
   *
   * eg
   * http://example.com/downloads/
   * /var/backups/oldsite/images/
   * sites/default/files/imported/
   * /var/backups/files/*
   *
   * If it's not a search, the exact path will be checked.
   * If it is a search, the exact path, and then anything
   * approximating it (by truncating the start of the search string)
   * will be looked for.
   *
   * TODO start thinking about security implications of allowing a
   * filesystem scan that grabs files from elsewhere on the server
   * and makes them public!
   * Currently, this is by design, as we want /var/backups/oldsite/files/*
   * to be a valid source. But...
   *
   * @param string $filepath
   *   Should be a rel path, not a full URI by now.
   *   Gets modified by reference.
   *
   * @param array $settings
   *   Configs & Context.
   *
   * @return string|bool
   *   The location status. FALSE if not found.
   */
  public function searchForLostFiles(&$filepath, array $settings) {
    $seek_paths = $this->config->get('seek_paths');

    if (empty($seek_paths)) {
      return FALSE;
    }
    \Drupal::logger('file_ownage')->notice('File %filepath is not found yet. Starting to scan alternate fallback locations it may be.', ['%filepath' => $filepath]);
    // This process MAY produce false matches, but is often correct,
    // and better than the broken link you currently have.
    $seek_paths = array_map('trim', $seek_paths);
    $status = FALSE;
    foreach ($seek_paths as $search_path) {
      // There are two types of scan, absolute and wildcard glob.
      if (preg_match('%\*$%', $search_path)) {
        // If the search path ends with '*',
        // See if the file is *anywhere* under the given search path.
        $dir = escapeshellarg(preg_replace('%/\*%', '', $search_path));
        $possible_path = $this->findInDir($filepath, $dir);
        if ($possible_path) {
          $filepath = $possible_path;
          return $this->pathStatus($filepath);
        }
      }
      else {
        // Search path should always end in /, for path concat.
        $search_path = preg_replace('/\/$/', '', $search_path) . '/';

        // As the search path *may* contain spaces, and spaces are either
        // valid or not for local vs remote URls (?) may need to urlencode
        // before building the possible path.
        // "Attachment 1.jpg" becomes:
        // - "http://remote.server/sites/default/files/Attachment%201.jpg"
        // - "public://Attachment 1.jpg".
        if ($this->isRemote($search_path)) {
          $possible_path = $search_path . rawurlencode($filepath);
        }
        else {
          $possible_path = $search_path . $filepath;
        }

        // We can't yet be sure if remote files exist.
        if ($status = $this->pathStatus($possible_path)) {
          if ($status == FILE_OWNAGE_IS_REMOTE) {
            \Drupal::logger('file_ownage')->debug('We are now hoping that %filepath is at remote location %possible_path', ['%filepath' => $filepath, '%possible_path' => $possible_path]);
          }
          else {
            \Drupal::logger('file_ownage')->debug('Looks like we found %filepath at %possible_path after all', ['%filepath' => $filepath, '%possible_path' => $possible_path]);
          }
          $filepath = $possible_path;
          return $status;
        }
      }
    }

    return $status;
  }

  /**
   * Try to find the named file in or under a given path.
   *
   * The most specific looking match (longest similar path) will win.
   *
   * Try to use as much of the path as we can,
   * then chop it back to get more random.
   * (with path fragment)
   *
   * @param $filepath
   * @param $dir
   *
   * @return int
   */
  public function findInDir($filepath, $search_path) {
    // Use the shell find, because otherwise we have to do a LOT of work.
    $excludes = implode(
      ' ',
      [
        "! -path '*/imagecache/*'",
        "! -path '*/tmp/*'",
      ]
    );

    // Start to trim the search string until something turns up.
    $search_for = $filepath;
    while (!empty($search_for)) {
      $strings = [
        '%search_for' => $search_for,
        '%search_path' => $search_path,
      ];
      \Drupal::logger('file_ownage')->debug('Searching filesystem under %search_path to try and find something matching %search_for', $strings);

      $search = " -path " . escapeshellarg('*/' . $search_for);
      $command = "find $search_path $excludes $search";
      $output = [];
      $return = exec($command . ' 2>&1', $output, $return_var);

      if (!$return_var) {
        if (!empty($output)) {
          // If the $output has anything in, use the shortest one we find.
          // Cheap order sort. there may be collisions, don't care
          // Create a list indexed by string length,
          // sort by keys and pop the top.
          $ordered_output = array_combine(array_map('strlen', $output), $output);
          ksort($ordered_output);
          $filepath = reset($ordered_output);
          $filepath = realpath($filepath);
          $strings['%filepath'] = $filepath;
          \Drupal::logger('file_ownage')->debug('Looks like we found %filepath at %search_path after searching!!', $strings);
          return $filepath;
        }
      }
      else {
        // Getting a return_far (exit code) meant that find failed.
        $strings = [
          '%command' => $command,
          '%output' => implode("\n", $output),
          '%return' => $return,
        ];
        \Drupal::logger('file_ownage')->warning('File search failed. I ran <pre>%command</pre> and the response was: <pre>%output</pre>', $strings);
      }

      // If not found yet, shorten the search pattern from the left.
      // We try to look for a filepath that includes, in order:
      // 'archive/images/thing.gif'
      // 'images/thing.gif'
      // 'thing.gif'
      // Eventually we will just end with the filename.
      $search_for = preg_replace('%^[^/]*/?%', '', $search_for);
    }
    // End loop that shortens the search parameter.
    return NULL;
  }

  /**
   * Summarizes the status of the managed filesystem.
   *
   * Used by the bulk review & repair tools.
   */
  public function summarizeManagedFiles() {
    // SELECT filemime, count(*) from file_managed GROUP BY filemime;.
    $connection = Database::getConnection();
    $sth = $connection->select('file_managed', 'fm');
    $sth->addField('fm', 'filemime');
    $sth->addExpression('COUNT(fm.filemime)', 'count');
    $sth->groupBy('fm.filemime');
    // Execute the statement.
    $data = $sth->execute();
    // Get all the results.
    $results = $data->fetchAll(\PDO::FETCH_ASSOC);
    return $results;
  }

  /**
   * Inspects the given path to see if it's remote, nearby or already local.
   *
   * As a special case, aegir-style prettyfiles count as local and correct.
   *
   * This has a bunch of error catching to make good guesses when given
   * almost-valid strings, as it's from a 'src' as found in markup.
   * Semi-invalid or relative URLs should still be resolved if possible.
   *
   * @param string $src
   *   May be updated by reference to correct some URL encoding
   *   or relative path fixes.
   *   Could be a full remote URL,
   *   could be a local public://something.png already,
   *   could be trickier.
   *
   * @return int
   *   Status.
   */
  public function pathStatus(&$src) {
    $is_remote = FALSE;
    $is_nearby = FALSE;
    $strings['%src'] = $src;

    // TODO - is the best way to get base_url?
    // Elsewhere I see mostly global being used :-/.
    $base_url = \Drupal::service('router.request_context')->getCompleteBaseUrl();

    // First test if the given src is a local file URI.
    // such as public://.
    if (file_valid_uri($src)) {
      // Check if it's really present on the filesystem.
      $realpath = $this->fileSystem->realpath($src);
      if (is_file($realpath) && is_readable($realpath)) {
        \Drupal::logger('file_ownage')->debug("The file %src is <b>local</b> and stored right. Carry on", $strings);
        // And is it already registered in the files table?
        $file = $this->getFileByUri($src);
        if ($file) {
          return FILE_OWNAGE_IS_REGISTERED;
        }
        // Not registered, but still in the right folders.
        return FILE_OWNAGE_IS_LOCAL;
      }
      else {
        \Drupal::logger('file_ownage')->warning("Looks like trouble with the files dir link to '%src'. It wasn't readable or found at the expected location inside the files directory.", $strings);
        return FALSE;
      }
    }

    // UrlHelper isValid here actually means is valid remote, like http://.
    // Local file:// schemes excluded.
    if (UrlHelper::isValid($src, TRUE)) {
      \Drupal::logger('file_ownage')->debug("The path %src is <b>absolute</b>.", $strings);

      if (UrlHelper::externalIsLocal($src, $base_url)) {
        // Localizing succeeded - which means it's really local.
        \Drupal::logger('file_ownage')->debug("The path %src is really just <b>local and absolute</b>.", $strings);
        // Convert the full URL back to localised path.
        // from http://this.server/sites/default/files/this.gif
        // to public://this.gif.
        $public_url = file_create_url('public://');
        $localized = str_replace($public_url, '', $src);
        $src = $localized;
      }
      else {
        \Drupal::logger('file_ownage')->debug("The file %src is <b>remote</b>.", $strings);
        return FILE_OWNAGE_IS_REMOTE;
      }
    }

    if (substr($src, 0, 1) == '/' && is_readable($src)) {
      \Drupal::logger('file_ownage')->debug("The file %src is <b>on the filesystem</b> (raw).", $strings);
      return FILE_OWNAGE_IS_ON_FILESYSTEM;
    }

    // Non-URL links are surely server-root-relative? I can't do relative.
    $working_src = ltrim($src, '/');
    $strings['%working_src'] = $working_src;

    // If it's already inside our files dir, tag it and move on.
    // Drupal core file_check_location went away. No replacement?
    // Question is - is it in our public files directory?
    $realpath = $this->fileSystem->realpath($working_src);
    $container = $this->fileSystem->realpath('public://');
    $strings['%container'] = $container;
    $is_local = (strpos($realpath, $container) === 0);

    if ($is_local) {
      \Drupal::logger('file_ownage')->debug("The search path %src is expected to be <b>local</b>.", $strings);
      // The src we should work with is the local version.
      $working_src = str_replace($container . '/', '', $realpath);

      // The least we can do is double-check the file exists.
      if (is_file($realpath) && is_readable($realpath)) {
        \Drupal::logger('file_ownage')->debug("The file %src is <b>local</b> and stored right. Carry on", $strings);

        // And is it already registered in the files table?
        $file = $this->getFileByFilepath($src);
        if ($file) {
          return FILE_OWNAGE_IS_REGISTERED;
        }
        // Not registered, but still in the right folders.
        return FILE_OWNAGE_IS_LOCAL;
      }
      else {
        \Drupal::logger('file_ownage')->warning("Looks like trouble with the local files dir link to '%src'. It wasn't readable or found at the expected location inside the files directory.", $strings);
        return FALSE;
      }
    }
    else {
      // Path was not underneath files.
      // Still should check it exists.
      if (!is_readable($working_src) && !is_readable($src)) {
        \Drupal::logger('file_ownage')->warning("Looks like trouble with the nearby link to %src ( %working_src ). It wasn't readable or found at the expected remote location. ", $strings);
        return FALSE;
      }
      \Drupal::logger('file_ownage')->debug("%src is apparently <b>local</b>, but not stored in the %container dir.", $strings);
      return FILE_OWNAGE_IS_NEARBY;
    }

  }

  /**
   * Determine the diff between local file schemes and remote ones.
   *
   * Http:// isRemote.
   * public:// is not.
   * /path/to/file is not.
   *
   * @param $uri
   *
   * @return bool
   */
  public function isRemote($uri) {
    return UrlHelper::isValid($uri, TRUE);
  }

  /**
   *
   */
  public function uriFromId($file_id) {
    $file = File::load($file_id);
    $uri = $file->getFileUri();
    return $uri;
  }

  /**
   * Util to lookupfile entities.
   *
   * Previously was file_ownage_get_file_by_uri()
   *
   * @param string $path
   *   File URI.
   *
   * @return null|\Drupal\file\Entity\File
   *   The found $file object.
   */
  public function getFileByUri($uri) {
    $query = \Drupal::entityQuery('file')
      ->condition('uri', $uri);
    $ids = $query->execute();
    // There can only be one, surely.
    $fid = reset($ids);
    return File::load($fid);
  }

  /**
   * Utility lookup.
   *
   * Previously was file_ownage_load_file_by_filepath()
   *
   * @param string $filepath
   *   Real filepath, not neccessarily a URI yet.
   *
   * @return \Drupal\file\Entity\File|bool
   *   $file data on success
   */
  public function getFileByFilepath($filepath) {

    // Assert valid input.
    if (empty($filepath)) {
      \Drupal::logger('file_ownage')->error("Empty filepath given to %function", ['%function' => __FUNCTION__]);
      return FALSE;
    }
    // Due to upgrade, sometimes this is run on filepaths, sometimes
    // on already fixed URIs.
    if (file_valid_uri($filepath)) {
      // cool.
      $file_uri = $filepath;
    }
    else {
      // Need to figure what the URI would be for this file.
      // No API for this? best guess was based on system_update_7034.
      $file_uri_path = str_replace(\Drupal::service("file_system")->realpath('public://') . '/', '', \Drupal::service("file_system")->realpath($filepath));
      $file_uri = file_build_uri($file_uri_path);
    }

    // Checking db for {$file->uri}.
    if ($file = $this->getFileByUri($file_uri)) {
      $strings = [
        '%filepath' => $file->filepath,
        '%file_uri' => $file_uri,
        '%fid' => $file->fid,
      ];
      \Drupal::logger('file_ownage')->debug("%filepath (%file_uri) is already registered in the DB as fid:%fid", []);
      // BUT, one strange occasions someone may have even just deleted the
      // file we expect. Double-check.
      if (!file_exists($file->uri)) {
        \Drupal::logger('file_ownage')->warning("%file_uri was registered in the DB as fid:%fid but has mysteriously vanished. I'd better remove that entry.", ['%file_uri' => $file->uri, '%fid' => $file->fid]);
        file_delete($file);
        return FALSE;
      }
      return $file;
    }

    \Drupal::logger('file_ownage')->info("%filepath (%file_uri) is so far unknown in the DB", ['%filepath' => $filepath, '%file_uri' => $file_uri]);
    return FALSE;
  }

}
