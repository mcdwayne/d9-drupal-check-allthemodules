# Services Environment Variable Parameters
initially developed by drunomics GmbH <hello@drunomics.com>


## Usage

* Install as usual.
* Set environment variables using the pattern:


    DRUPAL_SERVICE_{ variable }={ value }

The variable name following the same rules:

 * Casing is kept as is
 * '__' is replaced by dots (".").
 * '___' is used for setting nested array structures
 
 
 ## Usage example - configuring CORS:
 
For example the following environment variables would set Drupal's CORS values:

    DRUPAL_SERVICE_cors__config___enabled=1
    DRUPAL_SERVICE_cors__config___allowedOrigins___0=http://www.example.com
