<?php

/**
 * @file
 * The "Thunder Credentials" page layout.
 */

namespace Drupal\desk_net\Controller;

use Drupal\Core\Controller\ControllerBase;

class DrupalCredentialsController extends ControllerBase {

  /**
   * Generating custom form for "Drupal Credentials" page.
   */
  public function drupalCredentials() {
    global $base_url;

    $html = '<h2>' . t('Thunder Credentials') . '</h2>';
    $html .= '<p>';
    $html .= t('Use these credentials in Desk-Net on the Advanced Settings tab of 
   the <a href="https://www.desk-net.com/objectsPage.htm" target="_blank">
   platform</a> you are connecting to this Thunder website.');
    $html .= '</p>';

    $html .= '<table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">' . t('API URL') . '</th>
                        <td>'. $base_url . '/dr-json/v1</td>
                    </tr>
                    <tr>
                        <th scope="row">' . t('API User') . '</th>
                        <td id="api_key">
                        '. ModuleSettings::variableGet('drupal_desk_net_api_key') . '
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">' . t('API Secret') . '</th>
                        <td id="api_secret">
                        ' . ModuleSettings::variableGet('drupal_desk_net_api_secret') .'
                        </td>
                    </tr>
                 </tbody>
             </table>';

    // Modal dialog.
    $html .= '<div id="consentModal" class="modal">
                <!-- Modal content -->
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <p>' . t('Are you sure to generate new credentials?') . '</p>
                    <div class="position-control-element"><a id="confirm" href="' . $base_url . '/admin/config/desk-net/generate-new-credentials" class="button form-submit
                    btn-primary-submit" value="Confirm">Confirm</a>
                    <input type="button" name="cancel" id="cancel"
                    class="button form-submit" value="Cancel"></div>
                </div>
              </div>';

    $html .= '<input type="button" id="generate-new-credentials-submit"
  value="Generate new credentials" class="button form-submit">';

    $this->showMessage();

    return array(
      '#allowed_tags' => ['input', 'table', 'tbody', 'tr', 'td', 'th', 'div', 'span', 'p', 'h2', 'a'],
      '#markup' => $html,
    );
  }

  /**
   * Showing message after successfully generate new credentials.
   */
  private function showMessage() {
    // Getting GET parameters from url and check on generate new credentials.
    $request = \Drupal::request();
    $dn_credentials = $request->query->get('dn-credentials');

    if ($dn_credentials === 'generate') {
      drupal_set_message(t('New credentials successfully generated.'), 'status');
    }
  }
}