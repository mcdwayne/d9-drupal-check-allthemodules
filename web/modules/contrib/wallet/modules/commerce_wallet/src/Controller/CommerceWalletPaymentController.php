<?php

namespace Drupal\commerce_wallet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\wallet\WalletApi;
use Drupal\wallet\Entity\WalletTransaction;
use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\Core\Routing\TrustedRedirectResponse;
/**
 * Class CommerceWalletPaymentController.
 *
 * @package Drupal\commerce_wallet\Controller
 */
class CommerceWalletPaymentController extends ControllerBase {

  public function deduct_payment(Request $request) {
  	$return = array();
  	$return['STATUS'] = FALSE;
  	$return['MSG']    = '';
    $order_id = $request->get('ORDER_ID');
    $user_id  = $request->get('CUST_ID');
    $amount   = $request->get('TXN_AMOUNT');
    $callback_url = $request->get('CALLBACK_URL');
    $cancel_url   = $request->get('CANCEL_URL');
    $wallet_api = new WalletApi();
    $deduct_amount = ($amount * -1);
    $user_balance = $wallet_api->getWalletInfoPerUser($user_id);
    if($user_balance['Default'] >= $amount) {
			$transaction = WalletTransaction::create([
					'title' => 'Deduct Balance for order id'.$order_id.' from user id '.$user_id,
					'amount' => $deduct_amount,
					'category' => 1,
					'user_id' => $user_id,
					'status'  => 'Approved'
			]);
			$transaction->save();
			$return['STATUS'] = TRUE;
			return new TrustedRedirectResponse($callback_url);
    }
    else {
			return new TrustedRedirectResponse($cancel_url);
    }
		 // $redirect_url = Url::fromUri($callback_url, ['absolute' => TRUE])->toString();
  }

}
