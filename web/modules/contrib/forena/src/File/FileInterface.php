<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 11/17/15
 * Time: 4:09 PM
 */

namespace Drupal\forena\File;


use Drupal\views\Plugin\views\field\Boolean;

interface FileInterface {
  /**
   * @param $filename
   *   Name of file to check
   */
  public function isWritable($filename);

  /**
   * @param $filename
   *   Name of file to check for existence
   */
  public function exists($filename);

  /**
   * Determine if file is custom
   * @param $filename
   *   Name of file to load
   * @return Boolean
   *   True indicates there is no file to revert to.
   */
  public function isCustom($filename);

  /**
   * Determine if file is overriden
   * @param $filename
   * @return Boolean
   *   TRUE indicates that the file is different than
   *   module provided versions
   */
  public function isOverriden($filename);

  /**
   * @param $filename
   * @return mixed
   * Delete the contents of the file.
   * If the file has been overriden this only reverts the contents to the
   * original.
   */
  public function delete($filename);

  /**
   * Retrieve the contents of the file.
   */
  public function contents($filename);

  /**
   * Save the contents of a file.
   * @param $filename
   * @param $data
   * @return mixed
   */
  public function save($filename, $data);

  /**
   * Rerieve the meta data for a file.
   * Returns a structured object.
   * @param $filename
   * @return mixed
   */
  public function getMetaData($filename);

  /**
   * Loads metadata into object based on file type.
   * @param $object
   */
  public function extractMetaData(&$object);


}