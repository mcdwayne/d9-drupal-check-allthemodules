# Drupal Commerce 2 CoinGate Plugin 

Accept Bitcoin and 50+ Cryptocurrencies on your Drupal Commerce store.

Read the plugin installation instructions below to get started with CoinGate Cryptocurrency payment gateway on your shop. Accept Bitcoin, Litecoin, Ethereum and other coins hassle-free - and receive settlements in Bitcoin or in Euros or US Dollars to your bank.

## Install

Sign up for CoinGate account at <https://coingate.com> for production and <https://sandbox.coingate.com> for testing (sandbox) environment.

For a detailed explanation on how to create a merchant account please read our tutorial (https://support.coingate.com/en/42/how-can-i-create-coingate-api-credentials).

Please take note, that for "Test" mode you **must** generate separate API credentials on <https://sandbox.coingate.com>. API credentials generated on <https://coingate.com> will **not** work for "Test" mode.

Also note, that *Receive Currency* parameter in your plugin configuration window defines the currency of your settlements from CoinGate. Set it to BTC, USDT, EUR, USD or *Do not convert*, depending on how you wish to receive payouts. To receive settlements in **Euros** or **U.S. Dollars** to your bank, you have to verify as a merchant on CoinGate (login to your CoinGate account and click *Verification*). If you set your receive currency to **Bitcoin** or **USDT**, verification is not needed.

### via FTP 

1. Download <https://github.com/coingate/drupal-commerce2-plugin/releases/download/v1.0.0/DrupalCommerce2-CoinGate-1.0.0.zip>

2. Unzip and upload **commerce_coingate** directory to **/web/modules/custom/**. If directory **custom** does not exist in **modules** you can create it.

3. Activate the module by going to **Extend** » **List** checking the  **Commerce Coingate** module and clicking **Install** at the bottom of the page.

4. Configure the module by going to **Commerce** » **Configuration** » **Payment** » **Payment Gatways** and clicking **Add payment gateway**. In the configuration select the **Coingate** plugin and enter your **API Auth Token** , select **Receive Currecy** paramater for your payout currecy and under **Mode** check **Test** if you created a sandbox account or **Live** for production.

5. Select the **Enabled** under **Status** and click **Save**.