<?php

namespace Drupal\xhprof\XHProfLib\Storage;

/**
 * Interface StorageInterface
 */
interface StorageInterface {

  /**
   * @return mixed
   */
  public function getRuns();

  /**
   * @param string $run_id
   * @param string $namespace
   *
   * @return array
   */
  public function getRun($run_id, $namespace);

  /**
   * @param array $data
   * @param string $namespace
   * @param string $run_id
   *
   * @return string
   */
  public function saveRun($data, $namespace, $run_id);

  /**
   * @return string
   */
  public function getName();
}
