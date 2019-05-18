# Acquia Cloud Varnish - Stripping Query Strings for Performance

For customers using Acquia Cloud, the default Varnish cache configuration (VCL) will strip query parameters of commonly used marketing and analytics tools to make the caches more performant and improve hit rates.  This is good until Drupal makes a redirect where the the destination expects to contain those parameters which have been stripped.

This module takes the header 'X-Acquia-Stripped-Query' which Varnish passes to the backend after the query has been stripped, and applies the original query string to the URL of the destination path of the redirect.

You can read more about this feature in the documentation:

https://docs.acquia.com/acquia-cloud/performance/varnish/querystrings/

