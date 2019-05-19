<?php

namespace Drupal\ueditor\Uploader;

use Drupal\Core\Site\Settings;

/**
 * UEditor编辑器通用上传类
 */
abstract class Upload {
    protected $fileField; //文件域名
    protected $file; //文件上传对象
    protected $base64; //文件上传对象
    protected $config; //配置信息
    protected $oriName; //原始文件名
    protected $fileName; //新文件名
    protected $fullName; //完整文件名,即从当前配置目录开始的URL
    protected $filePath; //完整文件名,即从当前配置目录开始的URL
    protected $fileSize; //文件大小
    protected $fileType; //文件类型
    protected $stateInfo; //上传状态信息,
    protected $stateMap = array( //上传状态映射表，国际化用户需考虑此处数据的国际化
        "SUCCESS", //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制",
        "文件大小超出 MAX_FILE_SIZE 限制",
        "文件未被完整上传",
        "没有文件被上传",
        "上传文件为空",
        "ERROR_TMP_FILE" => "临时文件错误",
        "ERROR_TMP_FILE_NOT_FOUND" => "找不到临时文件",
        "ERROR_SIZE_EXCEED" => "文件大小超出网站限制",
        "ERROR_TYPE_NOT_ALLOWED" => "文件类型不允许",
        "ERROR_CREATE_DIR" => "目录创建失败",
        "ERROR_DIR_NOT_WRITEABLE" => "目录没有写权限",
        "ERROR_FILE_MOVE" => "文件保存时出错",
        "ERROR_FILE_NOT_FOUND" => "找不到上传文件",
        "ERROR_WRITE_CONTENT" => "写入文件内容错误",
        "ERROR_UNKNOWN" => "未知错误",
        "ERROR_WATERMARK_ADD" => "添加水印出错",
        "ERROR_WATERMARK_TEXT_RGB" => "水印文字颜色格式不正确",
        "ERROR_WATERMARK_NOT_FOUND" => "需要添加水印的图片不存在",
        "ERROR_WATERMARK_SIZE" => "水印太大或图片太小",
        "ERROR_DEAD_LINK" => "链接不可用",
        "ERROR_HTTP_LINK" => "链接不是http链接",
        "ERROR_HTTP_CONTENTTYPE" => "链接contentType不正确"
    );

    abstract function doUpload();

    /**
     * 构造函数
     * @param string $fileField 表单名称
     * @param array $config 配置项
     * @param bool $base64 是否解析base64编码，可省略。若开启，则$fileField代表的是base64编码的字符串表单名
     */
    public function __construct($config, $request) {
        $this->config = $config;
        $this->request = $request;
        $this->fileField = $this->config['fieldName'];
        $this->global_settings = \Drupal::config('ueditor.settings')->get('ueditor_global_settings');
    }

    /**
     * 抽象方法,上传核心方法
     * @return array
     */

    public function upload() {
        $this->doUpload();
        return $this->getFileInfo();
    }

    /**
     * 上传保存文件到drupal
     * @param $imagedata
     * @param $savePath
     * @return string
     */
    protected function savefileToDrupal($imagedata, $savePath, $managed = FALSE, $replace = FILE_EXISTS_RENAME) {
        $local = $managed ? file_save_data($imagedata, 'public://'.$savePath, $replace) : file_unmanaged_save_data($imagedata, 'public://'.$savePath, $replace);
        return $local;
    }

    /**
     * 添加水印
     * @param $savePath
     * @return string
     */
    protected function addWatermark($savePath) {
      $watermark_type = $this->global_settings['ueditor_watermark_type'];
      $watermark_place = $this->global_settings['ueditor_watermark_place'];
      if($watermark_type == 'image'){
        $watermark_path = $this->global_settings['ueditor_watermark_path'];
        $watermark_alpha = $this->global_settings['ueditor_watermark_alpha'];
        $success = $this->imageWaterMark($savePath, $watermark_place, $watermark_path, $watermark_alpha);
      }else{
        global $base_url;
        $watermark_textcontent = $this->global_settings['ueditor_watermark_textcontent'];
        $watermark_textfontsize = $this->global_settings['ueditor_watermark_textfontsize'];
        $watermark_textcolor = $this->global_settings['ueditor_watermark_textcolor'];
        $success = $this->imageWaterMark($savePath, $watermark_place, '', '', $watermark_textcontent, $watermark_textfontsize, $watermark_textcolor);
      }

      return $success;
    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    protected function getStateInfo($errCode) {
        return !$this->stateMap[$errCode] ? $this->stateMap["ERROR_UNKNOWN"] : $this->stateMap[$errCode];
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    protected function getFileExt()
    {
        return strtolower(strrchr($this->oriName, '.'));
    }

    /**
     * 重命名文件
     * @return string
     */
    protected function getFullName() {
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->config["pathFormat"];
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);

        //过滤文件名的非法自负,并替换文件名
        $oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
        $oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
        $format = str_replace("{filename}", $oriName, $format);

        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }

        //用Transliteration生成文件名
        if(strpos($format, '{transliteration_filename}')){
          if(module_exists('transliteration') && function_exists('transliteration_clean_filename')){
            $format = str_replace("{transliteration_filename}", transliteration_clean_filename($oriName), $format);
          }
        }       

        $ext = $this->getFileExt();
        return $format . $ext;
    }

    /**
     * 获取文件名
     * @return string
     */
    protected function getFileName () {
        return substr($this->filePath, strrpos($this->filePath, '/') + 1);
    }

    /**
     * 获取文件完整路径
     * @return string
     */
    protected function getFilePath() {
        $site_path = \Drupal::service('site.path');
        $fullname = ueditor_get_savepath($this->fullName);
        $uploadPath = Settings::get('file_public_path', $site_path . '/files');
        $rootPath = strtr(DRUPAL_ROOT,'\\','/');

        if (substr($fullname, 0, 1) != '/') {
            $fullname = '/' . $fullname;
        }

        return $rootPath . '/' . $uploadPath . $fullname;
    }

    /**
     * 文件类型检测
     * @return bool
     */
    protected function checkType() {
        return in_array($this->getFileExt(), $this->config["allowFiles"]);
    }

    /**
     * 文件大小检测
     * @return bool
     */
    protected function  checkSize($type) {
        return $this->fileSize <= ($this->global_settings['ueditor_'.$type.'_maxsize']*1000);
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo() {
        return array(
            "state" => $this->stateInfo,
            "url" => $this->fullName,
            "title" => $this->fileName,
            "original" => $this->oriName,
            "type" => $this->fileType,
            "size" => $this->fileSize
        );
    }

   /*
    * 功能：PHP图片水印 (水印支持图片或文字)
    * 参数：
    *$groundImage 背景图片，即需要加水印的图片，暂只支持GIF,JPG,PNG格式；
    *$waterPos水印位置，有10种状态，0为随机位置；
    *1为顶端居左，2为顶端居中，3为顶端居右；
    *4为中部居左，5为中部居中，6为中部居右；
    *7为底端居左，8为底端居中，9为底端居右；
    *$waterImage图片水印，即作为水印的图片，暂只支持GIF,JPG,PNG格式；
    *$waterText文字水印，即把文字作为为水印，支持ASCII码，不支持中文；
    *$textFont文字大小，值为1、2、3、4或5，默认为5；
    *$textColor文字颜色，值为十六进制颜色值，默认为#FF0000(红色)；
    *
    * 注意：Support GD 2.0，Support FreeType、GIF Read、GIF Create、JPG 、PNG
    *$waterImage 和 $waterText 最好不要同时使用，选其中之一即可，优先使用 $waterImage。
    *当$waterImage有效时，参数$waterString、$stringFont、$stringColor均不生效。
    *加水印后的图片的文件名和 $groundImage 一样。
    */
    protected function imageWaterMark($groundImage, $waterPos = 0, $waterImage = '', $wateralpha = '', $waterText = '', $fontSize = '', $textColor = '') {
      $isWaterImage = FALSE;
      //读取水印文件
      if(!empty($waterImage) && file_exists($waterImage)) {
        $isWaterImage = TRUE;
        $water_info = getimagesize($waterImage);
        //取得水印图片的宽
        $water_w = $water_info[0];
        //取得水印图片的高
        $water_h = $water_info[1];
        //取得水印图片的格式
        switch($water_info[2]){
          case 1:
            $water_im = imagecreatefromgif($waterImage);
            break;
          case 2:
            $water_im = imagecreatefromjpeg($waterImage);
            break;
          case 3:
            $water_im = imagecreatefrompng($waterImage);
            break;
          default:
            return "ERROR_TYPE_NOT_ALLOWED";
        }
      }
      //读取背景图片
      if(!empty($groundImage) && file_exists($groundImage)) {
        $ground_info = getimagesize($groundImage);
        //取得背景图片的宽
        $ground_w = $ground_info[0];
        //取得背景图片的高
        $ground_h = $ground_info[1];
        //取得背景图片的格式
        switch($ground_info[2]){
          case 1:
            $ground_im = imagecreatefromgif($groundImage);
            break;
          case 2:
            $ground_im = imagecreatefromjpeg($groundImage);
            break;
          case 3:
            $ground_im = imagecreatefrompng($groundImage);
            break;
          default:
            return "ERROR_TYPE_NOT_ALLOWED";
        }
      }else{
        return "ERROR_WATERMARK_NOT_FOUND";
      }
      //水印位置
      //图片水印
      if($isWaterImage){
        $w = $water_w;
        $h = $water_h;
        $label = "图片的";
      }else{
        //文字水印
        $fontface = drupal_get_path('module', 'ueditor') . '/fonts/fzcyjt.ttf';
        $temp = imagettfbbox(ceil($fontSize*1.2),0,$fontface,$waterText);//取得使用 TrueType 字体的文本的范围
        $w = $temp[2] - $temp[6];
        $h = $temp[3] - $temp[7];
        unset($temp);
        $label = "文字区域";
      }
      if( ($ground_w<$w) || ($ground_h<$h) ){
        //echo "需要加水印的图片的长度或宽度比水印".$label."还小，无法生成水印！";
        return "ERROR_WATERMARK_SIZE";
      }
      switch($waterPos){
        case 0://随机
          $posX = rand(0,($ground_w - $w));
          $posY = rand(0,($ground_h - $h));
          break;
        case 1://1为顶端居左
          $posX = 10;
          $posY = $h + 10;
          break;
        case 2://2为顶端居中
          $posX = ($ground_w - $w) / 2;
          $posY = $h + 10;
          break;
        case 3://3为顶端居右
          $posX = $ground_w - $w;
          $posY = $h + 10;
          break;
        case 4://4为中部居左
          $posX = 10;
          $posY = ($ground_h - $h) / 2;
          break;
        case 5://5为中部居中
          $posX = ($ground_w - $w) / 2;
          $posY = ($ground_h - $h) / 2;
          break;
        case 6://6为中部居右
          $posX = $ground_w - $w;
          $posY = ($ground_h - $h) / 2;
          break;
        case 7://7为底端居左
          $posX = 10;
          $posY = $ground_h - $h;
          break;
        case 8://8为底端居中
          $posX = ($ground_w - $w) / 2;
          $posY = $ground_h - $h;
          break;
        case 9://9为底端居右
          $posX = $ground_w - $w - 10;   // -10 是距离右侧10px 可以自己调节
          $posY = $ground_h - $h - 10;   // -10 是距离底部10px 可以自己调节
          break;
        default://随机
          $posX = rand(0,($ground_w - $w));
          $posY = rand(0,($ground_h - $h));
          break;
      }
      //设定图像的混色模式
      imagealphablending($ground_im, true);
      //图片水印
      if($isWaterImage){        
        imagecopymerge($ground_im, $water_im, $posX, $posY, 0, 0, $water_w, $water_h, $wateralpha);//拷贝水印到目标文件 
      }else{
        //文字水印
        if(!empty($textColor) && (strlen($textColor)==7)){
          $R = hexdec(substr($textColor,1,2));
          $G = hexdec(substr($textColor,3,2));
          $B = hexdec(substr($textColor,5));
        }else{
          return "ERROR_WATERMARK_TEXT_RGB";
        }
        imagettftext($ground_im,$fontSize,0,$posX,$posY,imagecolorallocate($ground_im,$R,$G,$B),$fontface,$waterText);
      }
      //生成水印后的图片
      @unlink($groundImage);
      //取得背景图片的格式
      switch($ground_info[2]){
        case 1:
          imagegif($ground_im,$groundImage);
          break;
        case 2:
          imagejpeg($ground_im,$groundImage);
          break;
        case 3:
          imagepng($ground_im,$groundImage);
          break;
        default:
          return "ERROR_WATERMARK_ADD";
      }

      //释放内存
      if(isset($water_info)){
        unset($water_info);
      }
      if(isset($water_im)){
        imagedestroy($water_im);
      }
      unset($ground_info);
      imagedestroy($ground_im);
      return TRUE;
    }
}
