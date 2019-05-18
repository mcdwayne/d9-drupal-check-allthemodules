<?php

namespace Drupal\openinbound\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformYamlTidy;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\openinbound\Controller\OI;

/**
 * Webform submission to OpenInbound handler.
 *
 * @WebformHandler(
 *   id = "openinbound_webform",
 *   label = @Translation("OpenInbound"),
 *   category = @Translation("External"),
 *   description = @Translation("Send webform submissions to OpenInbound.com."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class InboundWebformHandler extends WebformHandlerBase {

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $data = $webform_submission->getData();
        $config = \Drupal::service('config.factory')->getEditable('openinbound.settings');
        $oi = new OI($config->get('settings.openinbound_tracking_id'), $config->get('settings.openinbound_api_key'));
        \Drupal::logger('oi_debug_tracker_js')->notice(print_r($_COOKIE,1));
        \Drupal::logger('oi_debug_tracker_js')->notice(print_r($data,1));

        $oi->updateContact($_COOKIE['_oi_contact_id'], $data);
    }

}