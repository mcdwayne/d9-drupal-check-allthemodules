<?php

namespace Drupal\json_scanner_block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\json_scanner_block\DbStorage\DbActions;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * SettingsListController Class.
 */
class SettingsListController extends ControllerBase {

    protected $table_name = 'json_scanner_block';
    protected $delete_url = 'admin/config/content/json_scanner_block/delete';
    protected $edit_url = 'admin/config/content/json_scanner_block/edit';

    /**
     * Render a list of entries in the database.
     */
    public function entryList() {
        $content = [];

        $content['message'] = [
            '#markup' => $this->t('Generated list of all entries in the database. <br/>'.Link::fromTextAndUrl(t('Add JSON Source Detail'), Url::fromRoute('json_scanner_block.admin_settings'))->toString().'<br/>'.Link::fromTextAndUrl(t('Update JSON Source Datas'), Url::fromRoute('json_scanner_block.edit_data'))->toString()),
        ];

        $rows = [];
        $headers = [
            $this->t('Available Array'),
            $this->t('Json URL'),
            $this->t('Delete'),
        ];

        foreach ($entries = DbActions::load($this->table_name) as $entry) {
            // Sanitize each entry.
            
            //$entry->edit = Link::fromTextAndUrl(t('Edit'), Url::fromUri('internal:/'.$this->edit_url.'/'.$entry->id))->toString();
            $entry->delete = Link::fromTextAndUrl(t('Delete'), Url::fromUri('internal:/'.$this->delete_url.'/'.$entry->id))->toString();
            $entry->updated_variable = '{{json_scanner_block.'.$entry->name.'}}';
            
            //$rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry); //we will use this in future to map array with only plain text so no error can occur if html has exploit value
            $rows[] = (array) $entry;
        }

        //Filter the query here which fields you need to display.
        $rows = $this->getRequiredFields($rows, array('updated_variable', 'json_url', 'delete'));

        $content['table'] = [
            '#type' => 'table',
            '#header' => $headers,
            '#rows' => $rows,
            '#empty' => $this->t('No entries available.'),
        ];
        // Don't cache this page.
        $content['#cache']['max-age'] = 0;

        return $content;
    }

    /**
     * Filter the query based on field which needy.
     * 
     * @param array $rows
     * @param array $updated_rows
     * @return array
     */
    public function getRequiredFields(array $rows = [], array $updated_rows = [], array $customColumn = []) {
        $updated_row = false;
        foreach ($rows as $row_key => $row_value) {
            foreach ($updated_rows as $row) {
                $updated_row[$row_key][$row] = $row_value[$row];
            }

            foreach ($customColumn as $key => $val) {
                array_push($updated_row[$row_key][$key]);
                $updated_row[$row_key][$key] = $val;
            }
        }

        return $updated_row;
    }

    /**
     * return json if name found in db.
     * 
     * @param type $name
     * @return boolean
     */
    public function getJsonbyName($name = NULL) {
        $rows = [];
        foreach ($entries = DbActions::load($this->table_name) as $entry) {
            // Sanitize each entry.
            $rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);
        }

        //Filter the query here which fields you need to display.

        if ($rows != []) {
            $rows = $this->getRequiredFields($rows, array('name', 'json_data'));
            if (in_array($name, array_column($rows, 'name'))) { // search value in the array
                $jsonData = $rows[0]['json_data'];
                //$jsonStr = $jsonData->__toString();
                $jsonStr = $jsonData->jsonSerialize();
                return $jsonStr;
            }
        }

        return false;
    }

    /**
     * find json of given name (here name/title is used for finding json)
     * 
     * @param type $findData
     * @param type $findColumn
     * @return type
     */
    public function returnArrayJson($findData, $findColumn = 'name') {
        $jsonArray = DbActions::getSingleDatas($findData, $findColumn, 'json_data', $this->table_name);
        $jsonStr = $jsonArray[0]->json_data;
        return Json::decode($jsonStr);
    }

    /**
     * return array to use in template by hook_preprocess
     * 
     * @return type
     */
    public function availForTwig() {
        $jsonArrayValue = DbActions::getSingleDatas(1, 'avail_for_twig', 'json_data', $this->table_name, 'like');
        $jsonArrayKey = DbActions::getSingleDatas(1, 'avail_for_twig', 'name', $this->table_name, 'like');

        $i = 0;
        foreach ($jsonArrayValue as $jsonarr) {
            $jsonArrCollection[$jsonArrayKey[$i]->name] = Json::decode($jsonarr->json_data);
            $i++;
        }
        
        if(isset($jsonArrCollection)){
        return $jsonArrCollection;
        }
        return false;
    }
    
    /**
     * 
     * @param type $id
     * @return type
     */
    public function removeData($id){
        DbActions::delete($this->table_name,'id',$id);        
    }

}
