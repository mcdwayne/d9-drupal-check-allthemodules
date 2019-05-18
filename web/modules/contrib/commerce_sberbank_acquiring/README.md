# Commerce Sberbank Acquiring

Integration [Sberbank Acquiring](http://data.sberbank.ru/en/s_m_business/bankingservice/equairing/) with Drupal Commerce 2.

**This module should be only installed via Composer. Archives on drupal.org for informative purposes.**

## Installation

1. Request module with composer `composer require drupal/commerce_sberbank_acquiring`.
2. Enable module as usual.
3. Navigate to Store > Configuration > Payments > Payment Gateways.
4. Add new payment gateway as you need, just select plugin Sberbank Acquring.
5. Select payment mode, enter username and password for selected mode. They are different for test and live REST API's.
6. Save it.

## Credit cards for testing

| Type       | Credit No.          | Expires | CVC/CVV  | 3-D Secure |
|------------|---------------------|---------|----------|------------|
| VISA       | 4111 1111 1111 1111 | 2019/12 | 123      | 12345678   |
| MasterCard | 5555 5555 5555 5599 | 2019/12 | 123      |            |
| Apple Pay  | 5204 2477 5000 1471 | 2022/11 | 111      |            |

## Other info

- [REST API](https://web.rbsdev.com/dokuwiki/doku.php/integration:api:rest:start)
- [REST API](https://developer.sberbank.ru/acquiring-api-basket)*
- [Sberbank Acquiring official website](https://www.sberbank.ru/ru/s_m_business/bankingservice/acquiring_total) (RU)

\* _Sberbank has several API documentations, two of them listed above and two PDF's they send after registration. They also have two versions of API, one is basic and other is extended. So be care with it if you want to alter some data._