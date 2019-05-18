<?php

/**
 * Implements hook_drush_command().
 *
 * In this hook, you specify which commands your Drush module makes available,
 * what it does and description.
 *
 * Notice how this structure closely resembles how you define menu hooks.
 *
 * See `drush topic docs-commands` for a list of recognized keys.
 */
function config_log_drush_command() {
  $items = array();
  $items['config-log-commit'] = array(
    'description' => "Retrieve and commit config changes (demo only).",
    'arguments' => array(
      'repo path' => 'Path to the git repo.',
    ),
    'options' => array(
      'config-vcs' => 'Directory with the config (defaults to config_vcs)',
      'git-remote-url' => 'URL to push the commits to.',
    ),
  );
  return $items;
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_config_log_commit($path = '') {
  // @todo - don't bother with local repo. Push using Github file contents API.
  // See https://developer.github.com/v3/repos/contents/
  if (!$path) {
    return drush_log('Repo path is required', 'error');
  }
  $path = realpath($path);
  $config_vcs = $path . '/' . drush_get_option('config-vcs', 'config_vcs');
  $track = "$path/config_log_commit_last";
  $last = $last_initial = (int) trim(@file_get_contents($track));
  do {
    $result = \Drupal::database()->query('SELECT * FROM {config_log} WHERE clid > :id ORDER BY clid ASC', array('id' => $last));
    foreach ($result as $row) {
      $account = \Drupal\user\Entity\User::load($row->uid);
      $filename = $config_vcs . '/' . $row->name . '.yml';
      file_put_contents($filename, $row->data);
      $cmd = 'cd ' . escapeshellarg($config_vcs) . ' && git add * && (git diff --quiet HEAD || git commit -m ' . escapeshellarg($row->clid . ' user: ' . $account->getAccountName() . ' operation: ' . $row->operation . ' name:' . $row->name) . ')';
      $out = system($cmd, $retval);
      if ($retval == 0) {
        file_put_contents($track, $row->clid);
        $last = $row->clid;
      }
      else {
        throw new Exception($out);
      }
    }
    sleep(5);
  } while (1);

  // Push any commits we just made to a remote repo (i.e. the fork).
  if ($last > $last_initial) {
    // @todo: Don't assume hard coded remote name and branch name.
    $repo = drush_get_option('git-remote-url', 'git@github.com:AcquiaFullCircle/example.com.git');
    $cmd = 'cd ' . escapeshellarg($config_vcs) . ' && git push ' . escapeshellarg($repo) . ' 20150212-001';
    $out = system($cmd, $retval);
    if ($retval == 0) {
      // Success!
    }
    else {
      throw new Exception($out);
    }
  }
}
