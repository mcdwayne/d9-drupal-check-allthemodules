<?php

namespace Drupal\miniorange_saml\includes\metadata;

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Specify relative path to the drupal root.
$autoloader = require_once __DIR__ . '/../../../../autoload.php';
$request = Request::createFromGlobals();
// Retrieve the site path
// $site_path = DrupalKernel::findSitePath($request);
// Bootstrap drupal to different levels
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->prepareLegacyRequest($request);

global $base_url;	
$site_url = substr($base_url, 0, strpos($base_url, '/modules'));   

$entity_id = $site_url;
$acs_url = $site_url . '/samlassertion'; 
$certificate = file_get_contents( \Drupal::root() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'miniorange_saml' . DIRECTORY_SEPARATOR .  'resources' . DIRECTORY_SEPARATOR . 'sp-certificate.crt' );

$certificate = preg_replace("/[\r\n]+/", "", $certificate);
$certificate = str_replace( "-----BEGIN CERTIFICATE-----", "", $certificate );
$certificate = str_replace( "-----END CERTIFICATE-----", "", $certificate );
$certificate = str_replace( " ", "", $certificate );

header('Content-Type: text/xml');
echo '<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" validUntil="2020-10-28T23:59:59Z" cacheDuration="PT1446808792S" entityID="' . $entity_id . '">
  <md:SPSSODescriptor AuthnRequestsSigned="false" WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:KeyDescriptor use="signing">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <ds:X509Certificate>' . $certificate . '</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</md:NameIDFormat>
	<md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified</md:NameIDFormat>
	<md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:transient</md:NameIDFormat>
	<md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:persistent</md:NameIDFormat>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="' . $acs_url . '" index="1"/>
  </md:SPSSODescriptor>
  <md:Organization>
    <md:OrganizationName xml:lang="en-US">miniOrange</md:OrganizationName>
    <md:OrganizationDisplayName xml:lang="en-US">miniOrange</md:OrganizationDisplayName>
    <md:OrganizationURL xml:lang="en-US">http://miniorange.com</md:OrganizationURL>
  </md:Organization>
  <md:ContactPerson contactType="technical">
    <md:GivenName>miniOrange</md:GivenName>
    <md:EmailAddress>info@miniorange.com</md:EmailAddress>
  </md:ContactPerson>
  <md:ContactPerson contactType="support">
    <md:GivenName>miniOrange</md:GivenName>
    <md:EmailAddress>info@miniorange.com</md:EmailAddress>
  </md:ContactPerson>
</md:EntityDescriptor>';