<?php 

namespace Drupal\ueditor\Uploader;

use Drupal\ueditor\Uploader\Upload;

/**
 * Class UploadCatch
 * 图片远程抓取
 *
 * @package Drupal\ueditor\Uploader
 */
class UploadCatch extends Upload {
    public function doUpload() {
      $imgUrl = strtolower(str_replace("&amp;", "&", $this->config['imgUrl']));

      //http开头验证
      if (strpos($imgUrl, "http") !== 0) {
          $this->stateInfo = $this->getStateInfo("ERROR_HTTP_LINK");
          return;
      }
      //获取请求头并检测死链
      $heads = get_headers($imgUrl);
      if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
          $this->stateInfo = $this->getStateInfo("ERROR_DEAD_LINK");
          return;
      }
      //格式验证(扩展名验证和Content-Type验证)
      $fileType = strtolower(strrchr($imgUrl, '.'));
      list($fileType, $vars) = explode('?',$fileType);
      if (!in_array($fileType, $this->config['allowFiles']) || stristr($heads['Content-Type'], "image")) {
          $this->stateInfo = $this->getStateInfo("ERROR_HTTP_CONTENTTYPE");
          return;
      }

      //打开输出缓冲区并获取远程图片
      ob_start();
      $context = stream_context_create(
          array('http' => array(
              'follow_location' => false // don't follow redirects
          ))
      );
      readfile($imgUrl, false, $context);
      $img = ob_get_contents();
      ob_end_clean();
      preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);

      $this->oriName = $m ? $m[1]:"";
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
        if($use_watermark  && $this->config['type'] == 'image'){
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
        $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
      }
    }
}