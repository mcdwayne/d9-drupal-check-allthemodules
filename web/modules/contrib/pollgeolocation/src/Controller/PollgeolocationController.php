<?php
/**
 * @file
 * @author Deep Mazumder
 * Contains \Drupal\pollgeolocation\Controller\PollgeolocationController.
 */

namespace Drupal\pollgeolocation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Url;


class pollgeolocationController extends ControllerBase
{
    /**
     * showdata.
     *
     * @return string
     *   Return Table format Geolocation data.
     */
    public function showdata()
    {
        //var defination
        $request_param = "";
        $uuid = "";
        $rows = array();
        $form = [];
        $usrquery = "";
        $results = "";
        //DB preparation area
        $db = \Drupal::database();
        $request_param = \Drupal::request()->query->get('ip');
        //Fetch user id by name
        if (!empty($request_param)) {
            $usrquery = $db->select('users_field_data', 'u')
                ->fields('u', array('uid'))
                ->condition('name', $request_param, '=');
            $results = $usrquery->execute()->fetchAll();
            //print_r($results);die;
            if (count($results)) {
                $uuid = $results[0]->uid;
            } else {
                $uuid = '';
            }
        }
        $header = array(
            // We tabe it sortable by DNT and crete table headerr.
            array('data' => $this->t('Poll Question')),
            array('data' => $this->t('User')),
            array('data' => $this->t('Geolocation')),
            array('data' => $this->t('IP')),
            array('data' => $this->t('Date/Time'), 'field' => 'timestamp', 'sort' => 'desc'),
        );
        //If user id  found pull records with pagination , sort, search filters
        if (!empty($uuid)) {
            $query = $db->select('poll_vote', 'c');
            $query->join('poll_field_data', 'cs', 'cs.id = c.pid');
            $query->fields('c', array('uid', 'hostname', 'timestamp',))
                ->fields('cs', array('question'))
                ->condition('c.uid', $db->escapeLike($uuid), '=');
            // The actual action of sorting the rows is here.
            $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
                ->orderByHeader($header);
            // Limit the rows to 10 for each page.
            $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
                ->limit(10);
            //Pager here
            $result = $pager->execute()->fetchAll();
        } else if (!empty($request_param) and empty($uuid)) {
            //If user id not found check whether ip or question available or not then pull records with pagination , sort, search filters
            $query = $db->select('poll_vote', 'c');
                $query->join('poll_field_data', 'cs', 'cs.id = c.pid');
                $query->fields('c', array('uid', 'hostname', 'timestamp',))
                ->fields('cs', array('question'));
            $query->condition(db_or()
               ->condition('c.hostname', $db->escapeLike($request_param), '=')
                ->condition('cs.question', "%" . $db->escapeLike($request_param) . "%", 'LIKE'));
            // The actual action of sorting the rows is here.
           $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
                ->orderByHeader($header);
            // Limit the rows to 10 for each page.
            $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
               ->limit(10);
            //pager here
            $result = $query->execute()->fetchAll();
            //print $query;die;
        } else {
            //If user id/ip/question not found pull records with srot or pagination
            $query = $db->select('poll_vote', 'c')
                ->fields('c', array('uid', 'hostname', 'timestamp',))
                ->fields('cs', array('question'));
            $query->join('poll_field_data', 'cs', 'cs.id = c.pid');
            // The actual action of sorting the rows is here.
            $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
                ->orderByHeader($header);
            // Limit the rows to 10 for each page.
            $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
                ->limit(10);
            $result = $pager->execute()->fetchAll();
        }
        // Populate the rows.
        foreach ($result as $row) {
            //Enable Drupal Curl
            $client = \Drupal::httpClient();
            try {
                //Add dynamic IP data here later
                $url = "https://ipinfo.io/" . $row->hostname . "/json"; // pass dynamic IP here
                $request = $client->get($url);
                $status = $request->getStatusCode();
                $transfer_success = $request->getBody()->getContents();
                $decoded = Json::decode($transfer_success);
                //Creating Geolocation string
                if(!empty($decoded['region']) and !empty($decoded['city']) and !empty($decoded['country'])) {
                    $decoded = $decoded['region'] . ", " . $decoded ['city'] . ", " . $decoded['country'];
                }
                else{
                    $decoded ='Data Not Found';
                }

            } catch (RequestException $e) {
                drupal_set_message(t('Some Ip data not received'));
            }
            //getting Drupal user name here
            $account = \Drupal\user\Entity\User::load($row->uid); // pass your drupal uid
            $name = $account->getUsername();
            if (empty($name)) {
                $name = t('Anonymous');
            }
            $rows[] = array('data' => array(t($row->question),$name, t($decoded), $row->hostname, t(format_date($row->timestamp, 'custom', 'F j, Y, g:i a'))));
        }
        //add you custom code here for filter...
        $form = array(
            '#markup' => t('<h2>List of All Voter`s Geolocation</h2>')
        );
        $form['form'] = [
            '#type' => 'form',
            '#method' => 'get'
        ];
        $form['form']['filters'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Search By Username, IP or Poll Question'),
            '#open' => true,
        ];
        $form['form']['filters']['text'] = [
            '#title' => $this->t('Enter Username, IP or Poll Question'),
            '#type' => 'search',
            '#attributes' => array('name' => 'ip', 'value' => $request_param),
        ];
        $form['form']['filters']['actions'] = [
            '#type' => 'actions'
        ];
        $form['form']['filters']['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Search'),
            'reset' => array(
                '#type' => 'submit',
                '#attributes' => array('class' => array('my-reset-button-class'), 'onclick' => 'javascript:jQuery(".form-search").val("");jQuery(this.form).find(\'#' . $submit_btn_id . '\').trigger(\'click\');return false;'),
                '#value' => t('Reset'),
                '#submit' => array('submission_list_reset')
            )
        ];
        //place the table in the form
        $form['table'] = array(
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array(
                'id' => 'bd-contact-table',
            ),
            "#empty" => $this->t("No Records Found!") // The message to be displayed if table is
        );
        //place pager
        $form['pager'] = array(
            '#type' => 'pager'
        );
        return $form;
    }
}

?>