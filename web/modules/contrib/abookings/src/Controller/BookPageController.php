<?php

namespace Drupal\abookings\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class BookPageController.
 *
 * @package Drupal\abookings\Controller
 */
class BookPageController extends ControllerBase {

  /**
   * Provides markup for the Book page.
   *
   * @return array
   *   A renderable array of the page content.
   */
  public function viewPage() {
    $content = array();

    $content['#attached']['library'][] = 'abookings/abookings';
    $content['#attached']['library'][] = 'abookings/fullCalendar';

    $content['booking_info_container'] = [
      '#prefix' => '<div id="booking_info" class="half-right">',
      '#suffix' => '</div>',
    ];
    $content['booking_info_container']['booking_details'] = [
      '#prefix' => '<table>',
      '#suffix' => '</table>',
      'booking_cost' => [
        '#markup' => '<tr><td class="field_base_cost">'
          . '<strong>Base cost: </strong>'
          . '<span class="value"></span>'
          . '</td></tr>',
      ],
      'promo_discount' => [
        '#markup' => '<tr><td class="field_promo_discount">'
          . '<strong>Promotion discount: </strong>'
          . '<span class="value"></span>'
          . '</td></tr>',
      ],
      'addons' => [
        '#markup' => '<tr><td class="field_addons">'
          . '<strong>Addons: </strong>'
          . '<span class="value"></span>'
          . '</td></tr>',
      ],
      'total_cost' => [
        '#markup' => '<tr class="total"><td class="field_total_cost">'
          . '<strong>Total cost: </strong>'
          . '<span class="value"></span>'
          . '</td></tr>',
      ],
    ];

    $content['booking_info_container']['booking_info'] = [
      '#prefix' => '<div>',
      '#suffix' => '</div>',
      'checkin_date' => [
        '#markup' => '<p class="field_checkin_date">'
          . '<strong>Check-in: </strong><span class="value"></span></p>',
      ],
      'checkout_date' => [
        '#markup' => '<p class="field_checkout_date">'
          . '<strong>Check-out: </strong><span class="value"></span></p>',
      ],
      'nights' => [
        '#markup' => '<p class="field_num_nights">'
          . '<strong>Nights: </strong><span class="value"></span></p>',
      ],
      'message_container' => [
        '#markup' => '<div class="message_container"></div>',
      ],
      'loader' => [
        '#markup' => '<div class="loader hidden"></div>',
      ]
    ];


    $content['booking_form'] = [
      '#prefix' => '<div class="form half-left">',
      '#suffix' => '</div>',
      'form' => get_booking_node_form(),
    ];

    attach_booking_data($content);
    // kint($content, '$content');

    return $content;
  }

}


function get_booking_node_form() {
  $new_booking_values = array(
    'type' => 'booking',
    'title' => 'booking',
  );

  $new_booking = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->create($new_booking_values);

  $form_object = \Drupal::entityTypeManager()
    ->getFormObject('node', 'default')
    ->setEntity($new_booking);
  // kint($form_object, '$form_object');
  $booking_node_form = \Drupal::formBuilder()->getForm($form_object, '/book');
  // kint(array_keys($booking_node_form), '$booking_node_form');
  return $booking_node_form;
}
