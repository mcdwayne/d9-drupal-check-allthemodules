<?php

namespace Drupal\ueditor;


class Lists {
    public function __construct($allowFiles, $listSize, $path, $request) {
        $this->allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
        $this->listSize = $listSize;
        $this->path = $path;
        $this->request = $request;
    }

    public function getList() {
        $size = $this->request->get('size', $this->listSize);
        $start = $this->request->get('start', 0);
        $end = $start + $size;
        /* 获取文件列表 */
        $files = $this->getfiles($this->path, $this->allowFiles);
        if (!count($files)) {
            return [
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => count($files)
            ];
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
            $list[] = $files[$i];
        }


        /* 返回数据 */
        $result = [
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ];

        return $result;
    }
    /**
     * 遍历获取目录下的指定类型的文件
     * @param $path
     * @param array $files
     * @return array
     */
    protected function  getfiles($path, $allowFiles, &$files = array()) {
        $savepath = ueditor_get_savepath($path);
        $scanpath = 'public://' . $savepath;
        $files = file_scan_directory($scanpath, '/\.('.$allowFiles.')$/i');
        if (!empty($files)) {
          foreach ($files as $file) {
            $url = substr($file->uri, strlen($scanpath));
            $scanfiles[] = array(
              'url'=> str_replace('//', '/', $path.$url),
              'mtime'=> filemtime($file->uri)
            );    
          }
        }

        return $scanfiles;
    }

}
