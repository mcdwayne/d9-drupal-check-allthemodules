<?php

/**
 * @file
 * Contains \Drupal\resume\Form\ResumeForm.
 */

namespace Drupal\s3_zip_image_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MultipleImageUploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multipleimageupload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $library = libraries_detect('amazon-s3-php-class');
    if (!$library['installed']) {
      if ($library['error'] == 'not found' || $library['error'] == 'not detected') {
        $lib_error_message = strip_tags($library['error message']);
        drupal_set_message(t('@error Please make sure the library is <a href="@installedcorrectly">installed correctly</a>.', array('@error' => $lib_error_message, '@installedcorrectly' => $base_url . '/admin/help/s3_zip_image_upload')), 'error');
        watchdog('amazon-s3-php-class', $library['error message'], NULL, WATCHDOG_ERROR);
        return;
      }
    }
    $s3fs_bucket = \Drupal::state()->get('s3f s_bucket');
    $s3fs_awssdk2_access_key = \Drupal::state()->get('s3fs_awssdk2_access_key');
    $s3fs_awssdk2_secret_key = \Drupal::state()->get('s3fs_awssdk2_secret_key');
    $form['file_type'] = [
      '#type' => 'select',
      '#title' => t('File Type'),
      '#options' => [
        'images' => t('Images'),
      ],
      '#default_value' => 'images',
      '#disabled' => TRUE,
      '#description' => t('Allowed file extensions : jpg,jpeg,png'),
    ];
    $form['aws_creds'] = [
      '#type' => 'fieldset',
      '#title' => t('SHOW AMAZON WEB SERVICES CREDENTIALS'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['aws_creds']['destination_path'] = [
      '#type' => 'textfield',
      '#title' => t('Amazon S3 path'),
      '#default_value' => "http://$s3fs_bucket.s3.amazonaws.com/s3fs-public",
      '#disabled' => TRUE,
    ];
    $form['aws_creds']['bucket_name'] = [
      '#type' => 'textfield',
      '#title' => t('S3 Bucket Name'),
      '#default_value' => $s3fs_bucket,
      '#disabled' => TRUE,
    ];
    $form['aws_creds']['access_key'] = [
      '#type' => 'textfield',
      '#title' => t('Amazon Web Services Access Key'),
      '#default_value' => $s3fs_awssdk2_access_key,
      '#disabled' => TRUE,
    ];
    $form['aws_creds']['secret_key'] = [
      '#type' => 'textfield',
      '#title' => t('Amazon Web Services Secret Key'),
      '#default_value' => $s3fs_awssdk2_secret_key,
      '#disabled' => TRUE,
    ];

    $form['aws_creds']['folder_name'] = [
      '#type' => 'textfield',
      '#title' => t('S3 folder name'),
      '#description' => t('Name of the folder where images will store on AWS'),
      '#required' => TRUE,
    ];
    $Max_file_size = 10; // Max file size = 10 MB.
    $form['zip_file'] = [
      '#type' => 'managed_file',
      '#title' => t('Upload Zip File'),
      '#size' => 48,
      '#description' => t('Warning : MAX SIZE LIMIT : 1GB. !break ZIP File should contain Images directly, not in Folder. !breakAllowed Image Extesion are: JPG, JPEG, PNG'),
      '#upload_validators' => [
        'file_validate_extensions' => ['zip ZIP'],
        'file_validate_size' => [$Max_file_size * 1024 * 1024],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Upload',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = reset($form_state->getValue('zip_file'));
    $file = \Drupal\file\Entity\File::load($fid);
//    $filename = $file->get('filename')->value;
//    $file_mime = $file->get('filemime')->value;
    $file_uri = $file->get('uri')->value;
    $selected_type = $form_state->getValue('file_type');
    $directory_name = $form_state->getValue('folder_name');
    $bucket_name = \Drupal::state()->get('s3fs_bucket');
    $aws_url = "http://$bucket_name.s3.amazonaws.com/s3fs-public";
    $access_key = \Drupal::state()->get('s3fs_awssdk2_access_key');
    $secret_key = \Drupal::state()->get('s3fs_awssdk2_secret_key');
    if (is_writable("public://$directory_name")) {
      drupal_mkdir("public://$directory_name");
    }
    $bDirectoryExists = is_dir("public://$directory_name/");
    if ($bDirectoryExists) {
      //$file = file_save_upload('zip_file', array('file_validate_extensions' => array('zip')), 'temporary://', FILE_EXISTS_REPLACE);
      // Zip FIle.
      $zip = new \ZipArchive();
      $zip = zip_open(drupal_realpath($file_uri));
      $count = 0;
      $invalid_files = '';
      if (is_resource($zip)) {
        while ($zip_entry = zip_read($zip)) {
          $product_filename = zip_entry_name($zip_entry);
          $product_extension = 'jpg';
          if (!in_array($product_extension, ['jpg', 'JPG', 'jpeg', 'JPEG'])) {
            $count++;
            $invalid_files .= $count . ')' . $product_filename . '<br/>';
          }
        }
        $zip = zip_close($zip);
        // Images Validation in Zip.
        if ($count > 0) {
          $message = t('An error occurred and processing did not complete.!break Please Upload only image files. !break There are %count invalid files as @invalid_files', array(
            '!break' => '<br/>',
            '%count' => $count,
            '@invalid_files' => $invalid_files,
              )
          );
          drupal_set_message($message, 'error');
        }
        else {
          // Zip Extracting.
          $zip = new \ZipArchive();
          $zip->open(drupal_realpath($file_uri));
          $zip->extractTo("sites/default/files/$directory_name");
          $zip->close();
        }
        $dir = "sites/default/files/$directory_name/";
        // Scan Product Images directory for all files.
        if ($selected_type == 'images') {
          $files = file_scan_directory($dir, '/^.*\.(jpg|JPG|jpeg|JPEG|png|PNG)$/');
        }
        $images_created = array_chunk($files, 25);
        $highest_row = count($files);
        $count = 0;
        $upload_batch = [
          'title' => t('Uploading Images'),
          'progress_message' => t('Uploading Files to S3...'),
          'error_message' => t('Error!'),
          // Function call on completion of batch process.
          'finished' => '\Drupal\s3_zip_image_upload\ImageUploadBatch::imageUploadDataFinishedCallback',
        ];
        foreach ($images_created as $images) {
          $upload_batch = [
            'title' => t('Upload Zip Images...'),
            'operations' => [
              [
                '\Drupal\s3_zip_image_upload\ImageUploadBatch::imageUploadData',
                [
                  $images,
                  $count,
                  $highest_row,
                  $selected_type,
                  $aws_url,
                  $directory_name,
                  $bucket_name,
                  $access_key,
                  $secret_key,
                ],
              ],
            ],
          ];
        }
        batch_set($upload_batch);
      }
    }
    else {
      drupal_set_message(t('Unable to create directory. Please check public:// permissions'), 'error');
    }
  }

}
