<?php

namespace Drupal\bookings_backend\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\node\Entity\Node;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Entity\Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The Bookings back-end Booking controller.
 */
class BookingController extends ControllerBase {

  /**
   * Responds to booking POST requests and saves the new booking.
   *
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function post(Request $request) {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json');

    $response_data = [];
    $response_data['is_success'] = FALSE;

    $request_body = $request->getContent();
    // kint($request_body, '$request_body');

    $node_unvalidated = json_decode($request_body);
    // kint($node_unvalidated, '$node_unvalidated');

    if (! $node_unvalidated) {
      $response_data['error_message'] = 'Values could not be processed.';
      $response->setStatusCode(400);
      $response->setContent(json_encode($response_data));
      return $response;
    }

    $booking_values = $node_unvalidated;

    $booking_total = floatval($booking_values->field_base_cost);
    // kint($booking_total, '$booking_total');

    $promo_code_provided = $booking_values->field_promo_code_provided;
    $checkin_date = $booking_values->field_checkin_date;
    $nights = $booking_values->field_num_nights;

    $booking = create_booking_fm_submission($booking_values, $promo_code_provided, $booking_total);
    // kint($booking, '$booking unvalidated');

    // Validate the received data before saving.
    $violations = $booking->validate();
    if ($violations->count() > 0) {
      $violations_json = json_encode($violations);
      $error_msg = 'Node values are not valid. Violations: '
        . $violations->__toString();
      $response_data['error_message'] = $error_msg;
      $response->setStatusCode(400);
      $response->setContent(json_encode($response_data));
      return $response;
    }

    $result;
    try {
      $result = $booking->save();
    }
    catch (EntityStorageException $e) {
      // throw new HttpException(500, 'Booking could not be saved', $e);
      $response_data['error_message'] = 'Booking could not be saved.';
      $response->setStatusCode(500);
      $response->setContent(json_encode($response_data));
      return $response;
    }



    // Validate promotion

    if ($promo_code_provided) {
      try {
        $promo = get_promo($promo_code_provided, $checkin_date, $nights);
      }
      catch (BadRequestHttpException $e) {
        // kint($promo, '$promo');
        $response_data['error_message'] = "Promo code " . $promo_code_provided . " error: " . $e->getMessage();
        $response->setStatusCode(400);
        $response->setContent(json_encode($response_data));
        return $response;
      }

      // Apply promotion

      // OLD submission_apply_promotion($promo, $form_state);

      if ($promo) {
        $discount_percentage = floatval(
          $promo->get('field_promo_discount')->getValue()[0]['value']);
        // kint($discount_percentage, '$discount_percentage');
        $discount = $booking_total * ($discount_percentage / 100) . "";
        // kint($discount, '$discount');

        \save_promo_line_item($promo_code_provided, $discount, $booking->id(), $checkin_date);
      }
      else {
        $response_data['error_message'] = 'Promo code provided is not valid';
        $response->setStatusCode(400);
        $response->setContent(json_encode($response_data));
      }
    }



    \Drupal::logger('abookings')->notice('Created entity %type with ID %id.',
      array('%type' => $booking->getEntityTypeId(), '%id' => $booking->id()));

    $bookable_nid = $booking->get('field_bookable_unit')->getValue()[0]['target_id'];
    $bookable = node_load($bookable_nid);

    send_provis_booking_email($booking, $bookable);

    // Prepare the response

    $url = $booking->urlInfo('canonical', ['absolute' => TRUE])->toString(TRUE);
    // $response = new ModifiedResourceResponse($booking, 201, [
    //   'Location' => $url->getGeneratedUrl()
    // ]);
    $response_data['url'] = $url->getGeneratedUrl();

    $response_data['nid'] = $booking->id();

    $response_data['is_success'] = TRUE;
    $response->setContent(json_encode($response_data));
    return $response;

  }

}
