<?php

namespace Drupal\s3_zip_image_upload;

class ImageUploadBatch {

  /**
   * Processing batch for image upload.
   */
  public static function imageUploadData($images, $count, $highest_row, $selected_type, $aws_url, $directory_name, $bucket_name, $access_key, $secret_key, &$context) {
    if (!class_exists('S3')) {
      include '/libraries/amazon-s3-php-class/S3.php';
    }
    // AWS access info.
    if (!defined('AWSACCESSKEY')) {
      define('AWSACCESSKEY', $access_key);
    }
    if (!defined('AWSSECRETKEY')) {
      define('AWSSECRETKEY', $secret_key);
    }
    // Instantiate the class.
    $s3 = new S3(AWSACCESSKEY, AWSSECRETKEY);
    $context['message'] = t("Now processing :i of :highest_row", [':i' => $count, ":highest_row" => $highest_row]);
    foreach ($images as $file) {
      $servername = $_SERVER['DOCUMENT_ROOT'];
      $explode = explode('/', $servername);
      $explode_end = end($explode);
      if (empty($explode_end)) {
        $product_file_path = "sites/default/files/$directory_name/";
        $product_filename = $_SERVER['DOCUMENT_ROOT'] . $product_file_path . '' . $file->filename;
      }
      else {
        $product_file_path = "/sites/default/files/$directory_name/";
        $product_filename = $_SERVER['DOCUMENT_ROOT'] . $product_file_path . '' . $file->filename;
      }

      $file_img = $file->filename;
      $handle = fopen($product_filename, "rb");
      fclose($handle);
      // Initiate Imagic Library for Images.
      if ($selected_type == 'images') {
        $product_image = "s3fs-public/$directory_name/" . $file_img;
        $s3->putObjectFile($product_filename, $bucket_name, $product_image, S3::ACL_PUBLIC_READ);
      }
      $context['results'] = $selected_type;
      // Unlink local files.
      unlink($product_filename);
    }
  }

  /**
   * Implements batch process finish.
   */
  function imageUploadDataFinishedCallback($success, $results, $operations) {

// The 'success' parameter means no fatal PHP errors were detected. All
// other error management should be handled using 'results'.
    if ($success) {
      drupal_set_message(t('Successfully Uploaded @result to Amazon S3.', ['@result' => ucfirst($results),]));
    }

// Display any errors while batch processing.
    else {
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
          ]
      );
      drupal_set_message($message, 'error');
    }
  }

}
