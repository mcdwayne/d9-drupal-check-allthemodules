<?php
/*
    Software License Agreement (BSD License)

    Copyright (c) 2005-2014, SendSpace Ltd.
    All rights reserved.

    Redistribution and use of this software in source and binary forms,
	with or without modification, are permitted provided that the following
	conditions are met:

    1. Redistributions of source code must retain the above
       copyright notice, this list of conditions and the
       following disclaimer.

    2.  Redistributions in binary form must reproduce the above
       copyright notice, this list of conditions and the
       following disclaimer in the documentation and/or other
       materials provided with the distribution.

    3. Neither the name of SendSpace Ltd. nor the names of its
       contributors may be used to endorse or promote products
       derived from this software without specific prior
       written permission of SendSpace Ltd.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
    "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
    LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
    A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
    OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
    SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
    TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
    PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
    LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
    NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Drupal\sendspace\API;

define('SENDSPACE_API_VERSION', '1.0');
define('SENDSPACE_API_ROOT_FOLDER', '0');

class SendspaceRestAPI
{
    private $SessionKey = 0;
    private $ApiKey = 0;
    private $AppVer = '';
    private $Username = '';
    private $Password = '';
    private $LastError;

    public $Debug = false;
    public $UserInfo = array();
    public $APIUrl = 'http://api.sendspace.com/rest/';

    public function __construct($ApiKey, $AppVer)
    {
        if (empty($ApiKey))
            die('Cannot accept an empty API KEY');

        $this->ApiKey = $ApiKey;
        $this->AppVer = $AppVer;

        $this->_SetLastError('', 0, '');
    }

    /**
    * login into account
    * @param string $Username user name
    * @param string $Password user password
    * @return SessionKey or false according to sessions start status
    */
    public function Login($Username, $Password)
    {
        $this->Username = $Username;
        $this->Password = $Password;
        return $this->_StartSession();
    }

    /**
    * Get last error code + message
    * @return array contains error code + message
    */
    public function GetLastError()
    {
        return $this->LastError;
    }

    /**
    * set session key
    * @param string $SessionKey session key to use
    * @return boolean true/false if session key is valid and saved
    */
    public function SetSessionKey($SessionKey)
    {
        $this->SessionKey = $SessionKey;
        return TRUE;
        /* - no need to check session all the time
        if ($this->CheckSession())
            return TRUE;
        else
        {
            $this->SessionKey = 0;
            return FALSE;
        }*/
    }

    /**
    * Check if current Session Key valid (not expired) or not
    * @return true if session is valid to use, else false
    */
    public function CheckSession(&$vars)
    {
        //print_r($this->SessionKey);
        $params = array(
            'session_key' => $this->SessionKey,
            );

        if (($response = $this->_CallAPIMethod('auth.checksession', $params)) === FALSE)
            return FALSE;

        if ($response['session'][0]['value'] == 'ok')
        {
            foreach ($response as $key => $val)
                $vars[$key] = $val[0]['value'];
            unset($vars['session']);
            return TRUE;
        }
        else
            return FALSE;
    }

    /**
    * Logout a session
    * @return boolean true/false if logout went ok
    */
    public function Logout()
    {
        $params = array(
            'session_key' => $this->SessionKey,
            );

        if (($response = $this->_CallAPIMethod('auth.logout', $params)) === FALSE)
            return FALSE;
        else
            return TRUE;
    }

    /**
    * Start a new user session on the server
    * @return SessionKey or false according to sessions start status
    */
    private function _StartSession()
    {
        $params = array(
            'api_key' => $this->ApiKey,
            'api_version' => SENDSPACE_API_VERSION,
            'response_format' => 'xml',
            'app_version' => $this->AppVer,
            );

        if (($response = $this->_CallAPIMethod('auth.createtoken', $params)) === FALSE)
            return FALSE;

        $token = $response['token'][0]['value'];

        $params = array(
            'token' => $token,
            'user_name' => $this->Username,
            'tokened_password' => md5($token.md5($this->Password)),
            );

        if (($response = $this->_CallAPIMethod('auth.login', $params)) === FALSE)
            return FALSE;

        $this->SessionKey = $response['session_key'][0]['value'];

        foreach ($response as $key => $value)
        {
            if ($key != 'session_key')
                $this->UserInfo[$key] = $value[0]['value'];
        }

        return $this->SessionKey;
    }

    /**
    * Get upload info needed to upload a file
    * @param string $SpeedLimit limit of upload speed
    * @param string $Description short description
    * @param string $Password file password
    * @param string $FolderId folder id
    * @param string $RecipientEmail recipient email to receive notification of upload
    * @param string $NotifyUploader true/false, email uploader
    * @param string $RedirectUrl page to redirect after upload
    * @return array with upload info
    */
    public function UploadGetInfo($SpeedLimit = 0, $Description = null, $Password = null, $FolderId = null, $RecipientEmail = null, $NotifyUploader = null, $RedirectUrl = null)
    {
        $params = array(
                'session_key' => $this->SessionKey,
                'speed_limit' => $SpeedLimit,
            );

        if (!is_null($Description))
            $params['description'] = urlencode($Description);

        if (!is_null($Password))
            $params['password'] = urlencode($Password);

        if (!is_null($FolderId))
            $params['folder_id'] = urlencode($FolderId);

        if (!is_null($RecipientEmail))
            $params['recipient_email'] = urlencode($RecipientEmail);

        if (!is_null($NotifyUploader))
            $params['notify_uploader'] = $NotifyUploader ? '1' : '0';

        if (!is_null($RedirectUrl))
            $params['redirect_url'] = urlencode($RedirectUrl);

        if (($response = $this->_CallAPIMethod('upload.getinfo', $params)) === FALSE)
            return FALSE;

        foreach ($response['upload'][0]['attributes'] as $key => $val)
            $uploadinfo[strtolower($key)] = $val;

        return $uploadinfo;
    }

    /**
    * Get download url of a file
    * @param string $FileId file id
    * @param string $FileUrl file url
    * @return response values or FALSE
    */
    public function DownloadGetInfo($FileId)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'file_id' => $FileId,
            );

        if (($response = $this->_CallAPIMethod('download.getinfo', $params)) === FALSE)
            return FALSE;

        return $this->_GetResponseValues($response['download'][0]['attributes']);
    }

    /**
    * Get info about a file
    * @param string $FileId file id or Array of ids
    * @return array with file info
    */
    public function FilesGetInfo($FileId)
    {
        if (is_array($FileId))
            $Ids = implode(',', $FileId);
        else
            $Ids = $FileId;

        $params = array(
            'session_key' => $this->SessionKey,
            'file_id' => $Ids,
            );

        if (($response = $this->_CallAPIMethod('files.getinfo', $params)) === FALSE)
            return FALSE;

        if (is_array($FileId))
        {
            $files = array();
            if (isset($response['file']))
            {
                foreach ($response['file'] as $file)
                    $files[] = $this->_GetResponseValues($file['attributes']);
            }
            return $files;
        }
        else
            return $this->_GetResponseValues($response['file'][0]['attributes']);
    }

    /**
    * Move a file to a folder
    * @param string $FileId file id or Array of ids
    * @param string $FolderId folder id
    * @return array with file info
    */
    public function FilesMoveToFolder($FileId, $FolderId)
    {
        if (is_array($FileId))
            $Ids = implode(',', $FileId);
        else
            $Ids = $FileId;

        $params = array(
            'session_key' => $this->SessionKey,
            'file_id' => $Ids,
            'folder_id' => $FolderId,
            );

        if (($response = $this->_CallAPIMethod('files.movetofolder', $params)) === FALSE)
            return FALSE;

        if (is_array($FileId))
        {
            $files = array();
            if (isset($response['file']))
            {
                foreach ($response['file'] as $file)
                    $files[] = $this->_GetResponseValues($file['attributes']);
            }
            return $files;
        }
        else
            return $this->_GetResponseValues($response['file'][0]['attributes']);
    }

    /**
    * Set file info
    * @param string $FileId file id
    * @param string $Name file name,
    * @param string $Description file description,
    * @param string $Password password
    * @param string $FolderId folder id
    * @return true/false
    */
    public function FilesSetInfo($FileId, $Name = null, $Description = null, $Password = null, $FolderId = null, $TimeLimit = null, $DownloadLimit = null, $LimitAction = null, $LimitActionToFolder = null)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'file_id' => $FileId
            );

        if ($Name != null)
            $params['name'] = $Name;

        if ($Description != null)
            $params['description'] = $Description;

        if ($Password != null)
            $params['password'] = $Password;

        if ($FolderId != null)
            $params['folder_id'] = $FolderId;

        if ($TimeLimit != null && is_numeric($TimeLimit))
            $params['time_limit'] = $TimeLimit;

        if ($DownloadLimit != null && is_numeric($DownloadLimit))
            $params['download_limit'] = $DownloadLimit;

        if ($LimitAction != null && is_numeric($LimitAction))
            $params['limit_action'] = $LimitAction;

        if ($LimitActionToFolder != null)
            $params['limit_action_to_folder'] = $LimitActionToFolder;

        if (($response = $this->_CallAPIMethod('files.setinfo', $params)) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
    * Delete a file
    * @param string $FileId file id or Array of ids
    * @return true/false
    */
    public function FilesDelete($FileId)
    {
        if (is_array($FileId))
            $Ids = implode(',', $FileId);
        else
            $Ids = $FileId;

        $params = array(
            'session_key' => $this->SessionKey,
            'file_id' => $Ids
            );

        if (($response = $this->_CallAPIMethod('files.delete', $params)) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
    * Send link details to a friend
    * @param string $FileId file id
    * @param string $emails, array of emails
    * @param string $Message, extra message to send to recipients
    * @return true/false
    */
    public function FilesSendMail($FileId, $EmailsList, $Message = '')
    {
        if (is_array($EmailsList))
            $emails = implode(',', $EmailsList);
        else
            $emails = $EmailsList;
        $params = array(
            'session_key' => $this->SessionKey,
            'file_id' => $FileId,
            'emails' => $emails,
            'message' => $Message,
            );

        if (($response = $this->_CallAPIMethod('files.sendmail', $params)) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
    * Get info of a specific folder
    * @param string $FolderId folder id
    * @return array with folder info
    */
    public function FoldersGetInfo($FolderId)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'folder_id' => $FolderId
            );

        if (($response = $this->_CallAPIMethod('folders.getinfo', $params)) === FALSE)
            return FALSE;

        foreach ($response['folder'][0]['attributes'] as $key => $val)
            $folderinfo[strtolower($key)] = $val;

        return $folderinfo;
    }

    /**
    * Set folder info
    * @param string $FolderId folder id
    * @param string $Name folder name
    * @param boolean $Shared folder is shared or not
    * @return true/false
    */
    public function FoldersSetInfo($FolderId, $Name = null, $Shared = null, $ParentFolder = null)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'folder_id' => $FolderId
            );

        if ($Name != null)
            $params['name'] = $Name;

        if ($Shared != null)
            $params['shared'] = $Shared ? 1 : 0;

        if ($ParentFolder != null)
            $params['parent_folder_id'] = $ParentFolder;

        if (($response = $this->_CallAPIMethod('folders.setinfo', $params)) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
    * Create a new folder
    * @param string $Name folder name
    * @param boolean $Shared folder is shared or not
    * @param string $ParentFolder parent folder id
    * @return true/false
    */
    public function FoldersCreate($Name, $Shared, $ParentFolder)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'name' => $Name,
            'shared' => $Shared ? 1 : 0,
            'parent_folder_id' => $ParentFolder,
            );

        if (($response = $this->_CallAPIMethod('folders.create', $params)) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
    * Delete a folder
    * @param string $FolderId folder id
    * @return true/false
    */
    public function FoldersDelete($FolderId)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'folder_id' => $FolderId,
            );

        if (($response = $this->_CallAPIMethod('folders.delete', $params)) === FALSE)
            return FALSE;

        return TRUE;
    }

    /**
    * Get full directory contents (files + folders)
    * @param string $FolderId folder id
    * @return array of files & folders
    */
    public function FoldersGetContents($FolderId)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'folder_id' => $FolderId,
            );

        if (($response = $this->_CallAPIMethod('folders.getcontents', $params)) === FALSE)
            return FALSE;

        // extract folders
        $folders = array();
        if (isset($response['folder']))
        {
            foreach ($response['folder'] as $folder)
                $folders[] = $this->_GetResponseValues($folder['attributes']);
        }

        // extract files
        $files = array();
        if (isset($response['file']))
        {
            foreach ($response['file'] as $file)
                $files[] = $this->_GetResponseValues($file['attributes']);
        }

        $content['folders'] = $folders;
        $content['files'] = $files;

        return $content;
    }

    /**
    * Get shared directory contents (files + folders)
    * @param string $FolderId folder id
    * @return array of files & folders
    */
    public function FoldersGetShared($FolderId)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'folder_id' => $FolderId,
            );

        if (($response = $this->_CallAPIMethod('folders.getshared', $params)) === FALSE)
            return FALSE;

        // extract current
        if (isset($response['current'][0]))
            $content['current'] = $this->_GetResponseValues($response['current'][0]['attributes']);

        // extract folders
        $folders = array();
        if (isset($response['folder']))
        {
            foreach ($response['folder'] as $folder)
                $folders[] = $this->_GetResponseValues($folder['attributes']);
        }

        // extract files
        $files = array();
        if (isset($response['file']))
        {
            foreach ($response['file'] as $file)
                $files[] = $this->_GetResponseValues($file['attributes']);
        }

        $content['folders'] = $folders;
        $content['files'] = $files;

        return $content;
    }

    public function FoldersMove($FolderId, $NewParentFolderId)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'folder_id' => $FolderId,
            'parent_folder_id' => $NewParentFolderId,
            );

        if (($response = $this->_CallAPIMethod('folders.move', $params)) === FALSE)
            return FALSE;

        return TRUE;
    }

    public function FoldersSendMail($FolderId, $EmailsList, $Message = '')
    {
        if (is_array($EmailsList))
            $emails = implode(',', $EmailsList);
        else
            $emails = $EmailsList;
        $params = array(
            'session_key' => $this->SessionKey,
            'folder_id' => $FolderId,
            'emails' => $emails,
            'message' => $Message,
            );

        if (($response = $this->_CallAPIMethod('folders.sendmail', $params)) === FALSE)
            return FALSE;

        return TRUE;
    }

    public function FoldersGetAll()
    {
        $params = array(
            'session_key' => $this->SessionKey,
            );

        if (($response = $this->_CallAPIMethod('folders.getall', $params)) === FALSE)
            return FALSE;

        // extract folders
        $folders = array();
        if (isset($response['folder']))
        {
            foreach ($response['folder'] as $folder)
            {
                $folder_info = $this->_GetResponseValues($folder['attributes']);
                $folders[$folder_info['id']] = $folder_info;
            }
        }

        return $folders;
    }

    public function AddressBookList()
    {
        $params = array(
            'session_key' => $this->SessionKey,
            );

        if (($response = $this->_CallAPIMethod('addressbook.list', $params)) === FALSE)
            return FALSE;

        $rows = array();
        if (isset($response['contact']))
        {
            foreach ($response['contact'] as $row)
            {
                $info = $this->_GetResponseValues($row['attributes']);
                $rows[$info['email']] = $info;
            }
        }

        return $rows;
    }

    public function AddressBookAdd($email, $name, $note, $type)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'email' => $email,
            'name' => $name,
            'description' => $note,
            'type' => $type,
            );

        if (($response = $this->_CallAPIMethod('addressbook.add', $params)) === FALSE)
            return FALSE;

        return true;
    }

    public function AddressBookDelete($Email)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'email' => $Email,
            );

        if (($response = $this->_CallAPIMethod('addressbook.delete', $params)) === FALSE)
            return FALSE;

        return true;
    }


    public function AddressBookUpdate($Email, $Name = null, $Desc = null, $Type = null)
    {
        $params = array(
            'session_key' => $this->SessionKey,
            'email' => $Email,
            );

        if (!is_null($Name))
            $params['name'] = $Name;
        if (!is_null($Desc))
            $params['description'] = $Desc;
        if (!is_null($Type))
            $params['type'] = $Type;

        if (($response = $this->_CallAPIMethod('addressbook.update', $params)) === FALSE)
            return FALSE;

        return true;
    }

    public function AnonymousUploadGetInfo($SpeedLimit = 0)
    {
        $params = array(
                'api_key' => $this->ApiKey,
                'api_version' => SENDSPACE_API_VERSION,
                'app_version' => $this->AppVer,
                'speed_limit' => $SpeedLimit,
            );

        if (($response = $this->_CallAPIMethod('anonymous.uploadgetinfo', $params)) === FALSE)
            return FALSE;

        foreach ($response['upload'][0]['attributes'] as $key => $val)
            $uploadinfo[strtolower($key)] = $val;

        return $uploadinfo;
    }

    public function AnonymousFilesSendMail($SenderEmail, $RecipientsEmail, $FileId , $Message = '')
    {
        $params = array(
                'api_key' => $this->ApiKey,
                'api_version' => SENDSPACE_API_VERSION,
                'app_version' => $this->AppVer,
                'file_id' => $FileId,
                'sender_email' => $SenderEmail,
                'emails' => $RecipientsEmail,
                'message' => $Message,
            );

        if (($response = $this->_CallAPIMethod('anonymous.filessendmail', $params)) === FALSE)
            return FALSE;

        return TRUE;
    }

    private function _ExtractParamsFromResponse($response)
    {
        $xml_parser = xml_parser_create();
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
        if (xml_parse_into_struct($xml_parser, $response, $vals, $index) === 0)
        {
            $result = FALSE;
        }
        else
        {
            $response_params = array();
            if (isset($vals[0]['tag']) && $vals[0]['tag'] == 'result')
            {
                $response_params['result'] = $vals[0]['attributes'];
                $response_params['params'] = array();
                foreach ($vals as $val)
                {
                    if ($val['tag'] == 'result' || !in_array($val['type'], array('open', 'complete')))
                        continue;

                    $tag_val = array(
                            'value' => isset($val['value']) ? $val['value'] : '',
                            'attributes' => isset($val['attributes']) ? $val['attributes'] : '',
                        );

                    $response_params['params'][$val['tag']][] = $tag_val;
                }
            }

            if (count($response_params) > 0)
                $result = $response_params;
            else
                $result = FALSE;
        }

        xml_parser_free($xml_parser);

        return $result;
    }

    private function _SetLastError($method, $code, $text, $id = null)
    {
        $this->LastError = array(
            'method' => $method,
            'text' => $text,
            'code' => $code,
            );

        if (isset($id))
            $this->LastError['id'] = $id;
    }

    private function _GetResponseValues($response)
    {
        $ar = array();
        foreach ($response as $key => $val)
            $ar[strtolower($key)] = $val;
        return $ar;
    }

    private function _CallAPIMethod($method, $params = array())
    {
        // build the url
        $url = $this->APIUrl;
        $url .= "?method=$method";
        foreach ($params as $key=>$val)
            $url .= "&$key=" . urlencode($val);

        if ($this->Debug)
            error_log('API Request: ' . $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($this->Debug)
            error_log('API Response: ' . $result);

        // parse response
        $xml_parts = $this->_ExtractParamsFromResponse($result);

        // make sure the response is for the current request
        if ($xml_parts['result']['method'] != $method)
        {
            // weird condition, but we want to make sure it's the right response
            $this->_SetLastError($xml_parts['result']['method'], '-1', 'unexpected api client error');
            return FALSE;
        }

        if ($xml_parts['result']['status'] != 'ok')
        {
            $id = null;
            if (isset($xml_parts['params']['id'][0]['value']))
                $id = $xml_parts['params']['id'][0]['value'];
            $this->_SetLastError($xml_parts['result']['method'], $xml_parts['params']['error'][0]['attributes']['code'], $xml_parts['params']['error'][0]['attributes']['text'], $id);
            return FALSE;
        }

        return $xml_parts['params'];
    }
}
?>
