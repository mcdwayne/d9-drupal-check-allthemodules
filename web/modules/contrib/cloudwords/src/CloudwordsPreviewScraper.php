<?php
namespace Drupal\cloudwords;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

class CloudwordsPreviewScraper {
  protected $zip_file;
  /**
   *
   * returns filepath of archive for api call
   */
  public function __construct($projectName, $languageCode, $sourceObjectId, $path) {
    try {
      $client = new \GuzzleHttp\Client();

      $res = $client->get($path);
      $status = $res->getStatusCode();

      if($status !== 200){
        \Drupal::logger('cloudwords')->notice('Unable to retreive static content to prepare in context rreview.');
        return;
      }

      $data = $res->getBody(true)->getContents();

      // @todo change to temp dir similar to other archives
      $upload_dir = 'private://cloudwords/static_preview/';

      //SET PRIMARY DIRECTORY FOR STORAGE OF STATIC PAGE DIRECTORIES
      //project name - source - target - object id
      if (!file_prepare_directory($upload_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        form_set_error('reference', t('Unable to create the upload directory.'));
        \Drupal::logger('cloudwords')->notice(t('Unable to create the upload directory.'), []);
      }

      $static_dir_name = $projectName.'-'.$languageCode.'-'.$sourceObjectId;
      $drupal_static_path_full = $upload_dir.$static_dir_name;
      if (!file_prepare_directory($drupal_static_path_full, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        form_set_error('reference', t('Unable to create the upload directory.'));
        \Drupal::logger('cloudwords')->notice(t('Unable to create the upload directory.'), []);
      }

      $linked_assets = $this->generate_static_linked_files($data);

      $cloudwords_base_url = \Drupal::config('cloudwords.settings')->get('cloudwords_drupal_base_url');

      // linked assets such as css and js are packaged into the static file archive for the preview bundle
      foreach($linked_assets as $linked_asset){

        $uri_parts = parse_url($linked_asset);
        $url_scheme = '';
        if(!isset($uri_parts['scheme']) && !isset($uri_parts['host'])){
          $url_scheme = $cloudwords_base_url;
          //$url_scheme = 'internal:';
          if(!(substr($uri_parts['path'], 0, 1) == '/')) {
            $url_scheme .= '/';
          }
        }
        $linked_asset_url =  Url::fromUri($url_scheme.$linked_asset, ['absolute' => TRUE]);

        $path_info = pathinfo($linked_asset_url->toString());

        // convert css / js to relative path and bundle the files in the archive.
        // Most Drupal sites aggregate and cache css and js so filename references in static preview will become stale
        if(strpos($path_info['extension'], 'css') === 0 || strpos($path_info['extension'], 'js') === 0) {
          $linked_asset_req = $client->get($linked_asset_url->toString());
          if ($linked_asset_req->getStatusCode() != 200) {
            // @todo report item that couldn't get through
            continue;
          }
          $linked_asset_request_body = $linked_asset_req->getBody(true)->getContents();

          $linked_asset_parts = parse_url($linked_asset);
          $linked_asset_relative_filepath = $linked_asset_parts['path'];

          $static_linked_asset_relative_path = $drupal_static_path_full . '/' . dirname($linked_asset_relative_filepath);
          $static_linked_asset_filename = basename($linked_asset_relative_filepath);

          if (!file_prepare_directory($static_linked_asset_relative_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
            //drupal_set_message('Unable to create the upload directory.');
            \Drupal::logger('cloudwords')->notice('Unable to create the upload directory.');
          }

          // @todo these can remain external resources
          if (isset($linked_asset_request_body)) {
            file_put_contents($static_linked_asset_relative_path . '/' . $static_linked_asset_filename, $linked_asset_request_body);
          }
          //Replace references to css and js files with relative references
          $relative_path = dirname($linked_asset_relative_filepath) . '/' . $static_linked_asset_filename;

          //replace all instances of string references to files in html
          $data = str_replace($linked_asset, ltrim($relative_path, '/'), $data);
        }else{
          $data = str_replace('src="'.$linked_asset.'"', 'src="'.$linked_asset_url->toString().'"', $data);
        }
      }

      // put html of page with rewritten links into index file in folder
      file_put_contents($drupal_static_path_full.'/index.html', $data);

      // zip up entire static page directory
      $archiver = new \ZipArchive();
      $zip_file = \Drupal::service("file_system")->realpath($drupal_static_path_full . '.zip');
      $destination = \Drupal::service("file_system")->realpath($drupal_static_path_full);
      if ($archiver->open($zip_file, \ZIPARCHIVE::CREATE || \ZIPARCHIVE::OVERWRITE) !== TRUE) {
        return FALSE;
      }

      $this->list_valid_files_for_archive($destination, $valid_files);

      foreach ($valid_files as $file) {
        $archiver->addFromString(str_replace($destination . '/', '', $file), file_get_contents($file));
      }
      $archiver->close();

      //remove directory created for archive
      $this->remove_files_for_archive($destination);
      rmdir($destination);
      $this->zip_file = $zip_file;
    } catch (RequestException $e) {
      \Drupal::logger('cloudwords')->notice(print_r($e->getMessage(),true), []);
    }

  }

  public function list_valid_files_for_archive($destination, &$valid_files){
    if ($items = @scandir($destination)) {
      foreach ($items as $item) {
        if (is_file("$destination/$item") && strpos($item, '.') !== 0) {
          $valid_files[] = "$destination/$item";
        }else if(is_dir("$destination/$item") && strpos($item, '.') !== 0) {
          $this->list_valid_files_for_archive("$destination/$item", $valid_files);
        }
      }
    }
  }

  public function remove_files_for_archive($destination){
    if ($items = @scandir($destination)) {
      foreach ($items as $item) {
        if (is_file("$destination/$item") && strpos($item, '.') !== 0) {
          unlink("$destination/$item");
        }else if(is_dir("$destination/$item") && strpos($item, '.') !== 0) {
          $this->remove_files_for_archive("$destination/$item");
          rmdir("$destination/$item");
        }
      }
    }
  }

  /**
   * Find any linked files within the page and copy them over to the destination.
   */
  public function generate_static_linked_files($data) {

    $dom = new \domDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($data);
    $dom->preserveWhiteSpace = false;

    //get linked files from source text via regex
    $matches = $this->list_static_linked_files($data);

    $image_hrefs = [];
    // @todo remove image fetch - use absolute urls for all images
    //get all images referenced in html to copy to relative directory
    $images = $dom->getElementsByTagName('img');
    foreach ($images as $image) {
      $image_src = $image->getAttribute('src');
      if(strlen($image_src) > 0){
        $image_hrefs[] = $image_src;
      }
    }

    $matches = array_merge($matches, $image_hrefs);

    $files = [];
    $matches = array_unique($matches);

    libxml_clear_errors();

    return $matches;
  }


  /**
   * Find any linked files within the file by regex matches
   */
  public function list_static_linked_files($data) {

    // checking common files.
    $matches = [];
    // Match include("/anything").
    $includes = [];
    preg_match_all('/include\(["\'][\/\.]([^"\']*)["\']\)/', $data, $includes);
    if (isset($includes[1])) {
      $matches += $includes[1];
    }
    // Match url("/anything").
    // Regex based on drupal_build_css_cache().
    // - Finds css files for "@import url()".
    // - Finds files within css files - url(images/button.png).
    // - Excludes data based images.
    $imports = [];
    preg_match_all('/url\(\s*[\'"]?(?!(?:data)+:)([^\'")]+)[\'"]?\s*\)/i', $data, $imports);
    if (isset($imports[1])) {
      $matches = array_merge($matches, $imports[1]);
    }
    // Match src="/{anything}".
    //TODO - what about files stored on cdns?
    // @TODO we don't want image src to come through here
//    $srcs = array();
//    preg_match_all('/src=["\'][\/\.]([^"\']*)["\']/i', $data, $srcs);
//    if (isset($srcs[1])) {
//      $matches = array_merge($matches, $srcs[1]);
//    }
    // Match href="/{anything}.{ico|css|pdf|doc}(?{anything})" () querystring is
    // optional.
    // @TODO probably exclude linked files from archive.. in case of docs and things..
    $hrefs = [];
    preg_match_all('/href="[\/\.]([^"]*\.(ico|css|pdf|doc|js)(\?[^"]*)?)"/i', $data, $hrefs);
    if (isset($hrefs[1])) {
      $matches = array_merge($matches, $hrefs[1]);
    }
    // Match href='/{anything}.{ico|css|pdf|doc}(?{anything})' () querystring is
    // optional.
    $hrefs = [];
    preg_match_all("/href=\'[\/\.]([^']*\.(ico|css|pdf|doc|js)(\?[^']*)?)\'/i", $data, $hrefs);
    if (isset($hrefs[1])) {
      $matches = array_merge($matches, $hrefs[1]);
    }

    $files = [];

    $matches = array_unique($matches);

    //remove extensions  
    $exclude_extensions = ['html'];
    foreach($matches as $k => $v){
      $url = parse_url($v);
      $path = pathinfo($url['path']);
      if(isset($path['extension']) && in_array($path['extension'], $exclude_extensions)){
        unset($matches[$k]);
      }
    }

    return $matches;
  }
  public function get_zip_file(){
    return $this->zip_file;
  }
}