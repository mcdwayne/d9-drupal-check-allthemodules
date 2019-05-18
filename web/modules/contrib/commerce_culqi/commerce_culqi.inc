<?php
//*sk_live_PEC447rdAPr6fs33
/**
 * Page response.
 */
function commerce_culqi_response($order, $token) {

  //dpm(commerce_culqi_get_md5($order->order_id, $token, 'RESPONSE'));
  if(commerce_culqi_get_md5($order->order_id, $token, 'RESPONSE') == arg(4)) {
      $payload = commerce_culqi_load_transaction($order->order_id);
      //dpm($payload);
      $amount = number_format(commerce_currency_amount_to_decimal($payload['amount'], $payload['currency_code']), 2, '.', '');
      drupal_set_title($payload['outcome']->user_message);

      $rows = array(
       // array(t('AuthorizationResult'), $payload['authorizationResult']),
       // array(t('AuthorizationCode'), $payload['authorizationCode']),
        array(t('Message'), $payload['outcome']->user_message),
        array(t('Bin'), $payload['source']->card_number),
        array(t('Brand'), $payload['source']->iin->card_brand),
    //    array(t('PaymentReferenceCode'), $payload['paymentReferenceCode']),
      //  array(t('Reserved1'), $payload['reserved1']),
        array(t('Número de Operacion'), $payload['transfer_id']),
        array(t('Description'), $payload['description']),
        array(t('Amount'), $payload['amount']/100),
        array(t('Currency code'), $payload['currency_code']),
      );
  }
  else {
      return drupal_not_found();
  }
  $content['table'] = array(
    '#theme' => 'table',
    '#rows' => $rows,
  );
  $arguments = array(
    'message' => $payload['outcome']->user_message,
    'status' => 'status',
    'textbtn' => t('Return')
  );
  $content = drupal_render($content);
  $form_temp = drupal_get_form('commerce_culqi_cancel_form', $arguments);
  $form = drupal_render($form_temp);
  $content = $content.$form;
  return $content;
}

function commerce_culqi_response11() {
    header('Content-Type: application/json');
    $path = drupal_get_path('module', 'commerce_culqi');
    //require_once $path.'/lib/culqi.php';

    include_once  dirname(__FILE__).'/vendor/autoload.php';
    // Cargar Culqi-PHP de forma manual
  //  include_once  dirname(__FILE__).'/vendor/culqi/culqi-php/lib/culqi.php';


   // var_dump(dirname(__FILE__).'/vendor/culqi/culqi-php/lib/culqi.php');
    // use Culqi\Culqi;

    // Configurar tu API Key y autenticación
    //$SECRET_API_KEY = 'sk_test_UTCQSGcXW8bCyU59';
    $SECRET_API_KEY = 'sk_test_YeXu1lQovErq8Mjj';
    $culqi = new Culqi\Culqi(array('api_key' => $SECRET_API_KEY));

    try {
      // Creando Cargo a una tarjeta
      $charge = $culqi->Charges->create(
          array(
              "amount" => 1000,
              "capture" => true,
              "currency_code" => "PEN",
              "description" => "Venta de prueba1",
              "email" => "test77788@culqi.com",
              "installments" => (int)$_POST["installments"],
              "source_id" => $_POST["token"]
          )
      );
      // Response
      echo json_encode($charge);

    } catch (Exception $e) {

      echo json_encode($e->getMessage());

    }
}

/**
 * Page confirmation.
 */
function commerce_culqi_confirmation($order) {
  header('Content-Type: application/json');
  $path = drupal_get_path('module', 'commerce_culqi');

  include_once dirname(__FILE__).'/vendor/autoload.php';

  //dpm($order);
  //return "joo";


  if($_POST) {
    //dpm($_POST);
    if($_POST['token'] == 'cancel') {
      commerce_order_status_update($order, 'canceled');
      $result['message'] = 'ok';
      //drupal_goto('/');
      echo json_encode($result);
      exit();
    }
    else {
        $payment = commerce_payment_method_instance_load($order->data['payment_method']);
        $settings = $payment['settings'];

        $description = t($settings['py_description'], array(
        '@order_id' => $order->order_id,
        ));

        $SECRET_API_KEY = $settings['py_api_key'];
        $culqi = new Culqi\Culqi(array('api_key' => $SECRET_API_KEY));

        $data = array(
              "amount" => $order->commerce_order_total['und'][0]['amount'],
              "capture" => true,
              "currency_code" => $order->commerce_order_total['und'][0]['currency_code'],
              "description" => $description,
              "email" => $order->mail,
              "installments" => (int)$_POST["installments"],
              "source_id" => $_POST["token"]
          );

          try {
            // Creando Cargo a una tarjeta
            $charge = $culqi->Charges->create($data);
            // Response
           // $param = $charge;
            $param = (array)$charge;
            //var_dump($param);
            if(validate_charge($param)) {
              commerce_culqi_send_pay($order, $param);
            }
            // else {
            //   commerce_culqi_send_pay($order, $param, 'cancel');
            // }
            echo json_encode($charge);
            exit();

          } catch (Exception $e) {

            echo json_encode($e->getMessage());

          }
    }
  } else {

     $result['message'] = 'cancel';
    //  drupal_goto('/');
    echo json_encode($result);
    //return drupal_not_found();

  }

}

function validate_charge($param) {
  if($param['outcome']->code) {
    if($param['outcome']->code == RESPONSE_CULQI_SUCCESS) {
      return true;
    }
  }
  return false;
}

/**
 * Page confirmation.
 */
function commerce_culqi_send_pay($order, $param) {
    $payments = array();
    $payments = commerce_payment_transaction_load_multiple(array(), array('order_id' => $order->order_id));
    if(count($payments) <= 0) {
         //$param = commerce_alignetsoles_json_param($order);
        if($param['outcome']->code) {
          $transaction = commerce_culqi_save_transation($param, $order);
          $payment = commerce_payment_method_instance_load($order->data['payment_method']);
          $default_status = array(
            COMMERCE_PAYMENT_STATUS_SUCCESS => 'pending',
            COMMERCE_PAYMENT_STATUS_FAILURE => 'canceled',
          );
          if($transaction) {
              if (!isset($payment['settings']['py_assign_status']) || $payment['settings']['py_assign_status'] == 'M') {
                 $status = $default_status[$transaction->status];
                 if (isset($payment['settings']['py_status_' . $transaction->status])) {
                    $status = $payment['settings']['py_status_' . $transaction->status];
                 }
                 commerce_order_status_update($order, $status);
                 if ($transaction->status == COMMERCE_PAYMENT_STATUS_SUCCESS) {
                    commerce_checkout_complete($order);
                    $result['message'] = 'success';
                 }
                 else {
                     //commerce_order_status_update($order, 'canceled');
                     $result['message'] = 'failed';
                 }
              }
              elseif ($transaction->status == COMMERCE_PAYMENT_STATUS_SUCCESS) {
                 commerce_payment_redirect_pane_next_page($order);
                 $result['message'] = 'success';
              }
              else {
                 commerce_order_status_update($order, 'canceled');
                 $result['message'] = 'failed';
              }
            }
            module_invoke_all('commerce_alignetsoles_confirmation', $order, $transaction);
        }
    }
}

/**
 * Save transaction.
 */
function commerce_culqi_save_transation($param, $order) {
  $amount = $param['amount'];
 // $amount = commerce_currency_decimal_to_amount($exchange_money, $param['currency_code']);
  $payment = commerce_payment_method_instance_load($order->data['payment_method']);
  $param['codigo_respuesta'] = $param['outcome']->code;
  $param['instance_id'] = $payment['instance_id'];
  //$amount = number_format(commerce_currency_amount_to_decimal($param['amount'], $param['currency_code']), 2, '.', '');
   if($param['codigo_respuesta'] == RESPONSE_CULQI_SUCCESS) {
        $result = db_insert('commerce_culqi')
            ->fields(array(
              'order_id' => $order->order_id,
              'created' => REQUEST_TIME,
              'reference_culqi' => isset($param['id']) ? $param['id'] : 'NN',
              'state_transaction' => isset($param['outcome']->code) ? $param['outcome']->code : 'NN',
              'card_brand' => isset($param['source']->iin->card_brand) ? $param['source']->iin->card_brand : 'NN',//status order
              'value' => number_format(commerce_currency_amount_to_decimal($amount, $param['currency_code']), 2, '.', ''),
              'response' => serialize($param),
            ))
            ->execute();
   }

  $transaction = commerce_payment_transaction_new('culqi_payment', $order->order_id);
  $transaction->payload[REQUEST_TIME] = $param;
  $transaction->remote_id = $param['id'];
  $transaction->remote_status = $param['codigo_respuesta'];

  switch ($param['codigo_respuesta']) {
    case RESPONSE_CULQI_SUCCESS:
      $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
      break;
    default:
      $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
  }

  $transaction->instance_id = $param['instance_id'];
  $transaction->amount = $amount;
  $transaction->currency_code = $param['currency_code'];
  $transaction->message = $param['outcome']->merchant_message;
  if($param['codigo_respuesta'] != RESPONSE_CULQI_SUCCESS) {
      return NULL;
   }
  commerce_payment_transaction_save($transaction);

  return $transaction;
}

/**
 * Generate url response and confirmation.
 */
function commerce_culqi_get_url($order_id, $request = NULL) {
  $token = uniqid();
  $url_confirmation = COMMERCE_CULQI_URL_CONFIRMATION . '/' . $order_id . '/' . $token . '/' . commerce_culqi_get_md5($order_id, $token);
  if($request)
    $token = $request;
  $url_response = COMMERCE_CULQI_URL_RESPONSE . '/' . $order_id . '/' . $token . '/' . commerce_culqi_get_md5($order_id, $token, 'RESPONSE');

  return array(
    'response' => url($url_response, array(
      'absolute' => FALSE,
    )),
    'confirmation' => url($url_confirmation, array(
      'absolute' => FALSE,
    )),
  );
}

/**
 * Generate number reference unique.
 */
function commerce_culqi_get_reference($alias, $order_id) {
  return $alias . $order_id;
}

/**
 * Digital signature of the transaction.
 */
function commerce_culqi_get_firm($settings, $state_transation = NULL) {
  $params = array(
    $settings['py_api_key'],
    $settings['py_commerce_code'],
    $settings['sale_reference'],
    $settings['amount'],
    $settings['currency_code'],
  );
  if ($state_transation) {
    $params[] = $state_transation;
  }
  return md5(implode('~', $params));
}

/**
 * Encrypted key.
 */
function commerce_culqi_get_md5($order_id, $token, $type = 'CONFIRMATION') {
  return md5($order_id . '~' . strtoupper($type) . '~' . $token);
}

function commerce_culqi_get_md5_request($order_id, $status = 'failed') {
  return md5($order_id . '~' . strtoupper($status));
}
/**
 * Return transaction data
 */
function commerce_culqi_load_transaction($order_id) {
   $payments = commerce_payment_transaction_load_multiple(array(), array('order_id' => $order_id));
   $payment = !empty($payments) ? array_shift($payments) : NULL;
   $id_pay = $payment->created;
   $payload = $payment->payload[$id_pay];
   return $payload;
}

/**
 *
 */
function validate_culqi_data($data) {
  $validate = TRUE;
  foreach ($data as $key => $value) {
    size_field_culqi($key, $value, $validate);
    if(empty($value) && $key != 'iduser') {
      //dpm($data);
      if($key == 'City' && $data['country'] == 'PE') {
         drupal_set_message(t('Please fill the field department and district'), 'error');
      }
      else {
        drupal_set_message(t($key).' '.t('field  is required'), 'error');
      }
      $validate = FALSE;
    }
  }
  return $validate;
}

/**
 * Validate fields 
 */
function size_field_culqi($key, $field, &$validate) {
  $limit = array(
    'Name' => array(
      'limit' => 2,
      'message' => t('Ingrese Nombres válidos'),
     ),
    'Lastname' => array(
      'limit' => 2,
      'message' => t('Ingrese Apellidos válidos'),
     ),
    'Phone' => array(
      'limit' => 5,
      'message' => t('Ingrese un número de teléfono válido'),
     ),
    'Address' => array(
      'limit' => 5,
      'message' => t('Ingrese una dirección válida'),
     ),
  );
  if(isset($field) && isset($limit[$key]['limit']) && strlen($field) < $limit[$key]['limit']) {
      drupal_set_message($limit[$key]['message'], 'warning');
      $validate = FALSE;
  }
  if($key == 'Name' || $key == 'Lastname') {
    if(strlen(preg_replace("/[^a-zA-Z]/ ", "", $field)) < $limit[$key]['limit']) {
        drupal_set_message($limit[$key]['message'], 'warning');
        $validate = FALSE;
    }
  }
}