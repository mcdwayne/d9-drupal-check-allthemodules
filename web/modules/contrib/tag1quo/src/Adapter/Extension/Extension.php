<?php

namespace Drupal\tag1quo\Adapter\Extension;

use Drupal\tag1quo\Adapter\Core\Core;
use Drupal\tag1quo\VersionedClass;

/**
 * Class Extension.
 *
 * @internal This class is subject to change.
 */
class Extension extends VersionedClass {

  /**
   * The Core adapter.
   *
   * @var \Drupal\tag1quo\Adapter\Core\Core
   */
  protected static $core;

  /**
   * The extension object from the database.
   *
   * @var \stdClass|object
   */
  protected $extension;

  /**
   * The machine name of the extension.
   *
   * @var string
   */
  protected $name;

  /**
   * @var array
   */
  protected $info;

  protected $infoExtension = '.info';

  /**
   * @var array
   */
  protected $infoComments;

  /**
   * The internal path to the extension root folder.
   * @var string
   */
  protected $path;

  /**
   * The extension type.
   *
   * @var string
   */
  protected $type;

  /**
   * Extension constructor.
   *
   * @param string $name
   *   The machine name of the extension.
   * @param \stdClass|object $extension
   *   An Extension object.
   */
  public function __construct($name, $extension) {
    $this->extension = $extension;
    $this->name = $name;
    $this->type = $extension->type;
    $this->path = $this->core()->getPath($this->type, $this->name);
    $this->info = $this->parseInfo($extension->info);
    $this->infoComments = $this->parseInfoComments();
  }

  /**
   * Creates a new Extension adapter.
   *
   * @param string $name
   *   The machine name of the extension.
   * @param \stdClass|object $extension
   *   An Extension object.
   *
   * @return \Drupal\tag1quo\Adapter\Extension\Extension
   */
  public static function create($name, $extension) {
    return static::createVersionedInstance([$name, $extension]);
  }

  /**
   * Retrieves the Core adapter.
   *
   * @return \Drupal\tag1quo\Adapter\Core\Core
   */
  protected function core() {
    if (static::$core === NULL) {
      static::$core = Core::create();
    }
    return static::$core;
  }

  public function getInfoFilename() {
    return $this->getPath() . '/' . $this->getName() . $this->infoExtension;
  }

  public function getName() {
    return $this->name;
  }

  public function getPath() {
    return $this->path;
  }

  public function getType() {
    return $this->type;
  }

  protected function parseInfo(array $info = array()) {
    $info += array(
      'base_theme' => '',
      'core' => '',
      'datestamp' => '',
      'dependencies' => '',
      'description' => '',
      'engine' => '',
      'name' => '',
      'package' => '',
      'php' => '',
      'project' => '',
      'version' => '',
    );
    if (empty($info['version'])) {
      $info['version'] = $this->parseRepoVersion();
    }
    return $info;
  }

  /**
   * Return an array of all comments in an extension info file.
   *
   * @param string $filename
   *   The filename to an extension's .info[.yml] file.
   *
   * @return array
   *   Comments.
   */
  protected function parseInfoComments($filename) {
    $filename = $this->getInfoFilename();
    $comments = array();
    if (file_exists($filename)) {
      // Load the info file.
      $data = file_get_contents($filename);
      // Extract only the comments.
      if (preg_match_all('@[^\s]*[;#][^\r\n]*$@mx', $data, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
          $comments[] = $match[0];
        }
      }
    }
    return $comments;
  }

  protected function parseRepoVersion() {
    $version = NULL;

    $path = $this->getPath();

    // Immediately return if path isn't a repository or there's no git binary.
    if (!is_dir("$path/.git") || !is_readable("$path/.git") || !($git = $this->core()->gitBinary())) {
      return $version;
    }

    $options = array('cwd' => $path);

    // Determine the current branch.
    $current_branch = $this->core()->exec('%s rev-parse --abbrev-ref HEAD', array($git), $options);

    // If current branch is "HEAD", then we have to determine the branch the
    // last commit is on.
    if ($current_branch === 'HEAD') {
      $last_commit_hash = $this->core()->exec('%s rev-parse --verify HEAD', array($git), $options);
      $branches = $this->core()->exec('%s branch --contains %s', array($git, $last_commit_hash), $options);
      @preg_match('/^* (?<branch>.*)$/m', $branches, $matches);
      $current_branch = !empty($matches['branch']) ? $matches['branch'] : NULL;
    }

    // Retrieve all the tags.
    $tags = $this->core()->exec('%s tag', array($git), $options + array('array' => TRUE));
    $last_tag = array_pop($tags);

    // Get the commit hash for the tag or branch being packaged.
    $last_tag_hash = $this->core()->exec('%s rev-list --topo-order --max-count=1 %s', array($git, $last_tag ?: $current_branch ?: 'HEAD'), $options);
    if (!$last_tag_hash || !preg_match('/^[0-9a-f]{40}$/', $last_tag_hash)) {
      return $version;
    }

    $last_tag = $this->core()->exec('%s describe --tags %s', array($git, $last_tag_hash), $options);

    // If this is a -dev release, do some magic to determine a spiffy
    // "rebuild_version" string which we'll put into any .info files and
    // save in the DB for other uses.
    if ($last_tag) {
      // Make sure the tag starts as Drupal formatted (for eg.
      // 7.x-1.0-alpha1) and if we are on a proper branch (ie. not master)
      // then it's on that branch.
      if (preg_match('/^(?<drupalversion>\d+\.x-[\d\.]+(?:-[^-]+)?)(?<gitextra>-(?<numberofcommits>\d+-)g[0-9a-f]{7})?$/', $last_tag, $matches)) {
        // If we found additional git metadata (in particular, number of
        // commits) then use that info to build the version string.
        if (isset($matches['gitextra'])) {
          $version = $matches['drupalversion'] . '+' . $matches['numberofcommits'] . 'dev';
        }
        // Otherwise, the branch tip is pointing to the same commit as the
        // last tag on the branch, in which case we use the prior tag and
        // add '+0-dev' to indicate we're still on a -dev branch.
        else {
          $version = $last_tag . '+0-dev';
        }
      }
    }

    return $version;
  }

}
