<?php

namespace Drupal\cocoon_media;

/**
 * Base interface definition for DevelDumper plugins.
 *
 * @see \Drupal\devel\Annotation\DevelDumper
 * @see \Drupal\devel\DevelDumperPluginManager
 * @see \Drupal\devel\DevelDumperBase
 * @see plugin_api
 */
interface CocoonControllerInterface {

  public static function SoapClient($reqId, $sub_domain, $user_name, $secret_key);

  public function getTags();
  
	public function getTag($tagId);

	public function getFilesByTag($tagId);

  public function getThumbTypes();

  public function getSets();

  public function getFilesBySet($setId);

  public function getFile( $fileId );

  public function getThumbInfo( $fileId );

  public function getRequestId();

  function errorResponse($errMsg);

  public function getVersion();

}
