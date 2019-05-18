<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

namespace Drupal\commerce_multisafepay\Helpers;


use Drupal\Core\StringTranslation\StringTranslationTrait;

class ConditionHelper
{
    use StringTranslationTrait;

    protected $enabledTotal;
    protected $enabledCurrency;
    protected $currencyType;

    /**
     * Sets the Order amount condition
     *
     * @param $operator , Type of operator (example: lesser than = <, greater than = > etc)
     * @param $amount , The amount.
     * @param $currency , Type of currency (USD, EUR)
     * @return array
     */
    public function orderTotalCondition($operator, $amount, $currency){

        //Create a condition
        $condition = [
            'conditions' => [
                [
                    'plugin' => 'order_total_price',
                    'configuration' => [
                        'operator' => $operator,
                        'amount' => [
                            'number' => $amount,
                            'currency_code' => $currency
                        ]
                    ]
                ]
            ]
        ];

        //Set enabled true because we use it
        $this->enabledTotal = true;

        return $condition;
    }

    /**
     * Set the currency to this so we can use it in the messages
     *
     * @param $currency, Sets the currency type
     * @return bool
     */
    public function orderCurrencyCondition($currency){
        $this->currencyType = $currency;
        $this->enabledCurrency = true;
        return true;
    }

    /**
     * Sets the message of the condition
     *
     * @return array
     */
    public function orderConditionMessage(){
        $message = t('This gateway contains a restriction. To enable it please click on Order and Enable:');

        //set styling
        $form['styling'] = [
            '#type' => 'html_tag',
            '#tag' => 'style',
            '#value' =>'
                h3, li {
                    color: #0074bd;
                }
       '];

        //set message
        $form['details'] = [
            '#type' => 'html_tag',
            '#tag' => 'b',
            '#value' =>'
            <h3>'. $message .'</h3>
            <ul>
                ' . $this->checkTotalCondition() . '
                ' . $this->checkCurrencyCondition() . '
            </ul>
       '];

        return $form;
    }

    /**
     * Sets the message of the condition
     *
     * @return string
     */
    public function checkTotalCondition(){
        return $this->enabledTotal ? '<li>'. t('Current order total') . '</li>' : '';
    }

    /**
     * Sets the message of the condition
     *
     * @return string
     */
    public function checkCurrencyCondition(){
        return $this->enabledCurrency ? '<li>'. t('Order currency') . ' - ' . t($this->currencyType) .'</li>' : '';
    }
}