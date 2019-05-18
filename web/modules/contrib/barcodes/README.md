# Barcodes
The Barcodes module provides a field formatter to display various field types as rendered Barcodes.

**Available Barcodes**
* C39 : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9
* C39+ : CODE 39 with checksum
* C39E : CODE 39 EXTENDED
* C39E+ : CODE 39 EXTENDED + CHECKSUM
* C93 : CODE 93 - USS-93
* S25 : Standard 2 of 5
* S25+ : Standard 2 of 5 + CHECKSUM
* I25 : Interleaved 2 of 5
* I25+ : Interleaved 2 of 5 + CHECKSUM
* C128 : CODE 128
* C128A : CODE 128 A
* C128B : CODE 128 B
* C128C : CODE 128 C
* EAN2 : 2-Digits UPC-Based Extension
* EAN5 : 5-Digits UPC-Based Extension
* EAN8 : EAN 8
* EAN13 : EAN 13
* UPCA : UPC-A
* UPCE : UPC-E
* MSI : MSI (Variation of Plessey code)
* MSI+ : MSI + CHECKSUM (modulo 11)
* POSTNET : POSTNET
* PLANET : PLANET
* RMS4CC : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)
* KIX : KIX (Klant index - Customer index)
* IMB : IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200
* IMBPRE : IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200- pre-processed
* CODABAR : CODABAR
* CODE11 : CODE 11
* PHARMA : PHARMACODE
* PHARMA2T : PHARMACODE TWO-TRACKS
* DATAMATRIX : DATAMATRIX (ISO/IEC 16022)
* PDF417 : PDF417 (ISO/IEC 15438:2006)
* QRCODE : QR-CODE
* RAW : 2D RAW MODE comma-separated rows
* RAW2 : 2D RAW MODE rows enclosed in square parentheses

## Installation
Install dependencies with composer from your webroot:
    > composer require tecnickcom/tc-lib-barcode:^1
Enable the module
    > drush en barcodes

## Configuration
* Add a field of one of the types of email, integer, link, string, telephone, text, text_long or text_with_summary
* Choose Barcode as formatter
* Adjust the settings like type, color and dimensions to your liking

## Optional dependencies
* [Composer manager](https://drupal.org/project/composer_manager) (Drupal 7.x)  
  You may use composer manager module to manage external dependencies.
* [Token](https://drupal.org/project/token) (Drupal 7.x / 8.x)  
  You may use Token module, if you need token replacement functionality in your barcode data.

## Dependencies
* No further system dependencies, just PHP and Drupal
* No external service dependencies
* No special font dependencies
