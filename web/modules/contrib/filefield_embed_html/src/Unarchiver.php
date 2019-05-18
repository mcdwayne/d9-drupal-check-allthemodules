<?php

namespace Drupal\filefield_embed_html;

class Unarchiver {

  /**
   * The file to be processed.
   */
  protected $file;

  /**
   * Constructs the HTMLExtractor object.
   */
  public function __construct($file) {
    $this->file = $file;
  }

  /**
   * Gets the file's MIME Type.
   */
  public function getMimeType($file) {
    return mime_content_type($file);
  }

  /**
   * Extracts the archive into a specified destination directory.
   */
  public function extractTo($destination) {
    if ($this->getMimeType($this->file) == 'application/zip') {
      $archive = new \ZipArchive;
      $archive->open($this->file);

      for ($i = 0; $i < $archive->numFiles; $i++) {
        $entry = $archive->getNameIndex($i);

        if (preg_match('/\bindex\.html\b/', $entry)) {
          $entries = explode('/', $entry);
          $sub_dir = $entries[0];

          if ($sub_dir === 'index.html') {
            if ($archive->extractTo($destination) !== TRUE) {
              throw new \Exception('Unable to extract the archive file.');
            }
          }
          else {
            $files = array();
            mkdir($destination);

            for ($i = 0; $i < $archive->numFiles; $i++) {
              $entry = $archive->getNameIndex($i);
              $entry_explode = explode('/', $entry);

              if ($entry_explode[0] === $sub_dir && $entry_explode[1] !== '') {
                $files[] = $entry;
              }
            }

            $temp_dir = file_directory_temp() . $destination;
            if ($archive->extractTo($temp_dir, $files) === TRUE) {
              $temp_files = scandir($temp_dir . '/' . $sub_dir);

              foreach ($temp_files as $temp_file) {
                if ($temp_file != '.' && $temp_file != '..') {
                  $oldname = $temp_dir . '/' . $sub_dir . '/' . $temp_file;
                  $newname = $destination . '/' . $temp_file;
                  rename($oldname, $newname);
                }
              }
            }
            else {
              throw new \Exception('Unable to extract the archive file.');
            }
          }

          break;
        }
      }

      $archive->close();
    }
    else {
      throw new \Exception('The provided file is not supported. Please use a ZIP archive instead.');
    }
  }

}