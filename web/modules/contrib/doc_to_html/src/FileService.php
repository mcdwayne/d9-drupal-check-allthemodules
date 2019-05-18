<?php

namespace Drupal\doc_to_html;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\File\FileSystem;

/**
 * Class FileService.
 *
 * @package Drupal\doc_to_html
 */
class FileService implements FileServiceInterface {

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryservice;

  /**
   * Drupal\Core\File\FileSystem definition.
   *
   * @var Drupal\Core\File\FileSystem
   */
  protected $filesystemservice;
  /**
   * Constructor.
   */
  public function __construct(QueryFactory $entity_query, FileSystem $filesystem) {
    $this->queryservice = $entity_query;
    $this->filesystemservice = $filesystem;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('file_system')
    );
  }

  /**
   *
   */
  public function cleanFolder(){
    // Get storage folder for doc html
    $uri = 'public://';

    // Get basic folder from settings.
    $uri .=  \Drupal::config('doc_to_html.basicsettings')->get('doc_to_html_folder');

    // Remove all managet file stored in uri.
    $this->deleteManagedFile($uri);
    $this->deleteUnmanagedFile($uri);

  }

  /**
   * @param $uri
   * @return bool|false|string
   */
  public function realPath($uri){
    return $this->filesystemservice->realpath($uri);
  }

  public function escapeSpaceRealPath($uri){
    $path = $this->realPath($uri);
    return str_replace(' ','\ ',$path);
  }

  /**
   * This function convert doc or docx to html extension
   * @param $uri
   */
  public function getUriHTMLFrom($uri){
    $source_extension = array(
      '.docx' => '.docx',
      '.DOCX' => '.DOCX',
      '.doc' => '.doc',
      '.DOC' => '.DOC',
    );

    foreach ($source_extension as $extension => $match){
      if (strpos($uri, $extension) !== FALSE) {
        $uri = str_replace($match, '.html', $uri);

      }
    }
    return $uri;
  }

  /**
   * @param $uri
   * @return bool
   */
  protected function deleteManagedFile($uri){

    // Define query
    $query = $this->queryservice->get('file');
    $query->condition('uri',$uri.'%','LIKE');

    // Return all fid have parts of storage folder.
    $fids = $query->execute();

    if(!empty($fids)){

      // Delete multiple fids.
      file_delete_multiple($fids);
      return TRUE;
    }
    else{
      return FALSE;
    }
  }

  /**
   * @param $path
   * @return bool
   */
  private function deleteUnmanagedFile($path){

    // Scan directory
    $files = file_scan_directory($path,'//');
    if(!empty($files)){
      // Change permission
      file_prepare_directory($uri,FILE_MODIFY_PERMISSIONS);

      foreach ($files as $file) {

        // Get absolute path of file
        $file_real_path = $this->filesystemservice->realpath($file->uri);

        //Check if it is file.
        if(is_file($file_real_path)){

          // Delete file.
          file_unmanaged_delete($file->uri);
        }
      }
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}
