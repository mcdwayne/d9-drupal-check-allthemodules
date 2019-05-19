<?php 

namespace Drupal\ueditor\Uploader;

use Drupal\ueditor\Uploader\Upload;

/**
 *
 *
 * Class UploadFile
 *
 * 文件/图像普通上传
 *
 * @package Drupal\ueditor\Uploader
 */
class UploadFile extends Upload{
    public function doUpload() {
        $file = $this->file = $_FILES[$this->fileField];
        if (!$file) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return;
        }
        if ($this->file['error']) {
            $this->stateInfo = $this->getStateInfo($file['error']);
            return;
        } else if (!file_exists($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMP_FILE_NOT_FOUND");
            return;
        } else if (!is_uploaded_file($file['tmp_name'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMPFILE");
            return;
        }

        $this->oriName = $file['name'];
        $this->fileSize = $file['size'];
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->savePath = ueditor_get_savepath($this->fullName);
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize($this->config['type'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }
        // Load the files contents
        $imagedata = file_get_contents($file["tmp_name"]);
        
        $filedata = $this->savefileToDrupal($imagedata, $this->savePath);

        //移动文件
        if ($filedata) { //移动成功
          $success = FALSE;
          $use_watermark = $this->global_settings['ueditor_watermark'];
          if($use_watermark && $this->config['type'] == 'image'){
            $success = $this->addWatermark($this->filePath);
          }else{
            $success = TRUE;
          }

          if($success === TRUE){
            $this->stateInfo = $this->stateMap[0];
          }else{
            $this->stateInfo = $this->getStateInfo($success);
          }
        } else { //移动失败
          $this->stateInfo = $this->getStateInfo("ERROR_FILE_MOVE");
        }
    }
}
