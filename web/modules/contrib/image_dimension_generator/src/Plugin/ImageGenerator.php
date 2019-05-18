<?php
namespace Drupal\image_dimension_generator\Plugin;

// load the image manipulation class
require 'Zebra_Image.php';

class ImageGenerator {
    
/*
*  @var string 
*/
public $source;
    
/*
*  @var integer 
*/
public $quality;

/*
*  @var integer 
*/
public $original_width;

/*
*  @var integer 
*/
public $original_height;

/*
*  @var string 
*/
public $original_filename;

/*
*  @var string 
*/
public $original_extension;

/**
 *  Constructor of the class.
 *
 *  Initializes the class and the default properties
 *
 *  @return void
 */
public function __construct($source) {
    $original_image_info = getimagesize($source);
    $original_filename = basename($source);
    
    $this->source = $source;
    $this->original_width = $original_image_info[0];
    $this->original_height = $original_image_info[1];
    $this->original_filename = $original_filename;
    $this->original_extension = end(explode('.', $original_filename));
}
        


/**
 *  Function to resize image and crop as necessary automatically
 *  @return string
 */

public function resize_crop_image($target_width, $target_height, $target_directory = '', $quality = 100, $target_extension) {

    // create a new instance of the class
    $image = new Zebra_Image();
    
    // if you handle image uploads from users and you have enabled exif-support with --enable-exif
    // (or, on a Windows machine you have enabled php_mbstring.dll and php_exif.dll in php.ini)
    // set this property to TRUE in order to fix rotation so you always see images in correct position
    $image->auto_handle_exif_orientation = false;
    
    // indicate a source image (a GIF, PNG or JPEG file)
    $image->source_path = $this->source;
    
    // indicate a target image
    // note that there's no extra property to set in order to specify the target
    // image's type -simply by writing '.jpg' as extension will instruct the script
    // to create a 'jpg' file
    $image->target_path = rtrim($target_directory, '/') . '/' . reset(explode('.', $this->original_filename)) . "_" . $target_width . "_" . $target_height . "_" . $quality .  "." . (empty($target_extension)?'jpg':$target_extension);

    // since in this example we're going to have a jpeg file, let's set the output
    // image's quality
    $image->jpeg_quality = $quality;
    
    // some additional properties that can be set
    // read about them in the documentation
    $image->preserve_aspect_ratio = true;
    $image->enlarge_smaller_images = true;
    $image->preserve_time = true;
    $image->handle_exif_orientation_tag = true;
    
    // resize the image to exactly 100x100 pixels by using the "crop from center" method
    // (read more in the overview section or in the documentation)
    //  and if there is an error, check what the error is about
    if (!$image->resize($target_width, $target_height, ZEBRA_IMAGE_CROP_CENTER)) {
    
        // if there was an error, let's see what the error is about
        switch ($image->error) {
    
            case 1:
                echo 'Source file could not be found!';
                break;
            case 2:
                echo 'Source file is not readable!';
                break;
            case 3:
                echo 'Could not write target file!';
                break;
            case 4:
                echo 'Unsupported source file format!';
                break;
            case 5:
                echo 'Unsupported target file format!';
                break;
            case 6:
                echo 'GD library version does not support target file format!';
                break;
            case 7:
                echo 'GD library is not installed!';
                break;
            case 8:
                echo '"chmod" command is disabled via configuration!';
                break;
            case 9:
                echo '"exif_read_data" function is not available';
                break;
    
        }
    // if no errors
    } else {
        return $image->target_path;
    }

}

}