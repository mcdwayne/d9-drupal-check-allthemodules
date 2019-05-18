<?php

namespace Drupal\planyo\ulap\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\planyo\Common\PlanyoUtils;

class PlanyoUlapController {
  public function content() {
    if (isset($_POST['ulap_url']))
      $params = $_POST;
    else
      $params = $_GET;
    $content = PlanyoUtils::planyo_send_request($params);
    $response = new Response(
                             $content,
                             Response::HTTP_OK,
                             array('content-type' => 'text/html')
                             );
    return $response;
  }
}

?>