<?php

class SSHttpRequestSockets extends SSHttpRequest implements SShttpInterface {

    public $timeout = 10;
    private $_socket;

    public $max_retry = 1;

    private $_state_socket = false;

    private $_socket_error = array();

    public $type = 'sockets';

    private $id_handle;

    public function __destruct(){
        $this->closeSocket();
    }

    public function createSocket($recreate = false){
        $flags = STREAM_CLIENT_ASYNC_CONNECT;
        if(!$this->_socket || $recreate === true){
            if($this->_socket = @stream_socket_client($this->protocol . "://" . $this->host. ':' . $this->port, $errno, $errstr, $this->timeout, $flags)){
                stream_set_blocking($this->_socket, false);
                stream_context_set_params($this->_socket, array(
                    "notification" => array($this, 'stream_notification_callback')
                ));
                $this->_state_socket = true;
                $this->_socket_error = array();
            } else{
                $this->_socket_error = array(
                    'error_num' => $errno,
                    'error_message' =>  $errstr
                );
            }
        }
    }

    private function closeSocket(){
        if($this->_state_socket === true && $this->_socket)
            fclose($this->_socket);
    }

    public function sendRequest($data, $url = null, $id_handle = false){
        if(!$this->_state_socket){
            $this->createSocket();
        }

        if($id_handle){
            $this->id_handle = $id_handle;
        }

        if($this->_state_socket === true){
            if($url === null)
                $url = $this->api_path.'/'.$data['index'].'/'.$data['eType'];
            else
                $url = $this->api_path.$url;

            $content = json_encode($data);

            $req = "";
            $req.= "POST /$url HTTP/1.1\r\n";
            $req.= "Host: " . $this->host . "\r\n";
            $req.= "Content-Type: application/json\r\n";
            $req.= "Accept: application/json\r\n";
            $req.= "Content-length: " . strlen($content) . "\r\n";
            $req.= "\r\n";
            $req.= $content;

            if($sended_lenth = @fwrite($this->_socket, $req)){
                $this->setDebugInfo(false, $sended_lenth);
            } else{
                $sended = false;

                $error_num = $this->_socket_error['error_num'];
                $error_message = $this->_socket_error['error_message'];

                for($i = 0; $i <= $this->max_retry; $i++){
                    usleep(200000);
                    if($sended_lenth = @fwrite($this->_socket, $req)){
                        $sended = true;
                        $this->setDebugInfo(false, $sended_lenth);
                        break;
                    } else{
                        $error_num = $this->_socket_error['error_num'];
                        $error_message = $this->_socket_error['error_message'];
                        SSUtilities::error_log("Error fwrire socket. Tried $i times...", 'error_socket_connection');
                        $this->setDebugInfo(true, "#$error_num: $error_message");
                    }
                }

                if($sended === false){
                    $this->closeSocket();
                    usleep(200000);
                    $this->createSocket();
                    if($sended_lenth = @fwrite($this->_socket, $req)){
                        $this->setDebugInfo(false, $sended_lenth);
                    } else{
                        SSUtilities::error_log("Error fwrire socket after sleep.", 'error_socket_connection');
                        $cURL = new SSHttpRequestCurl();
                        $cURL->sendRequest($data);
                        $this->setDebugInfo(true, "#$error_num: $error_message. Data will sends through cURL");
                    }
                }
            }
        } else {
            if(!empty($this->_socket_error)){
                $error_num = $this->_socket_error['error_num'];
                $error_message = $this->_socket_error['error_message'];
                SSUtilities::error_log("#$error_num: $error_message", 'error_socket_connection');
                $this->setDebugInfo(true, "#$error_num: $error_message");
            }
        }

    }

    private function setDebugInfo($error = false, $message = false){
        if((defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG === true) && defined('STACKSIGHT_DEBUG_MODE') && STACKSIGHT_DEBUG_MODE === true) {
            if($error == true){
                $_SESSION['stacksight_debug'][$this->id_handle]['request_info'][] = array(
                    'error' => true,
                    'data' => $message,
                    'meta' => ($this->_socket) ? stream_get_meta_data($this->_socket) : false
                );
            } else{
                if($data = fread($this->_socket, 4096)){
                    $sended_data = $data;
                } else{
                    $sended_data = 'Wrote '.$message.' bytes.';
                }
                $_SESSION['stacksight_debug'][$this->id_handle]['request_info'][] = array(
                    'error' => false,
                    'data' => $sended_data,
                    'meta' => ($this->_socket) ? stream_get_meta_data($this->_socket) : false
                );
            }
        }
    }
}
