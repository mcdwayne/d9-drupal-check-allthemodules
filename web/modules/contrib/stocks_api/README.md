# Stocks API

This module provides an API for retrieving financial and industry data for individual stocks and stock exchanges.

## Features

### Stock Exchange Interface

The module makes HTTP requests to Nasdaq.com for information about stocks offered on various stock exchanges.
This data includes a list of stocks traded on an exchange, along with some basic industry and financial data for each stock on the exchange, parsed into a map.
Currently, only stocks listed on the New York Stock Exchange (NYSE) and NASDAQ are supported.

### Stock Data Interface

This module makes HTTP requests to Alpha Vantage for data on individual stocks. This data is returned as a JSON object and parsed into a map.
The requests to Alpha Vantage require an API key, which must be generated from the Alpha Vantage website (https://www.alphavantage.co/).
The API key must then be stored in the "alpha_vantage_api_key" setting in settings.php.

### Extensibility

The module supports scalability to other stock data services through abstract class interfaces.

## Testing

This module has not been tested for production environments.

## Contributors

* **Omar Abed** (@omarabed)
* **Malik Kotob** (@malikkotob) - Drupal 8 Grandmaster

## Acknowledgments

Big thanks to @malikkotob for extensive guidance throughout the development of this module.
