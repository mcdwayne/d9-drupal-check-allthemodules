<?php

namespace Drupal\file_ownage;

use GuzzleHttp\Exception\ClientException;
use Drupal\stage_file_proxy\FetchManagerInterface;
use Drupal\stage_file_proxy\FetchManager as SFPFetchManager;

/**
 * A stand-in for the stage_file_proxy 'fetch' service.
 *
 * When SFP finds a missing file, it normally calls FetchManager::fetch()
 * which pulls stuff from the remote server.
 *
 * We want to do almost the same, but will use different algorithms to
 * find the source.
 *
 * As we are just a stand-in, we extend the usual class to just override the
 * one method.
 * Our own file_finder does most of the heavy lifting.
 *
 * We tell the system to use this implimentation in place of the sfp
 * FetchManager, thanks to the service manager override.
 */
class FetchManager extends SFPFetchManager implements FetchManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch($server, $remote_file_dir, $relative_path, array $options) {
    // We ignore the first two args.
    // $relative_path may contain spaces when it's supplied.
    /** @var \Drupal\file_ownage\FindManagerInterface $file_finder */
    $file_finder = \Drupal::service('file_ownage.find_manager');
    $found_path = $relative_path;
    $found = $file_finder->find($found_path, $options);

    if (!$found) {
      return FALSE;
    }

    // Copy it down.
    // $found_path will have been converted into a usable source.
    try {
      $source = $found_path;
      $file_dir = $this->filePublicPath();
      $destination = $file_dir . '/' . $relative_path;
      return $file_finder->fetch($source, $destination, $found);
    }
    catch (ClientException $e) {
      // Do nothing.
      $strings['@error'] = $e->getMessage();
      \Drupal::logger('file_ownage')->error('Failed fetching file. @error.', $strings);
    }
    return FALSE;
  }

}
