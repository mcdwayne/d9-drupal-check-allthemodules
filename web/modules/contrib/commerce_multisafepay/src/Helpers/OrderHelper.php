<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

namespace Drupal\commerce_multisafepay\Helpers;

use Drupal\commerce_multisafepay\API\Client;
use Drupal\commerce_order\Adjustment;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\FlatRatePerItem;

class OrderHelper
{
    use StringTranslationTrait;

    protected $mspApiHelper;
    protected $logStorage;

    const MSP_COMPLETED = "completed";
    const MSP_INIT = "initialized";
    const MSP_UNCLEARED = "uncleared";
    const MSP_VOID = "void";
    const MSP_DECLINED = "declined";
    const MSP_EXPIRED = "expired";
    const MSP_CANCELLED = "cancelled";
    const MSP_REFUNDED = "refunded";
    const MSP_PARTIAL_REFUNDED = "partial_refunded";

    //Drupal Commerce Only
    const PARTIALLY_REFUNDED = "partially_refunded";
    const AUTHORIZATION = "authorization";
    const AUTHORIZATION_EXPIRED = "authorization_expired";
    const AUTHORIZATION_VOIDED = "authorization_voided";
    const NEW = "new";


    /**
     * Get and return the current status
     *
     * @param $state
     * @return string
     */
    public static function getPaymentState($state){
        switch ($state){
            case self::MSP_COMPLETED:
                    return self::MSP_COMPLETED;
                break;
            case self::MSP_INIT:
                    return self::NEW;
                break;
            case self::MSP_UNCLEARED:
                    return self::AUTHORIZATION;
                break;
            case self::MSP_VOID:
                    return self::AUTHORIZATION_VOIDED;
                break;
            case self::MSP_DECLINED:
                    return self::AUTHORIZATION_VOIDED;
                break;
            case self::MSP_EXPIRED:
                    return self::AUTHORIZATION_EXPIRED;
                break;
            case self::MSP_CANCELLED:
                    return self::AUTHORIZATION_VOIDED;
                break;
            case self::MSP_REFUNDED:
                    return self::MSP_REFUNDED;
                break;
            case self::MSP_PARTIAL_REFUNDED:
                    return self::PARTIALLY_REFUNDED;
                break;
            default:
                return null;
        }
    }
    
    //set discount so we can use it anywhere
    public $discount = ['type' => 'none', 'percentage' => 0, 'amount' => 0];

    public function __construct()
    {
        $this->mspApiHelper = new ApiHelper();
        $this->logStorage = \Drupal::entityTypeManager()->getStorage('commerce_log');
    }

    /**
     * Create the whole order data array
     *
     * @param $form
     * @param $payment
     * @param array $gatewayInfo
     * @return array
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function createOrderData($form, $payment, $gatewayInfo = [])
    {
        //Get URLS
        $redirectUrl = $form['#return_url'];
        $notification = $this->getNotifyUrl($payment)->toString();
        $cancelUrl = $form['#cancel_url'];

        $order = $payment->getOrder();
        $orderId = $payment->getOrderId();
        $currency = $payment->getAmount()->getCurrencyCode();
        $amount = $payment->getAmount()->getNumber();
        //convert to cents
        $amount = $amount * 100;

        //Redirect type and the gateway code
        $gatewayCode = $this->getGatewayHelperOptions($order)['code'];

        //Check if gateway is ideal and has no issuer id, if so: make redirect
        if($gatewayCode === 'IDEAL' && $gatewayInfo['issuer_id'] === 'none'){
            $type = 'redirect';
        }else {
            $type = $this->getGatewayHelperOptions($order)['type'];
        }

        //Set the checkout and shopping cart data
        $checkoutData = null;
        $shoppingCartData = null;
        $items = $this->getItemsData($order);

        //Check if the gateway uses the checkout & shopping data
        if(GatewayHelper::isShoppingCartAllowed($payment->getPaymentGateway()->getPluginId())){
            $checkoutData = $this->getCheckoutData($order);
            $shoppingCartData = $this->getShoppingCartData($order);
        }

        $drupalVersion = \Drupal::VERSION;
        $commerceVersion = system_get_info('module', 'commerce')['version'];
        $pluginVersion = system_get_info('module', 'commerce_multisafepay')['version'];

        $orderData = array(
            "type"             => $type,
            "gateway"          => $gatewayCode,
            "order_id"         => $orderId,
            "currency"         => $currency,
            "amount"           => $amount,
            "items"            => $items,
            "description"      => $orderId,
            "seconds_active"   => \Drupal::config(
                'commerce_multisafepay.settings'
            )->getRawData()['seconds_active'],
            "manual"           => "false",
            "payment_options"  => array(
                "notification_url" => $notification,
                "redirect_url"     => $redirectUrl,
                "cancel_url"       => $cancelUrl,
                "close_window"     => "true"
            ),
            "customer"         => $this->getCustomerData($order),
            "delivery"         => $this->getShippingData($order),
            "shopping_cart"    => $shoppingCartData,
            "checkout_options" => $checkoutData,
            "gateway_info"     => $gatewayInfo,
            "plugin"           => array(
                "shop"           => "Drupal: {$drupalVersion}, Commerce: {$commerceVersion}",
                "shop_version"   => "Drupal: {$drupalVersion}, Commerce: {$commerceVersion}",
                "plugin_version" => " - Plugin: {$pluginVersion}",
                "partner"        => "MultiSafepay",
            ),
        );

        return $orderData;
    }

    /**
     * Get MSP gateway options form the order
     *
     * @param $order
     * @return mixed
     */
    public function getGatewayHelperOptions($order){

        //Get the gateway id
        $gatewayId = $order->get('payment_gateway')->first(
        )->entity->getPluginId();

        //Get the msp gateway options
        $gatewayOptions = GatewayHelper::MSP_GATEWAYS['gateways'][$gatewayId];

        //return the options
        return $gatewayOptions;
    }

    /**
     * Get the notification URL
     *
     * @param $payment
     * @return Url
     */
    public function getNotifyUrl($payment) {
        return Url::fromRoute('commerce_payment.notify', [
            'commerce_payment_gateway' => $payment->getPaymentGatewayId(),
        ], [
            'absolute' => true,
        ]);
    }

    /**
     * Get the client it's forwarded IP
     *
     * @return mixed|null
     */
    public static function getForwardedIP()
    {
        //Check if there is a Forwarded IP
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //Validate the IP if there is one
            return self::validateIP($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            //If there is none return no IP
            return null;
        }
    }

    /**
     * Validate if the IP is correct
     *
     * @param $ip
     * @return mixed|null
     */
    private static function validateIP($ip)
    {
        $ipList = explode(',', $ip);
        $ip = trim(reset($ipList));

        //Validate IP address
        $isValid = filter_var($ip, FILTER_VALIDATE_IP);

        //Check if the IP is valid
        if ($isValid) {
            return $isValid;
        } else {
            return null;
        }
    }

    /**
     * Gathers the checkout data
     *
     * @param $order
     * @return array
     */
    public function getCheckoutData($order){
        //Get the order Items
        $orderItems = $order->getItems();
        //get one order item
        $orderItem = $orderItems[0];

        //Check if the item has adjustments
        if(!$orderItem->getAdjustments()){
            //if True -> Return array
            return array(
                "tax_tables" =>
                    array(
                        "default" =>
                            array(
                                "shipping_taxed" => null,
                                "rate" => 0
                            ),
                        'alternate' =>
                            array(
                                [
                                    'standalone' => false,
                                    'name'       => 'shipping',
                                    'rules'      => array(
                                        [
                                            'rate' => 0
                                        ]
                                    )
                                ]
                            )
                    ),
            );
        };

        //make empty values
        $checkoutData = ["tax_tables" => ['default' => ["shipping_taxed" => null,"rate" => 0], "alternate" => []]];

        //TODO: WHEN DRUPAL FIXES THE FIXED DISCOUNT BUG CHANGE FLATRATE url(https://www.drupal.org/project/commerce/issues/2980713)
        //Check if there is a discount
        if($this->getAdjustment($orderItem, 'promotion')){
            $adjustment = $this->getAdjustment($orderItem, 'promotion');
            //check if the discount code uses percentage or flat rate
            $this->discount = $adjustment->getPercentage() ?
                $this->discount = ['type' => 'percentage', 'percentage' => $this->discount['percentage'] + $adjustment->getPercentage(), 'amount' => $this->discount['amount']] :
                $this->discount = ['type' => 'flat', 'amount' => $this->discount['amount'] + abs($adjustment->getAmount()->getNumber()) , 'percentage' => $this->discount['percentage']];

            //set Discount table
            $discountTable = ["standalone" => false, "name" => 'promotion', "rules" => [["rate" => 0]]];

            //Make the taxtable for promotional items
            array_push($checkoutData['tax_tables']['alternate'], $discountTable);
        }
        //Check if there is Taxes
        if($this->getAdjustment($orderItem, 'tax')){
            $adjustment = $this->getAdjustment($orderItem, 'tax');
            //get VAT from first item
            $getVAT = $adjustment->getPercentage();

            //set the VAT of the item (default)
            $checkoutData['tax_tables']['default'] = ["shipping_taxed" => null, "rate" => $getVAT];

            //set Rate
            if(isset($checkoutData['tax_tables']['alternate'][0])){
                $checkoutData['tax_tables']['alternate'][0]["rules"][0]["rate"] = $getVAT;
            }
        }

        //Push a BTW0 alternate to the tax_tables
        array_push($checkoutData['tax_tables']['alternate'],[
            'standalone' => false,
            'name'       => 'BTW0',
            'rules'      => array(
                [
                    'rate' => 0,
                ]
            )
        ]);

        //return the VAT data to use it in customer data
        return $checkoutData;
    }

    /**
     * Gathers the shopping cart data
     *
     * @param OrderInterface $order
     *
     * @return array
     */
    public function getShoppingCartData(OrderInterface $order){
        //Set $totalOrderedProducts for using Shipping Flat Rate Per Item
        $totalOrderedProducts = 0;

        //Get the order Items
        $orderItems = $order->getItems();
        //Create the array where we will put items in
        $shoppingCartData = array(
            "items" => array(
            ),
        );

        $discountRow = [
            "name" => $this->t('Discount'),
            "description" => '',
            "quantity" => 1,
            "unit_price" => 0,
            "merchant_item_id" => 'msp-discount',
            "tax_table_selector" => "promotion",
        ];

        //Go through all items and put them in a array for the API
        foreach ($orderItems as $key => $orderItem) {

            //Add quantity total to $totalOrderedProducts
            $totalOrderedProducts += $orderItem->getQuantity();

            $taxAdjustment = $this->getAdjustment($orderItem, 'tax');

            //So we can get values that doesnt have methods
            $product = $orderItem->getPurchasedEntity();

            //Check if weight is enabled
            if($product->hasField('weight') && !empty($product->get('weight')->getValue())){
                $productWeight = $product->get('weight')->getValue()[0];
            }else{
                $productWeight = null;
            }

            //check if price is incl. or excl. tax
            $taxIncluded = false;
            //check if tax adjustment exist
            if($taxAdjustment instanceof Adjustment){
                $taxIncluded = $taxAdjustment->isIncluded();
            }

            $originalProductPrice = $product->getPrice()->getNumber();
            //Check if tax is included, if not: get price of product
            if ($taxIncluded) {
                //get price excl. tax
                $discountRow['tax_table_selector'] = "BTW0";
                $productPrice = $product->getPrice()->getNumber()  / (1 + $taxAdjustment->getPercentage());
            } else {
                //get value of the item and convert it to cents
                $productPrice = $product->getPrice()->getNumber();
            }
            //Get Quantity
            $productQuantity = (string)floatval($orderItem->getQuantity());

            //Make an array of the item and fill it with the data
            $item = array(
                "name" => $product->getTitle(),
                "description" => '',
                "unit_price" => $productPrice,
                "quantity" => $productQuantity,
                "merchant_item_id" => $product->getProductId(),
                "tax_table_selector" => "default",
                "weight" => array(
                    "unit" => $productWeight['unit'],
                    "value" => $productWeight['number']
                ),
            );

            //push the item to the items array
            array_push($shoppingCartData['items'], $item);

            //check if there is a discount. if so Take it off
            if($this->discount['amount'] > 0.00 || $this->discount['percentage'] > 0.00 ) {
                //Check if its a percentage or Flat discount coupon and set their Algorithms
                if($this->discount['type'] === "percentage"){
                    $discountRow["unit_price"] +=  -(($originalProductPrice * $productQuantity) * $this->discount['percentage'] - $this->discount['amount']);
                }else{
                    $discountRow["unit_price"] = -$this->discount['amount'];
                }
            }
        }

        //If there is a discount, push all discounts to the shopping cart data
        if($this->discount['amount'] > 0.00 || $this->discount['percentage'] > 0.00 ) {
            array_push($shoppingCartData['items'], $discountRow);
        }

        //Make Shipping record for shopping cart
        $shipmentCartData = $this->getShippingCartData($order, $totalOrderedProducts);

        //If no shipment cart data, exclude shipping from cart array
        if(!empty($shipmentCartData)){
            $shoppingCartData['items'][] = $shipmentCartData;
        }

        //return the items array to use it in customer data
        return $shoppingCartData;
    }
    /**
     * Get address and house number
     *
     * @param $streetAddress
     *
     * @return mixed
     */
    public function parseCustomerAddress($streetAddress)
    {
        list($address, $apartment) = $this->parseAddress($streetAddress);
        $customer['address'] = $address;
        $customer['housenumber'] = $apartment;
        return $customer;
    }

    /**
     * Split and process address to street and house number
     *
     * @param $streetAddress
     *
     * @return array
     */
    public function parseAddress($streetAddress)
    {
        $address = $streetAddress;
        $apartment = "";

        //Get String length
        $offset = strlen($streetAddress);

        //Loop until $offset returns true
        while (($offset = $this->splitAddress($streetAddress, ' ', $offset))
            !== false) {

            //Check if the length of the street address is lower than the offset
            if ($offset < strlen($streetAddress) - 1
                && is_numeric(
                    $streetAddress[$offset + 1]
                )
            ) {
                //if True, Trim the address and Apartment
                $address = trim(substr($streetAddress, 0, $offset));
                $apartment = trim(substr($streetAddress, $offset + 1));
                break;
            }
        }

        //check if apartment is empty and street address is higher than 0
        if (empty($apartment) && strlen($streetAddress) > 0
            && is_numeric(
                $streetAddress[0]
            )
        ) {
            //Find the position of the first occurrence of a substring in street address
            $pos = strpos($streetAddress, ' ');

            //check if strpos doesn't return false
            if ($pos !== false) {

                //if True, Trim the address and Apartment
                $apartment = trim(
                    substr($streetAddress, 0, $pos), ", \t\n\r\0\x0B"
                );
                $address = trim(substr($streetAddress, $pos + 1));
            }
        }

        //Return the address and apartment back.
        return array($address, $apartment);
    }

    /**
     * Helps split the address to street and house number to decide where to
     * split the string
     *
     * @param $streetAddress
     * @param $search
     * @param null $offset
     * @return bool|int
     */
    public function splitAddress($streetAddress, $search, $offset = null)
    {
        //Get the size of the Street Address
        $size = strlen($streetAddress);

        //Check if the offset is null if so make offset the size as street length
        if (is_null($offset)) {
            $offset = $size;
        }

        //Search for the chosen string
        $position = strpos(strrev($streetAddress), strrev($search), $size - $offset);

        //Check if there was nothing found in the string
        if ($position === false) {
            return false;
        }

        //Return the splitted address back
        return $size - $position - strlen($search);
    }

    /**
     * Check if the order has shipments
     *
     * @param OrderInterface $order
     *
     * @return bool
     */
    public function orderHasShipments(OrderInterface $order) {
        return $order->hasField('shipments') && !$order->get('shipments')->isEmpty();
    }

    /**
     * Get shipment data
     *
     * @param OrderInterface $order
     *
     * @return array
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function getShippingData(OrderInterface $order)
    {
        //if Order has shipment
        if (!$this->orderHasShipments($order)){
            return array();
        }

        return $this->getCustomerData($order, true);
    }

    /**
     * Returns the correct profile and additional customer data if shipping is false.
     *
     * @param OrderInterface $order
     * @param $shipping
     * @return array
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function addAdditionalProfileData(OrderInterface $order, $shipping){
        //Check if Order has no shipment
        if ($shipping === false){

            $arrayData = $order->getBillingProfile()->get('address')->first();

            //get Lang
            $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

            //add data to array
            $additionalCustomerData["locale"] = strtolower($language) . "_" . strtoupper($language);
            $additionalCustomerData["ip_address"] = \Drupal::request()->getClientIp();
            $additionalCustomerData["forwarded_ip"] = self::getForwardedIP();

            return ['profileData' => $arrayData, 'additionalCustomerData' => $additionalCustomerData];
        }

        //if Order has shipment
        $shipments = $order->get('shipments')->referencedEntities();
        $firstShipment = reset($shipments);
        $arrayData = $firstShipment->getShippingProfile()->address->first();

        return ['profileData' => $arrayData, 'additionalCustomerData' => null];
    }

    /**
     * get customer data and check if shipping must be added or not.
     *
     * @param OrderInterface $order
     * @param bool $shipping
     * @return mixed
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function getCustomerData(OrderInterface $order, $shipping = false)
    {

        $shipmentData = $this->addAdditionalProfileData($order, $shipping);
        $profileData = $shipmentData['profileData'];
        $returnData = $shipmentData['additionalCustomerData'];

        //Split street and house number
        $addressData = $this->parseCustomerAddress(
            $profileData->getAddressLine1()
        );

        //Return  data
        $returnData["first_name"] = $profileData->getGivenName();
        $returnData["last_name"] = $profileData->getFamilyName();
        $returnData["address1"] = $addressData['address'];
        $returnData["address2"] =  $profileData->getAddressLine2();
        $returnData["house_number"] = $addressData['housenumber'];
        $returnData["zip_code"] = trim($profileData->getPostalCode());
        $returnData["city"] = $profileData->getLocality();
        $returnData["state"] = $profileData->getAdministrativeArea();
        $returnData["country"] = $profileData->getCountryCode();
        $returnData["email"] = $order->getEmail();


        return $returnData;
    }

    /**
     * Check if order has been completed
     *
     * @param $status
     *
     * @return bool
     */
    public static function isStatusCompleted($status){
        return in_array($status, [
                OrderHelper::MSP_COMPLETED,
                OrderHelper::MSP_UNCLEARED,
            ]);
    }

    /**
     * Get the adjustment type of an order item
     *
     * @param OrderItem $orderItem
     * @param           $type
     *
     * @return bool | object
     */
    public function getAdjustment(OrderItem $orderItem, $type)
    {
        //Loop through all adjustments
        foreach ($orderItem->getAdjustments() as $key => $adjustment) {

            //Get the given adjustment $type
            if ($adjustment->getType() === $type) {
                return $orderItem->getAdjustments()[$key];
            }
        }
        return false;
    }

    /**
     * return shipping item cart data
     *
     * @param OrderInterface $order
     * @param int            $quantity
     *
     * @return array
     */
    public function getShippingCartData(OrderInterface $order, $quantity = 1)
    {
        //If no shipping on the order. don't add shipping to cart
        if(!$this->orderHasShipments($order)){
            return [];
        }

        $shipments = $order->get('shipments')->referencedEntities();
        $firstShipment = reset($shipments);

        //Get plugin
        $shippingPlugin = $firstShipment
            ->getShippingMethod()
            ->getPlugin();

        //Get configuration
        $shippingMethod = $shippingPlugin->getConfiguration();

        //If shipping method is flat rate per item
        if($shippingPlugin instanceof FlatRatePerItem){
            $shippingMethod['rate_amount']['number'] = $shippingMethod['rate_amount']['number'] * $quantity;
        }

        //Make shipping amount object
        $shippingAmount = new Price((string)$shippingMethod['rate_amount']['number'],$shippingMethod['rate_amount']['currency_code']);

        //If price is higher than 0 / free
        if($shippingAmount->getNumber() > 0) {

            //Make an array of the item and fill it with the data
            return array(
                "name"               => $shippingMethod['rate_label'],
                "description"        => '',
                "unit_price"         => $shippingAmount->getNumber(),
                "quantity"           => 1,
                "merchant_item_id"   => 'msp-shipping',
                "tax_table_selector" => "BTW0",
                "weight"             => array(
                    "unit"  => 0,
                    "value" => 'KG'
                ),
            );
        }

        return [];
    }

    /**
     * Logs MSP order related actions of the order
     *
     * @param $order
     * @param $log
     */
    public function logMsp($order, $log){
        $client = new Client();
        //set the mode of the gateway
        $gatewayHelper = new GatewayHelper();
        $mode = $gatewayHelper->getGatewayMode($order);

        $this->mspApiHelper->setApiSettings($client, $mode);

        $mspOrder = $client->orders->get('orders', $order->id());
        $gateway = $order->get('payment_gateway')->first(
        )->entity;

        $this->logStorage->generate($order, $log)->setParams([
                'old_gateway' => $gateway->get('label'),
                'new_gateway' => $mspOrder->payment_details->type,
                'status' => $mspOrder->status,
                'amount' => number_format($mspOrder->amount / 100, 2) ,
                'currency' => $mspOrder->currency,
                'msp_id' => $mspOrder->transaction_id,
                'external_id' => $mspOrder->payment_details->external_transaction_id,
            ])->save();
    }

    /**
     * Create HTML element to show on the MSP checkout page
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getItemsData(OrderInterface $order)
    {
        $html = "<ul>\n";

        //Generate a list of all ordered items
        foreach ($order->getItems() as $item)
        {
            $product = $item->getPurchasedEntity();

            $quantity = (string)floatval($item->getQuantity());

            $html .= "<li>{$quantity}&times; : {$product->getTitle()}</li>\n";
        }

        $html .= "</ul>";
        return $html;
    }

}