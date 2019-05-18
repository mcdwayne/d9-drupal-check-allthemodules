Alipay is the most popular thrid-party online payment solution in China,
founded by Alibaba Group (NYSE:BABA).

By using this module, Drupal site gets the ability of pay with Alipay for your
online business.

Dependencies
Please install Libraries API and download this customized version of Alipay SDK
from github(https://github.com/lugir/alipay), unzip it under sites/all/libraries

Configuration
Configure Alipay settings in Configurations » System » Alipay:

Partner ID and MD5 Key can be found at https://b.alipay.com/order/pidAndKey.htm
More settings will be avaliable in future versions.

Sub-module
There is a sub-module named alipay_demo, after enable it, you can use the demo
form in alipay/demo page to perform an online payment via Alipay.

API
Developers can use alipay_api_pay($out_trade_no, $subject, $total_fee, $body);
to navigate user to a payment page.

This project is sponsored by: TheRiseIT, Inc(www.theriseit.com)
