<?php
/**
 * -----------------------------------------------------------------------
 * vBSSO is a solution which helps you connect to different software platforms
 * via secure Single Sign-On.
 *
 * Copyright (c) 2011-2017 vBSSO. All Rights Reserved.
 * This software is the proprietary information of vBSSO.
 *
 * Author URI: http://www.vbsso.com
 * License: GPL version 2 or later -
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------
 */

namespace Drupal\vbsso\Controller;

use Drupal\file\Entity\File;

/**
 * Class VbssoPictureController
 *
 * @package Drupal\vbsso\Controller
 */
class VbssoPictureController {

    /**
     * Temporary path
     */
    protected $path;

    /**
     * Url to user avatar from master
     *
     * @var string $url url to image
     */
    protected $url;

    /**
     * Cache image folder
     */
    const IMAGE_CACHE_FOLDER = 'public://styles/thumbnail/public/vbsso_pictures/';
    
    /**
     * VbssoPictureController constructor.
     *
     * @param string $path path to save user pictures
     * @param string $url image url from master
     * 
     * @return void
     */
    public function __construct($path, $url) {
        $this->path = $path;
        $this->url = $url;

        $this->deleteImageCache();
    }

    /**
     * Get user avatar from vBulletin
     *
     * @param \Drupal\user\Entity\User $user current logged user
     *
     * @return bool
     */
    public function initialize(\Drupal\user\Entity\User $user) {

        $this->savePicture();

        $file = File::create(
            array(
                'uid' => 1,
                'filename' => $this->getFileName(),
                'uri' => $this->path . $this->getFileName(),
                'status' => 1,
            )
        );
        $file->save();

        $user->set('user_picture', $file);
        $user->save();
    }

    /**
     * Save picture from vBulletin
     *
     * @return \Drupal\file\FileInterface|false
     */
    public function savePicture() {

        $baa_username = sharedapi_decode_data(
            variable_get(VBSSO_NAMED_EVENT_FIELD_API_KEY, SHAREDAPI_DEFAULT_API_KEY), variable_get(VBSSO_NAMED_EVENT_FIELD_BAA_USERNAME, null)
        );

        $baa_password = sharedapi_decode_data(
            variable_get(VBSSO_NAMED_EVENT_FIELD_API_KEY, SHAREDAPI_DEFAULT_API_KEY), variable_get(VBSSO_NAMED_EVENT_FIELD_BAA_PASSWORD, null)
        );

        $options = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            )
        );

        if ($baa_username) {
            $options['http'] = array(
                'header' => "Authorization: Basic " . base64_encode("$baa_username:$baa_password")
            );
        }

        $context = stream_context_create($options);
        $picture = file_get_contents($this->url, false, $context);
        file_prepare_directory($this->path, FILE_CREATE_DIRECTORY);
        return file_save_data($picture, $this->path . $this->getFileName(), FILE_EXISTS_REPLACE);
    }

    /**
     * Create temporary file name
     *
     * @return mixed
     */
    protected function getFileName() {
        return md5($this->url) . $this->getExtension();
    }

    /**
     * Get image extension
     *
     * @return string
     */
    protected function getExtension() {
        $imageSize = getimagesize($this->url);
        
        if (!$imageSize) {
            return '.jpg';
        }
        
        $ext = explode('/', $imageSize['mime']);
        $ext = '.' . $ext[1];
        return $ext;
    }

    /**
     * Delete user image cache
     * 
     * @return void
     */
    protected function deleteImageCache() {
        $image = self::IMAGE_CACHE_FOLDER . $this->getFileName();
        if (file_exists($image)) {
            unlink($image);
        }
    }
}
