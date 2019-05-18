<?php

namespace Drupal\moodle_integration\Services;
use Drupal\moodle_integration\Utility;
use \Drupal\Core\Database\Connection;

/**
 * Class CustomService.
 */

class CourseService
{
    public function getServiceData()
    {
        $config    = \Drupal::config('moodle.settings');
        $baseurl   = $config->get('url') . '/webservice/rest/server.php?';
        $user      = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $moodle_id = $user->field_test->value;
        $params    = array(
            'wstoken' => $config->get('wstoken'),
            'wsfunction' => 'core_enrol_get_users_courses',
            'moodlewsrestformat' => 'json',
            'userid' => $moodle_id
        );
        $url       = $baseurl . http_build_query($params);
        $response  = file_get_contents($url);
        $newusers  = json_decode($response);
        return $newusers;
    }
    /**
     * Here you can pass your values as $array.
     */
    public function postServiceData($array)
    {
        //Do something here to post any data.
    }
}
