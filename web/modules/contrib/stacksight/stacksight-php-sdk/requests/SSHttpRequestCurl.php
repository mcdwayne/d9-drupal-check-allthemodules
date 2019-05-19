<?php 

class SSHttpRequestCurl extends SSHttpRequest implements SShttpInterface {

    public $type = 'curl';

    public function sendRequest($data, $url = false, $id_handle = false){
        $data_string = json_encode($data);
        $total_url = ($url) ? INDEX_ENDPOINT_01.$url : INDEX_ENDPOINT_01.'/'.$data['index'].'/'.$data['eType'];
        $ch = curl_init($total_url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
//        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'api');
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
        if((defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG === true) && defined('STACKSIGHT_DEBUG_MODE') && STACKSIGHT_DEBUG_MODE === true) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HEADER, true);
        } else{
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_HEADER, false);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        curl_exec($ch);

        if((defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG === true) && defined('STACKSIGHT_DEBUG_MODE') && STACKSIGHT_DEBUG_MODE === true) {
            $curl_handle_info = curl_getinfo($ch);
            $curl_info = array();
            /*
            if(!isset($curl_info[$id_handle]))
                $curl_info[$id_handle] = $curl_handle_info;
            elseif((int) $curl_handle_info['http_code'] == 200){
                $curl_info[$id_handle] = $curl_handle_info;
            }
            */
            $curl_info[$id_handle] = $curl_handle_info;
            $curl_info[$id_handle]['response'] = curl_multi_getcontent($ch);
            $_SESSION['stacksight_debug'][$id_handle]['request_info'] = $curl_info[$id_handle];
        }

    }
}
