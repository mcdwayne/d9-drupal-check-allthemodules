<?php

namespace Drupal\commerce_coingate\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class RedirectCheckoutForm extends PaymentOffsiteForm
{
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;

        /** @var \Drupal\commerce_coingate\Plugin\Commerce\PaymentGateway\RedirectCheckoutInterface $paymentGatewayPlugin*/
        $paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();

        $extra = [
            'return_url' => $form['#return_url'],
            'cancel_url' => $form['#cancel_url'],
        ];

        $coingateResponse = $paymentGatewayPlugin->createCoinGateInvoice($payment, $extra);

        if (!isset($coingateResponse['payment_url'])){
            return [
                '#type' => 'inline_template',
                '#template' => "<span>{{ '" . $coingateResponse. "' | t }}</span>",
            ];
        }

        $data = [
            'version' => 'v1',
            'return' => $form['#return_url'],
            'cancel' => $form['#cancel_url'],
            'total' => $payment->getAmount()->getNumber(),
        ];

        $response  = $this->buildRedirectForm(
            $form,
            $form_state,
            $coingateResponse['payment_url'],
            $data,
            PaymentOffsiteForm::REDIRECT_GET
        );

        return $response;
    }
}
