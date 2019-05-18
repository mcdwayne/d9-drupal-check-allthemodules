<?php

namespace Drupal\bookings_backend\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\node\Entity\Node;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Entity\Exception;
// use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
// use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The Bookings back-end Booking controller.
 */
class PromosController extends ControllerBase {

  /**
   * Responds to booking POST requests and saves the new booking.
   *
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function validatePromo(Request $request) {    
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json');

    $response_data = [];
    $response_data['is_valid'] = FALSE;

    // $request = \Drupal::requestStack()->getCurrentRequest();
    // $request_body = $request->getContent();
    // kint($request_body, '$request_body 2');

    $request_body = $request->getContent();
    // kint($request_body, '$request_body');

    $promo_request = json_decode($request_body);
    // kint($node_unvalidated, '$node_unvalidated');

    $promo_code_provided  = $promo_request->field_promo_code_provided;
    $checkin_date         = $promo_request->field_checkin_date;
    $nights               = $promo_request->field_num_nights;

    $promo;

    try {        
      $promo = get_promo($promo_code_provided, $checkin_date, $nights);
    }
    catch (BadRequestHttpException $e) {
      // kint($promo, '$promo');
      $message = "Promo code " . $code_provided . " error: " . $e->getMessage();
      $response_data['error_message'] = $message;
      // $response->setStatusCode(400);
      $response->setContent(json_encode($response_data));
      return $response;
    }
    

    if (! $promo) {
      $message = t("Promo code `%promo_code_provided` is not a valid promo code.", ['%promo_code_provided' => $promo_code_provided]);
      // throw new Exception($message);
      $response_data['error_message'] = $message;
      $response->setContent(json_encode($response_data));
      return $response;
    }

    // Prepare the response

    $response_data['is_valid'] = TRUE;
    $response_data['discount'] =
      floatval($promo->get('field_promo_discount')->getValue()[0]['value']);
    // kint($response_data['discount'], '$response_data['discount']');
    $response_data['nid'] = $promo->id();

    $response->setContent(json_encode($response_data));
    return $response;

  }

}
