<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */
namespace Drupal\commerce_multisafepay\Helpers;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class GatewayHelper
{
    use StringTranslationTrait;

    protected $mspOrderHelper;

    public function __construct()
    {
        $this->mspOrderHelper = new OrderHelper();
    }

    const MSP_GATEWAYS = [
        'gateways'     => [
            //Redirects
            'msp_belfius' => array('code' => 'BELFIUS', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_mistercash' => array('code' => 'MISTERCASH', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_amex' => array('code' => 'AMEX', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_dirdeb' => array('code' => 'DIRDEB', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_dotpay'  => array('code' => 'DOTPAY', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_eps'  => array('code' => 'EPS', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_ferbuy' => array('code' => 'AMEX', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_giropay' => array('code' => 'GIROPAY', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_idealqr' => array('code' => 'IDEALQR', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_visa' => array('code' => 'VISA', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_maestro' => array('code' => 'MAESTRO', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_mastercard' => array('code' => 'MASTERCARD', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_paysafecard' => array('code' => 'PSAFECARD', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_directbank' => array('code' => 'DIRECTBANK', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_trustpay' => array('code' => 'TRUSTPAY', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_wallet'  => array('code' => '', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_afterpay' => array('code' => 'AFTERPAY', 'type' => 'redirect', 'shopping_cart' => true),
            'msp_klarna' => array('code' => 'KLARNA', 'type' => 'redirect', 'shopping_cart' => true),
            'msp_payafterdelivery'  => array('code' => 'PAYAFTER', 'type' => 'redirect', 'shopping_cart' => true),
            'msp_santander'  => array('code' => 'SANTANDER', 'type' => 'redirect', 'shopping_cart' => false),

            //Direct
            'msp_ideal'  => array('code' => 'IDEAL', 'type' => 'direct', 'shopping_cart' => false),
            'msp_kbc' => array('code' => 'KBC', 'type' => 'direct', 'shopping_cart' => false),
            'msp_trustly' => array('code' => 'TRUSTLY', 'type' => 'direct', 'shopping_cart' => false),
            'msp_paypal' => array('code' => 'PAYPAL', 'type' => 'direct', 'shopping_cart' => false),
            'msp_alipay' => array('code' => 'ALIPAY', 'type' => 'direct', 'shopping_cart' => false),
            'msp_banktrans' => array('code' => 'BANKTRANS', 'type' => 'direct', 'shopping_cart' => false),
            'msp_einvoice' => array('code' => 'EINVOICE', 'type' => 'direct', 'shopping_cart' => true),
            'msp_inghome' => array('code' => 'INGHOME', 'type' => 'direct', 'shopping_cart' => false),

            //Gift cards
            'msp_babygiftcard' => array('code' => 'BABYGIFTCARD', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_beautyandwellness' => array('code' => 'BEAUTYANDWELLNESS', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_boekenbon' => array('code' => 'BOEKENBON', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_erotiekbon' => array('code' => 'EROTIEKBON', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_fashioncheque' => array('code' => 'FASHIONCHEQUE', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_fashiongiftcard' => array('code' => 'FASHIONGIFTCARD', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_fietsenbon' => array('code' => 'FIETSENBON', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_gezondheidsbon' => array('code' => 'GEZONDHEIDSBON', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_givacard' => array('code' => 'GIVACARD', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_goodcard' => array('code' => 'GOODCARD', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_nationaletuinbon' => array('code' => 'NATIONALETUINBON', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_nationaleverwencadeaubon' => array('code' => 'NATIONALEVERWENCADEAUBON', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_parfumcadeaukaart' => array('code' => 'PARFUMCADEAUKAART', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_podiumcadeaukaart' => array('code' => 'PODIUM', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_sportenfit' => array('code' => 'SPORTENFIT', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_vvvcadeaukaart' => array('code' => 'VVVGIFTCRD', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_wellnessgiftcard' => array('code' => 'WELLNESSGIFTCARD', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_wijncadeau' => array('code' => 'WIJNCADEAU', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_winkelcheque' => array('code' => 'WINKELCHEQUE', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_yourgift' => array('code' => 'YOURGIFT', 'type' => 'redirect', 'shopping_cart' => false),
            'msp_webshopgiftcard' => array('code' => 'WEBSHOPGIFTCARD', 'type' => 'redirect', 'shopping_cart' => false),
        ]
    ];

    /**
     * Check if the $gateway is part of MSP
     *
     * @param $gateway
     *
     * @return bool
     */
    public function isMspGateway($gateway){
        return isset(self::MSP_GATEWAYS['gateways'][$gateway]);
    }

    /**
     * Check if the given gateway has changed to another gateway
     *
     * @param $mspGateway
     * @param $gateway
     * @param $order
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function logDifferentGateway($mspGateway, $gateway, $order){
        if ($mspGateway !== self::MSP_GATEWAYS['gateways'][$gateway->getPluginId()]['code']) {
            $this->mspOrderHelper->logMsp($order, 'order_new_gateway' );
        }
    }

    /**
     * Check if shopping cart is true or false
     *
     * @param $gatewayId
     * @return bool
     */
    public static function isShoppingCartAllowed($gatewayId)
    {
        if (self::MSP_GATEWAYS['gateways'][$gatewayId]['shopping_cart']) {
            return true;
        }
        return false;
    }

    /**
     * Get the gateway mode from the order
     *
     * @param $order
     *
     * @return mixed
     */
    public function getGatewayMode($order){
        return $order->get('payment_gateway')->first()->entity->get('configuration')['mode'];
    }
}