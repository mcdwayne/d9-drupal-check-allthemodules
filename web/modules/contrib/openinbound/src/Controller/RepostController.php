<?php

namespace Drupal\openinbound\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RepostController extends ControllerBase
{
    function repost()
    {
        $oi_contact_id = filter_input(INPUT_COOKIE, '_oi_contact_id', FILTER_VALIDATE_INT);
        if ($oi_contact_id > 0) {
            $config = $this->config('openinbound.settings');
            $openinbound_tracking_id = $config->get('settings.openinbound_tracking_id');
            $openinbound_api_key = $config->get('settings.openinbound_api_key');
            $oi_email = filter_input(INPUT_GET, 'oi_email');
            if (!empty($oi_email)) {
                if (valid_email_address($oi_email)) {
                    $data['email'] = $oi_email;
                    $oi = new OI($openinbound_tracking_id, $openinbound_api_key);
                    $oi->updateContact($oi_contact_id, $data);

                    $properties = array();
                    $properties['title'] = 'E-Mail updated to ' . $oi_email . ' through "oi_email" parameter.';
                    $properties['event_type'] = 'oi_email';
                    $properties['raw'] = json_encode($data);
                    $oi->addEvent($oi_contact_id, $properties);
                }
            }

            $oi_add_tag = filter_input(INPUT_GET, 'oi_add_tag');
            if (!empty($oi_add_tag)) {
                $oi = new OI($openinbound_tracking_id, $openinbound_api_key);
                $oi->addTagToContact($oi_contact_id, $oi_add_tag);

                $properties = array();
                $properties['title'] = 'Tag "' . $oi_add_tag . '" added through "oi_add_tag" parameter.';
                $properties['event_type'] = 'oi_add_tag';
                $properties['raw'] = json_encode($oi_add_tag);
                $oi->addEvent($oi_contact_id, $properties);
            }

            $oi_add_event = filter_input(INPUT_GET, 'oi_add_event');
            if (!empty($oi_add_event)) {
                $oi = new OI($openinbound_tracking_id, $openinbound_api_key);
                $properties = array();
                $properties['title'] = 'Event "' . $oi_add_event . '" added through "oi_add_event" parameter.';
                $properties['event_type'] = 'oi_add_event';
                $properties['raw'] = json_encode($oi_add_event);
                $oi->addEvent($oi_contact_id, $properties);
            }
        }
        header("Content-type: text/javascript");
        echo $oi_contact_id;
        exit();
    }
}