INTRODUCTION
------------
The HelloSign module is a Drupal integration for the HelloSign electronic
signature API. It's a simple module that allows you to enter and store
your API key and token, encrypted using the Encryption module, and then
call the service to return a HelloSign client, upon which you can invoke
any of the supported methods provided by the SDK.

The module also provides an easy callback method for signature events.
Simply implement hook_process_hellosign_callback() to process all signature
request event callbacks and respond as desired.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/hellosign


 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/hellosign


 * For more information about HelloSign and its features:
   https://www.hellosign.com/


REQUIREMENTS
------------
This module requires the following library:
 * HelloSign PHP SDK (https://github.com/HelloFax/hellosign-php-sdk)

This module requires the Encryption module as a dependency, which can be found
here:
 * https://www.drupal.org/project/encryption


INSTALLATION
------------
 * Install this module via composer by running the following command:
   - composer require drupal/hellosign

 * After installing, until the issue below is fixed, remove the vendor
   folder entirely from the sdk folder before use.
   - https://github.com/HelloFax/hellosign-php-sdk/issues/53

CONFIGURATION
-------------
 * Configure HelloSign in Administration » Configuration » System » HelloSign
 or by going directly to /admin/config/system/hellosign:

   - HelloSign API Key

     The API key associated with your HelloSign account. You can create an
     account at https://www.hellosign.com/

   - HelloSign Client ID

     The Client ID associated with this HelloSign project. After you have a
     HelloSign account, you can create a client for the domain name you are
     using, and a client ID will be assigned to you.

   - CC email addresses

     A comma-separated list of email addresses which will be copied on every
     HelloSign signature request. Useful if you want to track completed requests
     by email without manually adding an additional address to every signature
     request.

   - Test mode

     Enables and disables test mode. In test mode, all requests sent to
     HelloSign will indicate to HelloSign that they are test requests.


USING THE API
-------------
 * To create a new Client connection instance, fetch the HelloSign service and
   call getClient().

   - $client = \Drupal::service('hellosign')->getClient()

 * To create a new signature request, call the createSignatureRequest method on
   the client with the following params.

   - $title: Document title
   - $subject: Email subject
   - $signers: Array of signers with a key of email address and a value of name
   - $file: A full path to a local system file
   - $mode: The type of signature request, either "embedded" or "email"

   This returns an array with status of 1 for success and 0 for failure. If
   failure, it also returns a "message", which contains the error string. If
   success, it also returns a signature_request_id token from HelloSign and an
   array of signatures.

 * To use any of the other methods the HelloSign SDK requires, simply call those
   methods on the HelloSign client.

   - Ex: $client->cancelSignatureRequest($signature_request_id)
   - Ex: $client->getSignatureEmbedUrl($signature_request_id)


MAINTAINERS
-----------
Current maintainers:
 * Clint Randall (https://www.drupal.org/u/camprandall)
 * Mike Goulding (https://www.drupal.org/u/mikeegoulding)
 * Jay Kerschner (https://www.drupal.org/u/jkerschner)
