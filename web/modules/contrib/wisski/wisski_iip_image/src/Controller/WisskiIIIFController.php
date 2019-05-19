<?php

namespace Drupal\wisski_iip_image\Controller;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Drupal\image\Entity\ImageStyle;

use Drupal\wisski_salz\Entity\Adapter;


class WisskiIIIFController {

  public function manifest(\Drupal\wisski_core\Entity\WisskiEntity $wisski_individual = NULL) {

    // This is based on the 
    // Iperion-ch Simple IIIF Manifest builder: Version 1.0
    //
    // Many thanks go to joseph.padfield@ng-london.org.uk
    
    $settings = \Drupal::configFactory()->getEditable('wisski_iip_image.wisski_iiif_settings');
        
    if(empty($settings->get('iiif_server'))) {
      drupal_set_message("IIIF is not configured properly. Please do that <a href='admin/config/wisski/iiif_settings'>here</a>.", "error");
      return array();
    }
    
    // url of the iiif server        
    $iiif_url = $settings->get('iiif_server');

    // the base-path provided to the IIIF-Server - should be subtracted from our paths!
    $iiif_base_path = $settings->get('iiif_prefix');

    global $base_url;

    // the url to this manifest    
    $manifest_url = $base_url . "/wisski/navigate/" . $wisski_individual->id() . "/iiif_manifest";

    // the url for the sequences 
    // I dont really know what this is for up to now...    
    $sequence_url = $base_url . "/wisski/sequence/" . $wisski_individual->id();


    // Get the logo for display purpose.
    $logo = theme_get_setting('logo.url');
    
    if(empty($logo)) 
      $logo = '/' . drupal_get_path('module', 'wisski_core') . "/images/img_nopic.png";

    // Basic example manifest information - this should be replaced with
    // specific details or a database call for dynamic details.
    $manifest = array (
      //manifest comment
      "comment" => "The original dataset with additional metadata can be found <a href='" . $base_url . "/wisski/navigate/" . $wisski_individual->id() . "/view'>here</a>. This IIIF manifest is generated from the WissKI system at " . $base_url,
      "id" => $manifest_url,
      //Unique display label for the manifest
      "label" => $wisski_individual->label(), //"A few small images just to display a working manifest",
      //Resolvable url to required logo image
      "logo" => $logo,
      //For simple manifests this does not need to change, does not need to resolve
      "sequence_id" => $base_url,
      // this should be customisable later on!
      "licence" => $settings->get("iiif_licence"),
      "attribution" => $settings->get("iiif_attribution"),
    );

    // load all adapters
    $adapters = entity_load_multiple('wisski_salz_adapter');

    // get the bundle for this
    $bundle_id = $wisski_individual->bundle();

    // get the entity id
    $entity_id = $wisski_individual->id();

    // build up an image array    
    $images = array();
    
    // go through all adapters and get all images for this.
    foreach($adapters as $adapter) {
      if($adapter->hasEntity($entity_id) && method_exists($adapter->getEngine(), "getImagesForEntityId")) {
        $images = array_merge($images, $adapter->getEngine()->getImagesForEntityId($entity_id,$bundle_id));
      }
      
    }
    
    $style = ImageStyle::load('wisski_pyramid');

    // if the style does not exist - create it.
    // this is copied from IIP-Module - perhaps not having it that
    // redundant would be better?
    if(empty($style)) {
      $service = \Drupal::service('image.toolkit.manager');
      $toolkit = $service->getDefaultToolkit();
      
      if(empty($toolkit) || $toolkit->getPluginId() !== "imagemagick") {
        drupal_set_message('Your default toolkit is not imagemagick. Please use imagemagick for this module.', "error");
        return;
      }
      
      $config = \Drupal::service('config.factory')->getEditable('imagemagick.settings');
      
      $formats = $config->get('image_formats');
      
      if(!isset($formats["PTIF"])) {
        drupal_set_message("PTIF was not a valid image format. We enabled it for you. Make sure it is supported by your imagemagick configuration.");
        $formats["PTIF"] = array('mime_type' => "image/tiff", "enabled" => TRUE);
        $config->set('image_formats', $formats);
        $config->save();
      }
      

      $image_style_name = 'wisski_pyramid';

      if(! $image_style = \Drupal\image\Entity\ImageStyle::load($image_style_name)) {
        $values = array('name'=>$image_style_name,'label'=>'Wisski Pyramid Style');
        $image_style = \Drupal\image\Entity\ImageStyle::create($values);
        $image_style->addImageEffect(array('id' => 'WisskiPyramidalTiffImageEffect'));
        $image_style->save();
      }
      $style = $image_style;
    }


    // get the wisski storage backend    
    $storage = \Drupal::entityManager()->getStorage('wisski_individual');
 
    // many variables to store data
    // full file path - absolute.
    $file_paths = array();
    // local path for drupal, typically public://something
    $local_paths = array();
    // just the filename
    $filenames = array();

    // iterate through all images. 
    foreach($images as $image) {
      $local_uri = $storage->ensureSchemedPublicFileUri($image);
      
      // get the file name - last part of the uri
      $exp = explode('/', $local_uri);
      
      $last_part = $exp[count($exp)-1];
      
      // if pdf - skip it!
      if(stripos($last_part, ".pdf") !== FALSE) {
        continue;
      }
      
      $filenames[] = $last_part;
      
      // get the pyramid
      $local_paths[] = $local_uri;

      $local_pyramid = $style->buildUri($local_uri);

      // if there is nothing, create a derivative
      if(!file_exists($local_pyramid) || empty(filesize($local_pyramid)) )
        $style->createDerivative($local_uri,$local_pyramid);
      
      $local_pyramid = \Drupal::service('file_system')->realpath($local_pyramid);
      $pyramids[] = $local_pyramid;
      
      // if there is something we have to erase this to get a proper
      // path
      
      if($iiif_base_path && strpos($local_pyramid, $iiif_base_path) === 0) { 	
        $remaining = substr($local_pyramid, strlen($iiif_base_path));
      } else {
        $remaining = $local_pyramid;
      }
      
      $file_paths[] = $remaining;      
    }
    
    $ims = array();
    
    // fill the image array.
    foreach($file_paths as $key => $filepath) {
      // try to load the image.
      $image = \Drupal::service('image.factory')->get($local_paths[$key]); 

      $height = 0;
      $width = 0;
      
      // only calculate this if there is an image
      if(!empty($image)) {
        $height = $image->getHeight();
        $width = $image->getWidth();
      }

      $ims[$key] = array(
          "image_name" => $wisski_individual->label(),
          "image_height" => $height,
          "image_width" => $width,
          "image_ppmm" => 314.96,
          "image_caption" => $wisski_individual->label(),
          "image_path" => $filepath,
        );      
    }
        
    // calculation of canvases based on the ims
    $canvases = [];
    foreach ($ims as $k => $d)
    {

      $nm = $sequence_url . "/" . $filenames[$k] . "/normal.json";
      $cp = $d["image_caption"];
      if ($d["image_ppmm"])
      {
        $scale = ($d["image_ppmm"]/$d["image_width"]); //$d["image_width"];
        // Can be added to the canvas below, but does not allow you so set
        // scale for each image just for each set of images.
        $addRuler = array(
          "service" => array(
            "@context" => "http://iiif.io/api/annex/services/physdim/1/context.json",
            "profile" => "http://iiif.io/api/annex/services/physdim",
            "physicalScale" => $scale,
            "physicalUnits" => "mm"
          )
        );
      } else {
        $addRuler = array();
      }
      
      $canvases[] = [
        "@id" => $nm,
        "@type" => "sc:Canvas",
        "label" => $cp,
        "height" => $d['image_height'],
        "width" => $d['image_width'],
        "images" => [ [
          "@type" => "oa:Annotation",
          "motivation" => "sc:painting",
          "on" => $nm,
          "resource" => [
            "@id" => $iiif_url . $d['image_path'] . "/full/full/0/default.jpg",
            "@type" => "dctypes:Image",
            "format" => "image/jpeg",
            "height" => $d['image_height'],
            "width" => $d['image_width'],
            "service" => [
              "@context" => "http://iiif.io/api/image/2/context.json",
              "@id" => $iiif_url . $d['image_path'],
              "profile" => "http://iiif.io/api/image/2/level2.json"
            ]
          ] 
          ]          
        ]
      ];
      
      if(!empty($addRuler)) {
        $num = count($canvases)-1;
        $canvases[$num] = array_merge($canvases[$num], $addRuler);
      }
    }
    
    $data = [
      "@context" => "http://iiif.io/api/presentation/2/context.json",
      "@id" => $manifest['id'],
      "@type" => "sc:Manifest",
      "label" => $manifest['label'],
      "license" => $manifest['licence'],
      "attribution" => $manifest['attribution'],
      "logo" => $manifest['logo'],
      "metadata" => [
        [
          "label" => "Description",
          "value" => $manifest['comment']
        ]
      ],
      "description" => $manifest['comment'],
      "viewingDirection" => "left-to-right",
      "viewingHint" => "individuals",
      "sequences" => [
        [
          "@id" => "$manifest[sequence_id]",
          "@type" => "sc:Sequence",
          "label" => "Normal Order",
          "canvases" => $canvases
        ]
      ]
    ];
        
    $data['#cache'] = [
      'max-age' => 1, 
      'contexts' => [
         'url',
      ],
    ];

    $response = new CacheableJsonResponse();

    $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
    $response->setData($data);

    return $response;
  }
  
}
