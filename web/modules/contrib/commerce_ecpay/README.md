# commerce_ecpay
Provides Commerce integration for ECPay (Work In Process)

## Features

* Support ECPay credit card off-site payment method

## Installation

TBC...

## Configure

1. Create a payment gateway on Drupal Commerce
2. Choose the payment gateway mode and input the credentials

In testing mode:

* MerchantID: **2000132** / **2000214** (non-OTP)
* Hash key: **5294y06JbISpM5x9**
* HashIV: **v77hoKGq4kWxNNIS**

In live mode:

According to the [instructions](https://www.ecpay.com.tw/CascadeFAQ/CascadeFAQ_Qa?nID=1179
), the MerchantID, Hash key and Hash IV can be get from the [admin page](https://vendor.ecpay.com.tw/) in ECPay. 

## Compatibility

* Tests on Drupal Commerce 2.x

