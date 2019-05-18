<?php

namespace Drupal\drd\Plugin\Update\Build;

use Drupal\drd\Update\PluginStorageInterface;
use GuzzleHttp\Client;

/**
 * Provides update build plugin to copy updates directly into the working dir.
 *
 * @Update(
 *  id = "direct",
 *  admin_label = @Translation("Copy directly"),
 * )
 */
class Direct extends Base {

  /**
   * {@inheritdoc}
   */
  public function build(PluginStorageInterface $storage, array $releases) {
    foreach ($releases as $release) {
      $name = $release->getMajor()->getProject()->getName();
      if ($release->getProjectType() == 'core') {
        $destination = $storage->getWorkingDirectory();
      }
      else {
        $ext = ($release->getMajor()->getCoreVersion() >= 8) ? '.info.yml' : '.info';
        $destination = $this->find($storage->getWorkingDirectory(), $name . $ext);
        if (!$destination) {
          // We can't find the destination for the new release, let's ignore
          // for now.
          $storage->log('Destination not found for ' . $name);
          continue;
        }
      }
      $archive = file_directory_temp() . DIRECTORY_SEPARATOR . $name . '.tar.gz';
      try {
        $client = new Client(['base_uri' => $release->getDownloadLink()->toString()]);
        $response = $client->request('get');
      }
      catch (\Exception $ex) {
        throw new \Exception('Can not download archive for ' . $name);
      }
      if ($response->getStatusCode() != 200) {
        throw new \Exception('Update not available for ' . $name);
      }
      file_put_contents($archive, $response->getBody()->getContents());
      if ($this->shell($storage, 'tar xf ' . $archive . ' -C ' . $destination . ' --strip-components=1')) {
        // An error occured, we stop further processing.
        throw new \Exception('Error while extracting ' . $archive);
      }
    }

    $this->changed = TRUE;
    return $this;
  }

  /**
   * Find a file in a directory tree recursively.
   *
   * @param string $dir
   *   The directory name.
   * @param string $filename
   *   The file name.
   *
   * @return string|bool
   *   The directory name in which the file was found or FALSE if the file
   *   couldn't be found in the tree.
   */
  private function find($dir, $filename) {
    if (file_exists($dir . DIRECTORY_SEPARATOR . $filename)) {
      return $dir;
    }
    $files = iterator_to_array(new \FilesystemIterator($dir, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS), FALSE);
    foreach ($files as $file) {
      if (is_dir($file)) {
        $find = $this->find($file, $filename);
        if ($find) {
          return $find;
        }
      }
    }
    return FALSE;
  }

}
