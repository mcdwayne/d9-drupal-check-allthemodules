<?php

namespace Drupal\tmgmt_wordbee\Beebox;
use Drupal\tmgmt_wordbee\Beebox\BeeboxException;

/**
 * PHP class used to make calls to the Wordbee Beebox API
 *
 * WordBee BeeBox PHP SDK
 *
 * @version 1.0
 * @author lpercetti
 * @requires curl
 * @requires BeeboxException
 */
class BeeboxAPI
{
    /**
     * The name of the connector
     * @var string
     */
    private $connectorName;

    /**
     * The version of the connector
     * @var string
     */
    private $connectorVersion;

    /**
     * The current authentification token
     * @var string
     */
    private $token;

    /**
     * The Beebox URL
     * @var string
     */
    private $url;

    /**
     * The Beebox project key
     * @var string
     */
    private $projectKey;

    /**
     * The Beebox project username
     * @var string
     */
    private $username;

    /**
     * The Beebox project password
     * @var string
     */
    private $password;

    /**
     * A cache containing the remote languages list.
     * Used to avoid multiple request on the server.
     * @var array
     */
    private $cacheRemoteLanguages;

    /**
     * BeeBoxAPI constructor
     *
     * @param string $connectorName
     *    The name of the connector (Drupal, Wordpress, ...)
     * @param string $connectorVersion
     *    The connector version
     * @param string $url
     *    The Beebox URL (http://HOST_ADDRESS:HOST_PORT)
     * @param string $projectKey
     *    The CMS Beebox project key (36 characters key)
     * @param string $username
     *    The CMS Beebox project username
     * @param string $password
     *    The CMS Beebox project password
     * */
    public function __construct($connectorName, $connectorVersion, $url, $projectKey, $username, $password) {
        $this->connectorName = $connectorName;
        $this->connectorVersion = $connectorVersion;
        $this->url = $url;
        $this->projectKey = $projectKey;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Proceed an API request to the Beebox server.
     * @param string $action The action
     * @param array $params The params of this action
     * @param boolean $transferContent Whether if the result have to be returned.
     * @param string $httpType The HTTP request Type (GET, POST, PUT, DELETE)
     * @param array $postParams The POST params (will enforce $httpType to be POST)
     * @param string $file The file to upload, as a string (will enforce $httpType to be PUT).
     * @throws BeeboxException This Exception contains details of an eventual error
     * @return array|null The Beebox response object if $transferContent is true
     */
    private function doRequest($action, $params, $transferContent = true, $httpType = null, array $postParams = null, $file = null) {
        /*
         * Creating the URL based on the $action and $params)
         */
        $parameters = '';
        if($params && is_array($params)) {
            foreach($params as $param => $value) {
                $separator = (empty($parameters)) ? '?' : '&';
                $parameters .= $separator.$param.'='.urlencode($value);
            }
        }
        $link = $this->url.'/'.ltrim('api/'.$action.$parameters, '/'); // removes extra slashs

        /*
         * Generate cURL request
         */
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, $transferContent);

        // POST REQUEST
        if($postParams && is_array($postParams)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postParams));
            $httpType = 'POST';
        }
        // PUT REQUEST WITH FILE
        if($file) {
            $fh = fopen('php://temp/maxmemory:256000', 'w');
            if ($fh) {
                fwrite($fh, $file);
                fseek($fh, 0);
            } else
                throw new BeeboxException('Unable to create the tmpfile !');
            curl_setopt($curl, CURLOPT_INFILE, $fh);
            curl_setopt($curl, CURLOPT_INFILESIZE, strlen($file));
            $httpType = 'PUT'; // curl_setopt($curl, CURLOPT_PUT, 1);
        }
        // CUSTOM REQUEST
        if($httpType) {
            if($httpType == 'PUT')
                curl_setopt($curl, CURLOPT_PUT, 1);
            else
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $httpType);
        }

        /*
         * Execute and check the cURL request
         */
        $content = curl_exec($curl);
        if(isset($fh))
            @fclose($fh);

        $http_status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $expected_status_code = (in_array($httpType, ['PUT', 'DELETE'])) ? 204 : 200;
        if($content === false || $http_status_code != $expected_status_code) {
            $details = array(
                'request'       =>  $link,
                'response'      =>  ($content === false) ? json_encode(array('message' => curl_error($curl))) : $content,
                'curl_state'    =>  curl_errno($curl),
                'http_status_code' => $http_status_code
            );

            throw new BeeboxException('Error processing API (returned http_status_code)', $details);
        }

        /*
         * Return the result
         */
        return $content;
    }

    /**
     * Checks if the token is set
     * @return boolean True if the token is a string, false otherwise
     */
    public function isConnected() {
        return is_string($this->token);
    }

    /**
     * Tries to connect to the Beebox using the plugin parameters
     * @see http://documents.wordbee.com/display/bb/API+-+Connect
     * @return boolean true if the connection is successfull, false otherwise
     */
    public function connect() {
        $this->token = $this->doRequest('connect', [
            'connector' =>  $this->connectorName,
            'version'   =>  $this->connectorVersion,
            'project'   =>  $this->projectKey,
            'login'     =>  $this->username,
            'pwd'       =>  $this->password
        ]);
        return $this->isConnected();
    }

    /**
     * Destroy the token and logout
     * @see http://documents.wordbee.com/display/bb/API+-+Disconnect
     * @return void
     */
    public function disconnect(){
        if(!$this->isConnected())
            return;
        try {
            $this->doRequest('disconnect', [
                'token' =>  $this->token
            ]);
        } catch(\Exception $e) {

        }
        $this->token = null;
    }

    /**
     * Calls the Beebox API to retrieve the Beebox project source and target
     * languages
     * @see http://documents.wordbee.com/display/bb/API+-+Get+project+information
     * @return array the language pairs available in the Beebox project like 'source' => 'target1' => 1
     *                                                                                   'target2' => 1
     *                                                                                   'targetX' => 1
     */
    public function getProjectLanguages() {
        if(!$this->cacheRemoteLanguages) {
            if (!$this->isConnected())
                $this->connect();

            $content = $this->doRequest('details', ['token' => $this->token]);
            $details = json_decode($content, true);

            $target = array();
            foreach ($details['targetLocales'] as $i => $targetLocale) {
                $target[$targetLocale] = 1;
            }
            $language_pairs[$details['sourceLocale']] = $target;
            $this->cacheRemoteLanguages = $language_pairs; // cache the result to don't make a lot of useless requests
        } else
            $language_pairs = $this->cacheRemoteLanguages;

        return $language_pairs;
    }

    /**
     * Retrives workprogress of the Beebox for the specified files, if no file specified it will retrieve every file finishing by '-wordpress_connector.xliff'
     * @see http://documents.wordbee.com/display/bb/API+-+Get+translation+status
     * @param array $files Can be an array containning a list of filenames if you want to filter
     * @return array corresponding to the json returned by the Beebox API
     */
    public function getWorkprogress($files = null) {
        if (!$this->isConnected())
            $this->connect();

        $params = [
            'token' =>  $this->token
        ];
        if($files && is_array($files)) {
            $params['filter'] = ['filePaths' => []];
            foreach($files as $filename) {
                $params['filter']['filePaths'][] = [
                    'Item1' =>  '',
                    'Item2' =>  $filename
                ];
            }
        } else {
            $params['filter'] = [
                'patterns' => [
                    'fpath' => '-'.strtolower($this->connectorName).'_connector\\\.xliff$'
                ]
            ];
        }

        return $this->doRequest('workprogress/translatedfiles', null, true, 'POST', $params);
    }

    /**
     * Deletes the specified file in the Beebox
     * @see http://documents.wordbee.com/display/bb/API+-+Delete+file
     * @param String $filename Name of the file you wish to delete
     * @param String $source SOurce language of the file
     */
    public function deleteFile($filename, $source) {
        if (!$this->isConnected())
            $this->connect();

        $this->doRequest('files/file', [
            'token'     =>  $this->token,
            'locale'    =>  $source,
            'filename'  =>  $filename,
            'folder'    =>  ''
        ], false, 'DELETE');

        return true;
    }

    /**
     * Upload a file to the Beebox 'in' folder
     *
     * @param string $fileContent The content of the file you wish to send
     * @param string $filename Name the file will have in the Beebox
     * @param string $source Source language od the file
     */
    public function sendFile($fileContent, $filename, $source) {
        if (!$this->isConnected())
            $this->connect();

        $this->doRequest('files/file', [
            'token'     =>  $this->token,
            'locale'    =>  $source,
            'folder'    =>  '',
            'filename'  =>  $filename
        ], true, null, null, $fileContent);

        return true;
    }

    /**
     * Downloads the specified file from the Beebox
     * @see http://documents.wordbee.com/display/bb/API+-+Get+translated+content
     * @param string $filename Name of the file you wish to retrieve
     * @param string $folder Name of the folder where the file is located (usually the target language)
     * @return string The content of the file
     */
    public function getFile($filename, $folder) {
        if (!$this->isConnected())
            $this->connect();

        return $this->doRequest('files/file', [
            'token'     =>  $this->token,
            'locale'    =>  '',
            'folder'    =>  $folder,
            'filename'  =>  $filename
        ]);
    }

    /**
     * Tells the Beebox to scan its files
     */
    public function scanFiles() {
        if (!$this->isConnected())
            $this->connect();

        $this->doRequest('files/operations/scan', ['token' => $this->token], false, 'PUT');

        return true;
    }

    /**
     * Asks to the Beebox if a scan is required
     *
     * @return boolean True if a scan is required, false otherwise
     * @throws BeeboxException If the response scanRequired is not a boolean.
     */
    public function scanRequired() {
        if (!$this->isConnected())
            $this->connect();

        $content = $this->doRequest('files/status', ['token' => $this->token]);
        $response = json_decode($content, true);
        if (is_array($response) && isset($response['scanRequired']))
            return (boolean)$response['scanRequired'];
        else
            throw new BeeboxException('unexpected result from: scan required');
    }
}