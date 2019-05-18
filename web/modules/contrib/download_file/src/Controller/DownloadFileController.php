<?php
/**
 * Created by PhpStorm.
 * User: benjaminmullins
 * Date: 4/23/17
 * Time: 8:10 AM
 */

namespace Drupal\download_file\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
#use Drupal\file\Entity\File;

class DownloadFileController  extends ControllerBase {
    public function download_file_direct_download($file_id  = null){

        $file = \Drupal\file\Entity\File::load($file_id);
        $default_headers = file_get_content_headers($file);
        $custom_headers = [
            'Content-Type' => 'force-download',
            'Content-Disposition' => 'attachment; filename="' . $file->getFilename() . '"',
            'Content-Length' => $file->getSize(),
            'Content-Transfer-Encoding' => 'binary',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Accept-Ranges' => 'bytes'
        ];

        $headers = array_merge($default_headers, $custom_headers);
        return new BinaryFileResponse($file->getFileUri(), 200, $headers);
    }
}