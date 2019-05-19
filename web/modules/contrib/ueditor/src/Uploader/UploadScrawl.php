<?php 

namespace Drupal\ueditor\Uploader;

use Drupal\ueditor\Uploader\Upload;

/**
 * Class UploadScrawl
 * 涂鸦上传
 * @package Drupal\ueditor\Uploader
 */
class UploadScrawl extends Upload {
    public function doUpload() {
        $base64Data = $_POST[$this->fileField];
        $img = base64_decode($base64Data);

        $this->oriName = $this->config['oriName'];
        $this->fileSize = strlen($img);
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

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }

        $filedata = $this->savefileToDrupal($img, $this->savePath);

        //移动文件
        if ($filedata) { //移动成功
            $success = FALSE;
            $use_watermark = $this->global_settings['ueditor_watermark'];
            if($use_watermark){
                $success = $this->addWatermark($this->filePath);
            }
            if($success === TRUE){
                $this->stateInfo = $this->stateMap[0];
            }else{
                $this->stateInfo = $this->getStateInfo($success);
            }
        } else { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
        }
    }
}