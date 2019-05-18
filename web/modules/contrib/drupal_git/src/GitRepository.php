<?php

namespace Drupal\drupal_git;

/**
 * Class GitRepository.
 */
class GitRepository implements GitRepositoryInterface {

  protected $repo;

  protected $cwd;

  /**
   * Constructor.
   *
   * @param mixed $repo
   *   Repo directory.
   *
   * @throws GitException
   *   Git Exception.
   */
  public function __construct($repo) {
    if (basename($repo) === '.git') {
      $repo = dirname($repo);
    }

    $this->repository = realpath($repo);

    if ($this->repository === FALSE) {
      throw new GitException("Repository '$repo' not found.");
    }
  }

  /**
   * Gives Repo path.
   *
   * @return string
   *   Repo path.
   */
  public function getRepositoryPath() {
    return $this->repository;
  }

  /**
   * Returns list of tags in repo.
   *
   * @return string[]
   *   NULL  NULL => no tags.
   *
   * @throws GitException
   */
  public function getTags() {
    return $this->extractFromCommand('git tag', 'trim');
  }

  /**
   * Gives Remote Repo.
   */
  public function getRemoteRepo() {
    return $this->extractFromCommand('git remote -v', function ($value) {
          return trim($value);
    });
  }

  /**
   * Gives Diff of branches.
   */
  public function getDiffOfBranch($base_branch, $child_branch) {
    return $this->extractFromCommand("git diff $base_branch...$child_branch", function ($value) {
          return trim($value);
    });
  }

  /**
   * Gives logs.
   *
   * @return array
   *   Gives string of git graph.
   */
  public function getPrettyLogs() {
    return $this->extractFromCommand('git log --all --decorate --oneline --graph', function ($value) {
          return trim($value);
    });
  }

  /**
   * Gives logs.
   *
   * @return array
   *   Gives string of logs.
   */
  public function getAllLogs() {
    return array_filter($this->extractFromCommand("git log", function ($value) {
          return trim($value);
    }));
  }

  /**
   * Gives logs.
   *
   * @return array
   *   Gives string of log summary.
   */
  public function getLogSummary() {
    return array_filter($this->extractFromCommand('git log --summary', function ($value) {
          return trim($value);
    }));
  }

  /**
   * Gives Status.
   *
   * @return array
   *   Gives string of status.
   */
  public function getStatus() {
    return array_filter($this->extractFromCommand('git status', function ($value) {
          return trim($value);
    }));
  }

  /**
   * Gives users details.
   *
   * @return array
   *   Gives string of log username and email.
   */
  public function getUsersSummary() {
    return array_filter($this->extractFromCommand('git shortlog -n -e -s --all --no-merges', function ($value) {
          return trim($value);
    }));
  }

  /**
   * Gets name of current branch git branch` + magic.
   *
   * @return string
   *   Gives current branch name.
   *
   * @throws GitException
   */
  public function getCurrentBranchName() {
    try {
      $branch = $this->extractFromCommand('git branch -a', function ($value) {
        if (isset($value[0]) && $value[0] === '*') {
          return trim(substr($value, 1));
        }

        return FALSE;
      });

      if (is_array($branch)) {
        return $branch[0];
      }
    }
    catch (GitException $e) {

    }
    throw new GitException('Getting current branch name failed.');
  }

  /**
   * Returns list of all (local & remote) branches in repo.
   *
   * @return string[]
   *   NULL  NULL => no branches.
   *
   * @throws GitException
   */
  public function getBranches() {
    return $this->extractFromCommand('git branch -a', function ($value) {
          return trim(substr($value, 1));
    });
  }

  /**
   * Returns list of local branches in repo.
   *
   * @return string[]
   *   NULL  NULL => no branches.
   *
   * @throws GitException
   */
  public function getLocalBranches() {
    return $this->extractFromCommand('git branch', function ($value) {
          return trim(substr($value, 1));
    });
  }

  /**
   * Returns last commit ID `git log --pretty=format:'%H' -n 1`.
   *
   * @return string
   *   Gives las commit id of current branch.
   *
   * @throws GitException
   */
  public function getLastCommitId() {
    $this->begin();
    $lastLine = exec('git log --pretty=format:\'%H\' -n 1 2>&1');
    $this->end();
    if (preg_match('/^[0-9a-f]{40}$/i', $lastLine)) {
      return $lastLine;
    }
    return NULL;
  }

  /**
   * Helper function to execute the commands.
   *
   * @param mixed $cmd
   *   Command to execute.
   *
   * @return mixed
   *   String.
   *
   * @throws GitException
   */
  public function execute($cmd) {
    if (!is_array($cmd)) {
      $cmd = [$cmd];
    }

    array_unshift($cmd, 'git');
    $cmd = self::processCommand($cmd);

    $this->begin();
    exec($cmd . ' 2>&1', $output, $ret);
    $this->end();

    if ($ret !== 0) {
      throw new GitException("Command '$cmd' failed (exit-code $ret).", $ret);
    }

    return $output;
  }

  /**
   * Helper function.
   *
   * @return $this
   *   Current object.
   */
  protected function begin() {
    // TODO: good idea??
    if ($this->cwd === NULL) {
      $this->cwd = getcwd();
      chdir($this->repository);
    }

    return $this;
  }

  /**
   * Helper function.
   *
   * @return $this
   *   Current object.
   */
  protected function end() {
    if (is_string($this->cwd)) {
      chdir($this->cwd);
    }

    $this->cwd = NULL;
    return $this;
  }

  /**
   * Helper function to extract data from command.
   *
   * @return output
   *   Current object.
   */
  protected function extractFromCommand($cmd, $filter = NULL) {
    $output   = [];
    $exitCode = NULL;

    $this->begin();
    exec("$cmd", $output, $exitCode);
    $this->end();

    if ($exitCode !== 0 || !is_array($output)) {
      throw new GitException("Command $cmd failed.");
    }

    if ($filter !== NULL) {
      $newArray = [];

      foreach ($output as $line) {
        $value = $filter($line);

        if ($value === FALSE) {
          continue;
        }

        $newArray[] = $value;
      }

      $output = $newArray;
    }

    // Empty array.
    if (!isset($output[0])) {
      return NULL;
    }

    return $output;
  }

  /**
   * Function to run the cmd command.
   *
   * @param string $cmd
   *   Command String.
   *
   * @return $this
   *   Object of the class.
   *
   * @throws GitException
   */
  protected function run($cmd/* , $options = NULL */) {
    $args = func_get_args();
    $cmd = self::processCommand($args);
    exec($cmd . ' 2>&1', $output, $ret);

    if ($ret !== 0) {
      throw new GitException("Command '$cmd' failed (exit-code $ret).", $ret);
    }

    return $this;
  }

  /**
   * Helper function for processing the commands.
   *
   * @param array $args
   *   Array of the arguments.
   *
   * @return string
   *   Return string after process.
   */
  protected static function processCommand(array $args) {
    $cmd = [];

    $programName = array_shift($args);

    foreach ($args as $arg) {
      if (is_array($arg)) {
        foreach ($arg as $key => $value) {
          $_c = '';

          if (is_string($key)) {
            $_c = "$key ";
          }

          $cmd[] = $_c . escapeshellarg($value);
        }
      }
      elseif (is_scalar($arg) && !is_bool($arg)) {
        $cmd[] = escapeshellarg($arg);
      }
    }

    return "$programName " . implode(' ', $cmd);
  }

  /**
   * Function to init repo in directory.
   *
   * @param string $directory
   *   Directory.
   * @param array $params
   *   Parameters.
   *
   * @return \static
   *   returns static property.
   *
   * @throws GitException
   */
  public static function init($directory, array $params = NULL) {
    if (is_dir("$directory/.git")) {
      throw new GitException("Repo already exists in $directory.");
    }

    // Intentionally @; not atomic; from Nette FW.
    if (!is_dir($directory) && !@mkdir($directory, 0777, TRUE)) {
      throw new GitException("Unable to create directory '$directory'.");
    }

    $cwd = getcwd();
    chdir($directory);
    exec(self::processCommand([
      'git init',
      $params,
      $directory,
    ]), $output, $returnCode);

    if ($returnCode !== 0) {
      throw new GitException("Git init failed (directory $directory).");
    }

    $repo = getcwd();
    chdir($cwd);

    return new static($repo);
  }

}
