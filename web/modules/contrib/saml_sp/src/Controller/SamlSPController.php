<?php

namespace Drupal\saml_sp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Response as Saml2_Response;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides route responses for the SAML SP module.
 */
class SamlSPController extends ControllerBase {

  /**
   * Generate the XMl metadata for the given IdP.
   */
  public function metadata($return_string = FALSE) {
    list($metadata, $errors) = saml_sp__get_metadata();

    $output = $metadata;

    if ($return_string) {
      return $output;
    }
    $response = new Response();
    $response->setContent($metadata);
    $response->headers->set('Content-Type', 'text/xml');
    return $response;
  }

  /**
   * Receive data back from the IdP.
   */
  public function consume() {
    if (!$this->validAuthenticationResponse()) {
      return new RedirectResponse(\Drupal::url('<front>'));
    }

    // The \OneLogin\Saml2\Response object uses the settings to verify the
    // validity of a request, in \OneLogin\Saml2\Response::isValid(), via
    // XMLSecurityDSig. Extract the incoming ID (the `inresponseto` parameter
    // of the `<samlp:response` XML node).
    if ($inbound_id = _saml_sp__extract_inbound_id($_POST['SAMLResponse'])) {
      if ($request = saml_sp__get_tracked_request($inbound_id)) {
        $idp = saml_sp_idp_load($request['idp']);

        // Try to check the validity of the samlResponse.
        try {
          $certs = $idp->getX509Cert();
          if (!is_array($certs)) {
            $certs = [$certs];
          }
          $is_valid = FALSE;
          // Go through each cert and see if any provides a valid response.
          foreach ($certs as $cert) {
            if ($is_valid) {
              continue;
            }
            $idp->setX509Cert([$cert]);
            $settings = saml_sp__get_settings($idp);
            // Creating Saml2 Settings object from array:
            $saml_settings = new Settings($settings);
            $saml_response = new Saml2_Response($saml_settings, $_POST['SAMLResponse']);
            // $saml_response->isValid() will throw various exceptions
            // to communicate any errors. Sadly, these are all of type
            // Exception - no subclassing.
            $is_valid = $saml_response->isValid();
          }
        }
        catch (Exception $e) {
          // @TODO: Inspect the Exceptions, and log a meaningful error condition.
          \Drupal::logger('saml_sp')->error('Invalid response, %exception', ['%exception' => $e->message]);
          $is_valid = FALSE;
        }
        // Remove the now-expired tracked request.
        $store = saml_sp_get_tempstore('track_request');
        $store->delete($inbound_id);

        if (!$is_valid) {
          $error = $saml_response->getError();
          list($problem) = array_reverse(explode(' ', $error));

          switch ($problem) {
            case 'Responder':
              $message = t('There was a problem with the response from @idp_name. Please try again later.', [
                '@idp_name' => $idp->label(),
              ]);
              break;

            case 'Requester':
              $message = t('There was an issue with the request made to @idp_name. Please try again later.', [
                '@idp_name' => $idp->label(),
              ]);
              break;

            case 'VersionMismatch':
              $message = t('SAML VersionMismatch between @idp_name and @site_name. Please try again later.', [
                '@idp_name' => $idp->label(),
                '@site_name' => variable_get('site_name', 'Drupal'),
              ]);
              break;
          }
          if (!empty($message)) {
            \Drupal::messenger()->addMessage($message, MessengerInterface::TYPE_ERROR);
          }
          \Drupal::logger('saml_sp')->error('Invalid response, @error: <pre>@response</pre>', [
            '@error' => $error,
            '@response' => print_r($saml_response->response, TRUE),
          ]);
        }

        // Invoke the callback function.
        $callback = $request['callback'];
        $result = $callback($is_valid, $saml_response, $idp);

        // The callback *should* redirect the user to a valid page.
        // Provide a fail-safe just in case it doesn't.
        if (empty($result)) {
          return new RedirectResponse(\Drupal::url('user.page'));
        }
        else {
          return $result;
        }
      }
      else {
        \Drupal::logger('saml_sp')->error('Request with inbound ID @id not found.', ['@id' => $inbound_id]);
      }
    }
    // Failover: redirect to the homepage.
    \Drupal::logger('saml_sp')->warning('Failover: redirect to the homepage. No inbound ID or something.');
    return new RedirectResponse(\Drupal::url('<front>'));
  }

  /**
   * Check that a request is a valid SAML authentication response.
   *
   * @return bool
   *   TRUE if the response is valid.
   */
  private function validAuthenticationResponse() {
    return ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['SAMLResponse']));
  }

  /**
   * Log the user out.
   */
  public function logout() {

  }

}
