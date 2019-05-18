<?php

namespace Drupal\canvas_api;

/**
 * The Canvas class is the base class used for making all Canvas API calls.
 *
 * Depending on the requested API, we need to make calls using the following
 * methods: GET (default), PUT, POST, and DELETE. Child classes can set the
 * method and parameters for the call.
 */
abstract class Canvas {
  
  public $params = [];
  public $path;
  private $client;

  public function __construct(CanvasClient $client) {
    $this->client = $client->client;
  }
  /**
   * Execute the API request and return the response.
   *
   * @param $method
   * GET (default), PUT, POST, and DELETE
   *
   * @return array
   * JSON-decoded response from Canvas
   */
  protected function _getResponse($method){

    $path = 'api/v1/' . $this->path;

    try{

      $query = $this->buildQuery($this->params);

      $response = call_user_func_array(array($this->client,$method),array($path,array('query' => $query)));

      // Canvas limits the number of responses returned in a GET request. If we pass
      // per_page=100, we can get up to 100, but that's all; if there are more,
      // we need to get the "next" page, which is passed in the Link: attribute
      // in the response header.
      if($method == 'get'){
        $data = json_decode($response->getBody(),TRUE);
        $next = $this->getNextURL($response);
        // We use a counter as a failsafe to this becoming an infinite loop.
        $i = 0;
        while($next){
          $response = $this->client->get($next);
          $data = array_merge($data,json_decode($response->getBody(),TRUE));
          $next = $this->getNextURL($response);
          $i++;
          if($i>50){
            // THIS IS JUST A FAILSAFE;
            die('Over 50 iterations');
          }
        }
        return $data;
      }
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      return json_decode($e->getResponse()->getBody()->getContents(),TRUE);
    }
    return json_decode($response->getBody(),TRUE);    
  }

  public function put(){
    return $this->getResponse('put');
  }
  
  public function post(){
    return $this->getResponse('post');
  }
  
  public function get(){
    $this->params['per_page'] = 100;
    return $this->getResponse('get');
  }

  public function delete(){
    return $this->getResponse('delete');
  }
  
  /**
   * Extracts the "next" url from the response header.
   * 
   * @param object $response
   * Http response
   *
   * @return string
   * Link to next URL if found, FALSE if not
   */
  protected function getNextURL($response){
    $linkHeader = $response->getHeader('Link');
    $link = reset($linkHeader);
    $matches = [];
    $pattern = '/,<(.+)>; rel="next"/';
    preg_match($pattern, $link, $matches);
    return isset($matches[1]) ? $matches[1] : FALSE;
  }

  /**
   * Helper function to build the query.
   *
   * Array values passed to Canvas must be in the form foo[]=bar&foo[]=baz, but
   * http_build_query (used by the Guzzle Client) will encode this as
   * foo[0]=bar&foo[1]=baz, which will fail. This helper function will also
   * allow us to pass in parameters as arrays, i.e.:
   *    $canvas_api->params = array(
   *      'course' => array(
   *         'account_id' => 1,
   *         'name' => 'Course Name',
   *         'term_id' => 6,
   *      ),
   *    );
   *
   * See @link https://canvas.instructure.com/doc/api/courses.html#method.courses.update @endlink
   *
   * @param array $params
   * @return string
   */
  protected function buildQuery($params){

    $query = [];
    foreach($params as $key=>$param){
      if(is_array($param)) {
        foreach ($param as $key2=>$value) {
          $index = is_int($key2) ? '' : $key2;
          $query[] = $key . '[' . $index . ']=' . $value;
        }
      } else {
        $query[] = $key . '=' . $param;
      }
    }
    return implode('&',$query);
  }
  
}
