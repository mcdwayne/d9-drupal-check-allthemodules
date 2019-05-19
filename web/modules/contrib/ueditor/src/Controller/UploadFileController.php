<?php

namespace Drupal\ueditor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\ueditor\Uploader\UploadScrawl;
use Drupal\ueditor\Uploader\UploadFile;
use Drupal\ueditor\Uploader\UploadCatch;
use Drupal\ueditor\Lists;

/**
 * Class UploadFileController.
 *
 * @package Drupal\ueditor\Controller
 */
class UploadFileController extends ControllerBase {

  /**
   * Upload.
   *
   * @return string
   *   Return json string.
   */
  public function server(Request $request) {
    date_default_timezone_set("Asia/Chongqing");
    error_reporting(E_ERROR);
    header("Content-Type: text/html; charset=utf-8");

    $file_path = strtr(DRUPAL_ROOT,'\\','/') . '/' . drupal_get_path('module', 'ueditor');
    $config = Json::decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($file_path . '/lib/config.json')), true);
    $ueditor_uploadpath_config = \Drupal::config('ueditor.settings')->get('ueditor_uploadpath_config');
   
    foreach($ueditor_uploadpath_config as $key => $pathitem){
      if(isset($config[$key])){
        $config[$key] = _ueditor_realpath($pathitem);
      }
    }

    $action = $request->get('action');

    switch ($action) {
        case 'config':
            $result = $config;
            break;
        case 'uploadimage':
            $upConfig = array(
                "pathFormat" => $config['imagePathFormat'],
                "maxSize" => $config['imageMaxSize'],
                "allowFiles" => $config['imageAllowFiles'],
                'fieldName' => $config['imageFieldName'],
                'type' => 'image',
            );
            $controller = new UploadFile($upConfig, $request);
            $result = $controller->upload();

            break;
        case 'uploadscrawl':
            $upConfig = array(
                "pathFormat" => $config['scrawlPathFormat'],
                "maxSize" => $config['scrawlMaxSize'],
                //   "allowFiles" => $config['scrawlAllowFiles'],
                "oriName" => "scrawl.png",
                'fieldName' => $config['scrawlFieldName'],
            );
            $controller = new UploadScrawl($upConfig, $request);
            $result = $controller->upload();

            break;
        case 'uploadvideo':
            $upConfig = array(
                "pathFormat" => $config['videoPathFormat'],
                "maxSize" => $config['videoMaxSize'],
                "allowFiles" => $config['videoAllowFiles'],
                'fieldName' => $config['videoFieldName'],
                'type' => 'video',
            );
            $controller = new UploadFile($upConfig, $request);
            $result = $controller->upload();

            break;
        case 'uploadfile':
        default:
            $upConfig = array(
                "pathFormat" => $config['filePathFormat'],
                "maxSize" => $config['fileMaxSize'],
                "allowFiles" => $config['fileAllowFiles'],
                'fieldName' => $config['fileFieldName'],
                'type' => 'file',
            );
            $controller = new UploadFile($upConfig, $request);
            $result = $controller->upload();

            break;

        /* 列出图片 */
        case 'listimage':
            $controller = new Lists(
                    $config['imageManagerAllowFiles'],
                    $config['imageManagerListSize'],
                    $config['imageManagerListPath'],
                    $request);
            $result = $controller->getList();

            break;
        /* 列出文件 */
        case 'listfile':
            $controller = new Lists(
                    $config['fileManagerAllowFiles'],
                    $config['fileManagerListSize'],
                    $config['fileManagerListPath'],
                    $request);
            $result = $controller->getList();

            break;

        /* 抓取远程文件 */
        case 'catchimage':
            $upConfig = array(
                "pathFormat" => $config['catcherPathFormat'],
                "maxSize" => $config['catcherMaxSize'],
                "allowFiles" => $config['catcherAllowFiles'],
                "oriName" => "remote.png",
                'fieldName' => $config['catcherFieldName'],
            );
            
            $sources = $request->get($upConfig['fieldName']);

            $list = [];
            foreach ($sources as $imgUrl) {
                $upConfig['imgUrl'] = $imgUrl;
                $controller = new UploadCatch($upConfig, $request);
                $info = $controller->upload();

                array_push($list, array(
                    "state" => $info["state"],
                    "url" => $info["url"],
                    "size" => $info["size"],
                    "title" => htmlspecialchars($info["title"]),
                    "original" => htmlspecialchars($info["original"]),
                    "source" => htmlspecialchars($imgUrl)
                ));
            }
            $result = [
                'state' => count($list) ? 'SUCCESS' : 'ERROR',
                'list' => $list
            ];
            break;
    }

    /* output */
    if ($request->get('callback')) {
      if (preg_match("/^[\w_]+$/", $request->get('callback'))) {
        $response['state'] = htmlspecialchars($request->get('callback')) . '(' . $result . ')';
        return new JsonResponse($response);
      } else {
        $response['state'] = t('callback parameters are not legitimate');
        return new JsonResponse($response);
      }
    } else {
      return new JsonResponse($result);
    }
  }

}
