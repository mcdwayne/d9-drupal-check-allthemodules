<?php

namespace Drupal\webfactory_master\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webfactory_master\Entity\SatelliteEntity;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for webfactory slave routes.
 */
class SatelliteController extends ControllerBase {

  /**
   * Returns the admin page.
   *
   * @param int $id
   *   A satellite entity id.
   *
   * @return string
   *   Json response.
   */
  public function checkDeploy($id) {
    $sat = SatelliteEntity::load($id);
    $result = [
      'status' => 0,
    ];

    $ping_client = new Client([
      'base_uri' => 'http://' . $sat->get('host'),
      ['redirect.disable' => TRUE],
    ]);

    try {
      $response = $ping_client->get('/webfactory_slave/site?_format=hal_json', array('allow_redirects' => FALSE));

      $status = $response->getStatusCode();

      if ($status == 200) {
        $result['status'] = 1;
        $result['host'] = 'http://' . $sat->get('host');

        $sat->markAsDeployed();
        $sat->save();
      }
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $status = $response->getStatusCode();

      if ($status == 403) {
        $result['error_message'] = $this->t('There was a problem checking for the satellite @satellite. Ensure that anonymous users have the permission @permission on the satellite.', array('@satellite' => $sat->id(), '@permission' => 'restful get webfactory_slave:site'));
      }
      elseif ($status == 404) {
        $result['error_message'] = $this->t("There was a problem checking for the satellite @satellite: 'Page not found'. Deployment may not be finished yet.", array('@satellite' => $sat->id(), '@permission' => 'restful get webfactory_slave:site'));
      }
      else {
        $result['error_message'] = $e->getMessage();
      }
    }

    return new Response(json_encode($result));
  }

}
