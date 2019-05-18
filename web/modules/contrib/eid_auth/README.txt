ID-Card and Mobile-ID authentication module for Drupal 8.

Requires external library Bitweb/id-services.
Recommended (tested) core version is 8.3

To get ID-Card authentication to work,
put the following into .htaccess file (https is required):

# Check if SSL is enabled.
<IfModule ssl_module>
  # Initiate authentication if specific path is requested.
  <If "%{DOCUMENT_URI} == '/eid/login/id_card'">
    SSLVerifyClient require
    SSLVerifyDepth 2
  </If>
</IfModule>

To test Mobile-ID:
WSDL url: https://tsp.demo.sk.ee/?wsdl
Service name: Testimine
Display message: Testimine

Add your certificate into test service (if you want to test
with your own phone): https://demo.sk.ee/MIDCertsReg/

Enable (on the form display settings page) field 'field_personal_id_code'
to allow entering personal ID codes.
