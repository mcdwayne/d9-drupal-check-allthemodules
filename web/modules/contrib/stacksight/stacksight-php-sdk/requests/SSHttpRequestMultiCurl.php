<?php
class SSHttpRequestMultiCurl extends SSHttpRequest implements SShttpInterface
{

    private $objects = array();
    private $ch = array();

    public $type = 'multicurl';

    private $associate = array();

    public function addObject($data, $url, $type)
    {
        if (!empty($data)) {
            $this->objects[] = array(
                'data' => $data,
                'url' => $url,
                'type' => $type
            );
        }
    }

    public function sendRequest($data = false, $url = false, $id_handle = false)
    {
        if (!empty($this->objects)) {
            $mh = curl_multi_init();
            $handles = array();
            foreach ($this->objects as $object) {
                $data = $object['data'];
                $url = $object['url'];
                $data_string = json_encode($data);
                $ch = ($url) ? curl_init(INDEX_ENDPOINT_01 . $url) : curl_init(INDEX_ENDPOINT_01 . '/' . $data['index'] . '/' . $data['eType']);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'api');
                curl_setopt($ch, CURLINFO_HEADER_OUT, false);
                curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

                if((defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG === true) && defined('STACKSIGHT_DEBUG_MODE') && STACKSIGHT_DEBUG_MODE === true) {
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_HEADER, true);
                } else{
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                    curl_setopt($ch, CURLOPT_HEADER, false);
                }

                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($data_string))
                );

                $this->associate[(int) $ch] = $object['type'];
                curl_multi_add_handle($mh, $ch);
                $handles[] = $ch;
            }

            $active = null;
            $curl_info = array();
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            while ($active && $mrc == CURLM_OK) {
                while (curl_multi_exec($mh, $active) === CURLM_CALL_MULTI_PERFORM);
                if (curl_multi_select($mh) != -1) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                        if((defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG === true) && defined('STACKSIGHT_DEBUG_MODE') && STACKSIGHT_DEBUG_MODE === true) {
                            $info = curl_multi_info_read($mh);
                            if (false !== $info) {
                                $id_handle = (int) $info['handle'];
                                $curl_handle_info = curl_getinfo($info['handle']);
                                if(!isset($curl_info[$this->associate[$id_handle]]))
                                    $curl_info[$this->associate[$id_handle]] = $curl_handle_info;
                                elseif((int) $curl_handle_info['http_code'] == 200){
                                    $curl_info[$this->associate[$id_handle]] = $curl_handle_info;
                                }
                                $curl_info[$this->associate[$id_handle]]['response'] = curl_multi_getcontent($info['handle']);
                            }
                        }
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }
            for ($i = 0; $i < count($handles); $i++) {
                curl_multi_remove_handle($mh, $handles[$i]);
            }

            curl_multi_close($mh);

            if((defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG === true) && defined('STACKSIGHT_DEBUG_MODE') && STACKSIGHT_DEBUG_MODE === true) {
                foreach($curl_info as $key => $info){
                    $_SESSION['stacksight_debug'][$key]['request_info'] = $info;
                }
            }

        }
    }
}
