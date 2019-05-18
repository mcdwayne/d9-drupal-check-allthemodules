<?php
/**
 * @file
 * Contains \Drupal\image_dimension_generator\Controller\ImageDimensionGeneratorController.
 */
namespace Drupal\image_dimension_generator\Controller;

// load the image manipulation class
use Drupal\image_dimension_generator\Plugin\ImageGenerator;

class ImageDimensionGeneratorController {
  public function content($fid, $width, $height, $quality) {
    $ext = (isset($_GET['ext'])?$_GET['ext']:'');
    $image = self::image_dimension_generator_get_image($fid, $width, $height, $quality, $ext);
    $url = file_create_url($image['directory']) . '/' . $image['filename'];
    $image_name = $image['filename'];
    switch(end(explode('.',$image['filename']))){
        case 'jpg':
            $mime = 'image/jpeg';
            $image_create = "imagecreatefromjpeg";
            $image = "imagejpeg";
            break;
        case 'png':
            $mime = 'image/png';
            $image_create = "imagecreatefrompng";
            $image = "imagepng";
            break; 
        default:
            $image = false;
            break;
    }
    
    if(!$image){echo "Wrong image format. Use either jpg or png only.";exit;}

    header("Content-type: {$mime}");
    header("Content-Disposition: filename={$image_name}");
    $image($image_create($url));
  }

  // get details of generated image
  public function image_dimension_generator_get_image($fid, $width, $height, $quality, $ext = "", $directory = "public://idg") {
    $file = \Drupal\file\Entity\File::load($fid);
    $source = drupal_realpath($file->getFileUri());
    $directory_path = drupal_realpath($directory);

    $source_filename = basename($source);
    $expected_filename = reset(explode('.', $source_filename)) . "_" . $width . "_" . $height . "_" . $quality . "." . end(explode('.', $source_filename));
    $expected_file = rtrim($directory_path, '/') . '/' . $expected_filename;

    if(file_exists($expected_file)){
        return ['filename' => $expected_filename, 'directory' => $directory];
    }

    file_prepare_directory($directory_path, FILE_CREATE_DIRECTORY);
    $imageGenerator = new ImageGenerator($source);
    $new_image_url = $imageGenerator->resize_crop_image($width,$height,$directory_path,$quality,$ext);
    return ['filename' => basename($new_image_url), 'directory' => $directory];
  }

  // function to generate image url by calling function
  public function generate($fid, $width, $height, $quality, $ext = "", $directory = "public://idg") {
    $image = image_dimension_generator_get_image($fid, $width, $height, $quality, $ext, $directory);
    $url = file_create_url($image['directory']) . '/' . $image['filename'];
    return $url;
  }

}

