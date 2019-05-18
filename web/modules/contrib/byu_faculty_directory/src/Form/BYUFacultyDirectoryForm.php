<?php
/**
 * @file
 * Contains \Drupal\byu_faculty_directory\Form\BYUFacultyDirectoryForm
 *
 * Configuration form for the BYU Faculty Directory Module
 *
 * Overview of Functions:
 *
 * getFormId()                  -Form ID
 * getEditableConfigNames()     -Available settings
 * buildForm()                  -Create form render array
 * validateForm()               -Validate user input
 * submitForm()                 -Common form submit actions
 * submitParent()               -Parent module specific submit actions
 * submitChild()                -Child module specific submit actions
 * getFacultyFromOIT()          -Retrieve faculty data from OIT (Parent only)
 * getFacultyFromParent()       -Retrieve faculty data from parent module (Child only)
 * createContent()              -Create content from cached faculty data (Parent only)
 * storeSetting()               -Store specified configuration setting
 *
 */
namespace Drupal\byu_faculty_directory\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use function MongoDB\BSON\toJSON;
/*use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;*/

class BYUFacultyDirectoryForm extends ConfigFormBase {

    const ParentMode = 0;
    const ChildMode = 1;

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'byu_faculty_directory_admin_settings';
    }

    /**
     *
     *
     * @inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'byu_faculty_directory_form.settings',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Form Structure:
     *
     * --Module Settings--
     * Parent Mode/Child Mode
     *
     * --Profile Page Settings--
     * Upload Background Image
     *
     * --API Settings--
     * OIT API Key (Parent)
     * Parent URL (Child)
     * Module API Key
     *
     * --API Actions--
     * Fetch All Faculty (Parent)
     * Create Content (Parent)
     * Download Faculty Data (Child)
     * Download All/Download Filtered (Child)
     * List of Departments to Filter (Child)
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('byu_faculty_directory.config');
        $current_mode = \Drupal::config('byu_faculty_directory.config')->get('module_mode');


        //Module Mode Selection
        $form['module'] = array(
            '#type' => 'fieldset',
            '#title' => t('Module Settings'),
        );
        $form['module']['#tree'] = TRUE;
        $form['module']['mode'] = array(
            '#type' => 'radios',
            '#title' => t('Module Mode'),
            '#default_value' => $current_mode,
            '#options' => array(
                0 => t('Parent'),
                1 => t('Child'),
            ),
        );
        $form['module']['mode_description'] = array(
            '#markup' => '<i>Parent: Used by colleges to retrieve data from OIT and provide it to departments.<br>Child: Used by departments to recieve data from colleges.<br>Submitting the form after changing this option will only apply this change and no others.</i>',
        );


        $form['profile'] = array(
            '#type' => 'fieldset',
            '#title' => t('Faculty Profile Page Settings'),
        );
        $form['profile']['#tree'] = TRUE;
        $form['profile']['background_image'] = array(
            '#type' => 'managed_file',
            '#title' => t('Faculty Profile Background Image'),
            '#upload_validators' => [
                'file_validate_extensions' => ['gif png jpg jpeg'],
                'file_validate_size' => [25600000],
            ],
            '#widget' => 'imce',
            '#upload_location' => 'public://byu_faculty_directory/',
            '#preview' => TRUE,
            '#required' => FALSE,
            '#default_value' => $config->get('profile_background_image'),
        );


        $form['api'] = array(
            '#type' => 'fieldset',
            '#title' => t('API Settings'),
        );
        $form['api']['#tree'] = TRUE;

        //Parent Mode
        if ($current_mode == BYUFacultyDirectoryForm::ParentMode) {
            $form['api']['oit_api_key'] = array(
                '#type' => 'textfield',
                '#title' => t('API Key for OIT Data Retrieval'),
                '#default_value' => $config->get('oit_api_key'),
            );
            $form['api']['dept_api_key'] = array(
                '#type' => 'textfield',
                '#title' => t('API Key for Child REST API'),
                '#default_value' => $config->get('dept_api_key'),
            );
            $form['api']['proxy_server_label'] = array(
                '#markup' => '<b><u>Proxy Server Settings</u></b><br>Connection settings for a whitelisted proxy server (for retrieving profile pictures and CVs):<br>',
            );
            $form['api']['oit_proxy_url'] = array(
                '#type' => 'textfield',
                '#title' => t('Hostname/URL of Proxy Server'),
                '#default_value' => $config->get('oit_proxy_url'),
            );
            $form['api']['oit_proxy_url_label'] = array(
                '#markup' => '<i>Hostname only, exclude "http"</i><br>',
            );
            $form['api']['oit_proxy_port'] = array(
                '#type' => 'number',
                '#title' => t('Port for Proxy URL'),
                '#default_value' => $config->get('oit_proxy_port'),
            );
            $form['api']['oit_proxy_username'] = array(
                '#type' => 'textfield',
                '#title' => t('Username for Proxy Server'),
                '#default_value' => $config->get('oit_proxy_username'),
            );
            $form['api']['oit_proxy_password'] = array(
                '#type' => 'password',
                '#title' => t('Password for Proxy Server'),
                '#default_value' => $config->get('oit_proxy_password'),
            );
            $form['api']['oit_proxy_password_label'] = array(
                '#markup' => '<i>If the password field is empty and you\'ve previously saved a password, leaving this empty will use the saved password.</i><br>',
            );

        }
        else if ($current_mode == BYUFacultyDirectoryForm::ChildMode) {
            $form['api']['parent_url'] = array(
                '#type' => 'textfield',
                '#title' => t('Base URL of Parent Site'),
                '#default_value' => $config->get('parent_url'),
            );
            $form['api']['parent_url_label'] = array(
                '#markup' => '<i>Include appropriate prefix and trailing slash (e.g. \'http://example.com/\')  </i><br>',
            );
            $form['api']['dept_api_key'] = array(
                '#type' => 'textfield',
                '#title' => t('API Key for Parent REST API'),
                '#default_value' => $config->get('dept_api_key'),
            );
        }

        $form['content'] = array(
            '#type' => 'fieldset',
            '#title' => t('API Actions'),
        );
        $form['content']['#tree'] = TRUE;

        if ($current_mode == BYUFacultyDirectoryForm::ParentMode) {
            $form['content']['fetch_all_faculty'] = array(
                '#type' => 'checkbox',
                '#title' => t('Download all faculty data from the OIT API'),
                '#default_value' => $config->get('fetch_all_faculty'),
            );
            $form['content']['fetch_all_faculty_label'] = array(
                '#markup' => '<i><em>WARNING:</em> This will take a LONG time!</i><br>',
            );

            /*
            //Dev/Testing -
            //Enable this option to just create content from cache and not download any new data from OIT.

            $form['content']['fetch_all_faculty_label'] = array(
                '#markup' => '<i>This only downloads from OIT and does not create content. Use the following option to create from the downloaded data.</i><br>',
            );
            $form['content']['create_content'] = array(
                '#type' => 'checkbox',
                '#title' => t('Create content from cached faculty data'),
                '#default_value' => $config->get('create_content'),
            );

            */
        }
        else if ($current_mode == BYUFacultyDirectoryForm::ChildMode) {

            $form['content']['fetch'] = array(
                '#type' => 'checkbox',
                '#title' => t('Download faculty data from the Parent Module'),
            );
            $form['content']['fetch_type'] = array(
                '#type' => 'radios',
                '#title' => t('Type of Data to Download'),
                '#default_value' => 0,
                '#options' => array(
                    0 => t('All Faculty'),
                    1 => t('Specific Faculty'),
                ),
            );
            $form['content']['filtered_departments'] = array(
                '#type' => 'textfield',
                '#title' => t('Department Names to Filter By'),
            );
            $form['content']['filtered_departments_label'] = array(
                '#markup' => '<i>Comma separated list of department names for the "Specific Faculty" option. Do not include spaces before and after comma.</i><br>',
            );
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state){
        //Did we change modes? If so, ignore all other validation
        $current_mode = \Drupal::config('byu_faculty_directory.config')->get('module_mode');
        $new_mode = $form_state->getValue(array('module','mode'));
        if ($current_mode != $new_mode) {
            return;
        }

        if ($current_mode == BYUFacultyDirectoryForm::ParentMode) {
            $fetch_all = $form_state->getValue(array('content', 'fetch_all_faculty'));
            if ($fetch_all && strlen($form_state->getValue(array('api', 'oit_api_key'))) < 1){
                $form_state->setErrorByName('api][oit_api_key', $this->t('Fetching from OIT requires an OIT API key.'));
            }
            if ($fetch_all && strlen($form_state->getValue(array('api', 'oit_proxy_url'))) < 1){
                $form_state->setErrorByName('api][oit_proxy_url', $this->t('Fetching from OIT requires a proxy URL.'));
            }
            $port = $form_state->getValue(array('api', 'oit_proxy_port'));
            if ($fetch_all && (0 > $port || 65535 < $port)){
                $form_state->setErrorByName('api][oit_proxy_port', $this->t('Fetching from OIT requires a proxy port.'));
            }
            if ($fetch_all && strlen($form_state->getValue(array('api', 'oit_proxy_username'))) < 1){
                $form_state->setErrorByName('api][oit_proxy_username', $this->t('Fetching from OIT requires a proxy username'));
            }
            if ($fetch_all && strlen(\Drupal::config('byu_faculty_directory.config')->get('oit_proxy_password')) < 1) {
                if (strlen($form_state->getValue(array('api', 'oit_proxy_password'))) < 1) {
                    $form_state->setErrorByName('api][oit_proxy_password', $this->t('Fetching from OIT requires a proxy password.'));
                }
            }
            if (strlen($form_state->getValue(array('api', 'dept_api_key'))) < 1) {
                $form_state->setErrorByName('api][dept_api_key', $this->t('Please enter a value in the Child REST API Key field.'));
            }
        }
        else if ($current_mode == BYUFacultyDirectoryForm::ChildMode) {
            $fetch = $form_state->getValue(array('content', 'fetch'));
            $fetch_type = $form_state->getValue(array('content', 'fetch_type'));
            if ($fetch) {
                if (strlen($form_state->getValue(array('api', 'dept_api_key'))) < 1) {
                    $form_state->setErrorByName('api][dept_api_key', $this->t('Fetching from the parent module requires a REST API key.'));
                }
                if (strlen($form_state->getValue(array('api', 'parent_url'))) < 1) {
                    $form_state->setErrorByName('api][parent_url', $this->t('Fetching from the parent module requires a parent URL.'));
                }
            }
            if ($fetch && $fetch_type && strlen($form_state->getValue(array('content', 'filtered_departments'))) < 1) {
                $form_state->setErrorByName('content][filtered_departments', $this->t('Fetching filtered faculty requires a list of departments to filter by.'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state){
        //If mode has changed, do not do anything else
        $current_mode = \Drupal::config('byu_faculty_directory.config')->get('module_mode');
        $new_mode = $form_state->getValue(array('module', 'mode'));
        if ($current_mode != $new_mode) {
            $this->storeSetting('module_mode', $new_mode);
            drupal_set_message(t('Module mode updated!'), 'status');
            return;
        }

        //Common to Parent and Child modes

        $old_dept_api_key = \Drupal::config('byu_faculty_directory.config')->get('dept_api_key');
        $new_dept_api_key = $form_state->getValue(array('api', 'dept_api_key'));
        //Department API key updated
        if (strcmp($old_dept_api_key, $new_dept_api_key) !== 0) {
            $this->storeSetting('dept_api_key', $new_dept_api_key);
            drupal_set_message(t('Module REST API key updated!'), 'status');
        }

        $old_profile_background_image = \Drupal::config('byu_faculty_directory.config')->get('profile_background_image');
        $profile_background_image = $form_state->getValue(array('profile', 'background_image'));

        //Profile background image updated
        if (strcmp($old_profile_background_image[0], $profile_background_image[0]) !== 0){
            // Load the object of the file by its fid
            $file = \Drupal\file\Entity\File::load($profile_background_image[0]);
            if ($file != null) {
                // Set the status flag permanent of the file object.
                if (!empty($file)) {
                    $file->setPermanent();
                    // Save the file in the database.
                    $file->save();
                    $file_usage = \Drupal::service('file.usage');
                    $file_usage->add($file, 'byu_faculty_directory', 'file', \Drupal::currentUser()->id());
                }
                $this->storeSetting('profile_background_image', $profile_background_image);
                drupal_set_message(t('Profile background image updated! Clear cache to display changes.'), 'status');
                drupal_set_message(t('New background image URL: ').$file->url('canonical'), 'status');
            }
        }

        //Process unique parent and child mode submissions
        if ($current_mode == BYUFacultyDirectoryForm::ParentMode) {
            $this->submitParent($form, $form_state);
        }
        else if ($current_mode == BYUFacultyDirectoryForm::ChildMode){
            $this->submitChild($form, $form_state);
        }
    }

    private function submitParent(array &$form, FormStateInterface &$form_state) {
        $fetch = $form_state->getValue(array('content', 'fetch_all_faculty'));
        $create = $form_state->getValue(array('content', 'create_content'));
        $old_oit_api_key = \Drupal::config('byu_faculty_directory.config')->get('oit_api_key');
        $new_oit_api_key = $form_state->getValue(array('api', 'oit_api_key'));
        $old_oit_proxy_url = \Drupal::config('byu_faculty_directory.config')->get('oit_proxy_url');
        $new_oit_proxy_url = $form_state->getValue(array('api', 'oit_proxy_url'));
        $old_oit_proxy_port = \Drupal::config('byu_faculty_directory.config')->get('oit_proxy_port');
        $new_oit_proxy_port = $form_state->getValue(array('api', 'oit_proxy_port'));
        $old_oit_proxy_usr = \Drupal::config('byu_faculty_directory.config')->get('oit_proxy_username');
        $new_oit_proxy_usr = $form_state->getValue(array('api', 'oit_proxy_username'));
        $old_oit_proxy_pass = \Drupal::config('byu_faculty_directory.config')->get('oit_proxy_password');
        $new_oit_proxy_pass = $form_state->getValue(array('api', 'oit_proxy_password'));

        if (strcmp($old_oit_api_key, $new_oit_api_key) !== 0) {
            $this->storeSetting('oit_api_key', $new_oit_api_key);
            drupal_set_message(t('OIT API key updated!'), 'status');
        }
        if (strcmp($old_oit_proxy_url, $new_oit_proxy_url) !== 0) {
            $this->storeSetting('oit_proxy_url', $new_oit_proxy_url);
            drupal_set_message(t('OIT Proxy URL updated!'), 'status');
        }
        if ($old_oit_proxy_port !== $new_oit_proxy_port) {
            $this->storeSetting('oit_proxy_port', $new_oit_proxy_port);
            drupal_set_message(t('OIT Proxy Port updated!'), 'status');
        }
        if (strcmp($old_oit_proxy_usr, $new_oit_proxy_usr) !== 0) {
            $this->storeSetting('oit_proxy_username', $new_oit_proxy_usr);
            drupal_set_message(t('OIT Proxy Username updated!'), 'status');
        }
        if (!(strlen($old_oit_proxy_pass) > 1 && strlen($new_oit_proxy_pass) < 1 )){
            if (strcmp($old_oit_proxy_pass, $new_oit_proxy_pass) !== 0) {
                $this->storeSetting('oit_proxy_password', $new_oit_proxy_pass);
                drupal_set_message(t('OIT Proxy Password updated!'), 'status');
            }
        }

        if ($fetch == 1) {
            try {
                $this->getFacultyFromOIT();
                drupal_set_message(t('Successfully retrieved faculty data from OIT!'), 'status');
            } catch (\Exception $e) {
                drupal_set_message($e->getMessage(), 'error');
            }
        }
        if ($create == 1) {
            try {
                $this->createContent();
                drupal_set_message(t('Successfully created content from cached faculty data!'), 'status');
            } catch (\Exception $e) {
                drupal_set_message($e->getMessage(), 'error');
            }
        }
    }

    private function submitChild(array &$form, FormStateInterface &$form_state) {
        $old_url = \Drupal::config('byu_faculty_directory.config')->get('parent_url');
        $new_url = $form_state->getValue(array('api', 'parent_url'));

        if (strcmp($old_url, $new_url) !== 0) {
            $this->storeSetting('parent_url', $new_url);
            drupal_set_message(t('Parent Module URL updated!'), 'status');
        }

        $fetch = $form_state->getValue(array('content', 'fetch'));

        if ((bool)$fetch) {
            $fetch_type = $form_state->getValue(array('content', 'fetch_type'));
            try {
                $departments = $form_state->getValue(array('content', 'filtered_departments'), '');
                $this->getFacultyFromParent($fetch_type, $departments);
                drupal_set_message(t('Successfully retrieved faculty data from parent module!'), 'status');
            } catch (\Exception $e) {
                drupal_set_message($e->getMessage(), 'error');
            }
        }
    }

    /**
     * Gets ALL faculty members from OIT API and stores them in a file
     * Used by parent mode
     * @throws \Exception upon failed connection to OIT database
     */
    private function getFacultyFromOIT(){
        $api_key = \Drupal::config('byu_faculty_directory.config')->get('oit_api_key');

        try {
            $result = file_get_contents("https://ws.byu.edu/services/facultyProfile/faculty?applicationKey=".$api_key);
            if ($result === false) {
                throw new \Exception('getFacultyFromOIT(): 500 Response Recieved - Invalid Application Key. No changes made to cached data');
            }
        } catch (\Exception $e) {
            throw new \Exception('getFacultyFromOIT(): Destination Unreachable - No changes made to cached data. Check application key and status of ws.byu.edu.');
        }

        $data = new \SimpleXMLElement($result);
        $netids = array();
        $netid_attribute = 'username';

        //Get all netids
        $gottem = False;
        foreach($data->Record as $record) {

            foreach($record->children('dmd', true)->IndexEntry as $indexentry){

                //$netids[] = (string)$record->attributes()->$netid_attribute;

                //Filter by department (for testing, reduces download/parsing time)

                if ((string)($indexentry->attributes()->{'text'}) === 'ENG: Chemical Engineering') {
                    $netids[] = (string)$record->attributes()->$netid_attribute;
                    break;
                }
                if ((string)($indexentry->attributes()->{'text'}) === 'ENG: Mechanical Engineering') {
                    $netids[] = (string)$record->attributes()->$netid_attribute;
                    break;
                }
                if ((string)($indexentry->attributes()->{'text'}) === 'ENG: Civil and Environmental Engineering') {
                    $netids[] = (string)$record->attributes()->$netid_attribute;
                    break;
                }
                if ((string)($indexentry->attributes()->{'text'}) === 'ENG: Electrical and Computer Engineering') {
                    $netids[] = (string)$record->attributes()->$netid_attribute;
                    break;
                }
                if ((string)($indexentry->attributes()->{'text'}) === 'ENG: Technology'){
                    $netids[] = (string)$record->attributes()->$netid_attribute;
                    break;
                }

            }
        }

        foreach($netids as $netid){
            $netid_data = file_get_contents('https://ws.byu.edu/services/facultyProfile/faculty/'.$netid.'/profile/?applicationKey='.$api_key);
            $facultyProfile = simplexml_load_string($netid_data);
            try {
                $this->createSingleFacultyMember($facultyProfile);
            }catch(\Exception $e){
                throw new \Exception($e->getMessage());
            }

        } 

        /*$client = new Client(['verify' => false]);
        $requests = function ($items, $key) {
            foreach ($items as $item) {
                $uri = 'https://ws.byu.edu/services/facultyProfile/faculty/' . $item . '/profile/?applicationKey=' . $key;
                yield new Request('GET', $uri);
            }
        };
        $pool = new Pool($client, $requests($netids, $api_key), [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) {
                $profile = simplexml_load_string($response->getBody());
                try {
                    $this->createSingleFacultyMember($profile);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            },
            'rejected' => function ($reason, $index) {
                throw new \Exception($reason);
            },
        ]); */

        /*
         * For Testing - Save data into an XML file, load it with createContent()
         *

        $file_header = '<?xml version="1.0"?>'."\n<facultyProfiles>\n";
        $filename = drupal_get_path('module','byu_faculty_directory').'/data/all_faculty_cache.xml';
        file_put_contents($filename,$file_header);

        foreach($netids as $netid){
            $netid_data = file_get_contents('https://ws.byu.edu/services/facultyProfile/faculty/'.$netid.'/profile/?applicationKey='.$api_key);
            //Strip <?xml ... ?\> so we don't have duplicates
            $netid_data = preg_replace("/<\?xml.*\?>/i", "", $netid_data);
            $netid_data = $netid_data."\n";
            file_put_contents($filename,$netid_data,FILE_APPEND);
        }
        $file_footer = "\n</facultyProfiles>";
        file_put_contents($filename,$file_footer,FILE_APPEND);

        $this->createContent();*/
    }

    /**
     * Create content (via Faculty Member content type) for each cached faculty member
     * Used by parent mode
     * For Testing/Debugging
     *
    private function createContent(){
    $filename = drupal_get_path('module','byu_faculty_directory').'/data/all_faculty_cache.xml';
    $data = simplexml_load_file($filename);

    foreach($data->facultyProfile as $facultyProfile){
    $this->createSingleFacultyMember($facultyProfile);
    }
    }
     */


    /**
     * Create content (via Faculty Member content type) for a single faculty profile (in XML format)
     * Used by parent mode
     */
    private function createSingleFacultyMember(\SimpleXMLElement &$facultyProfile) {
        //Currently unused - preferred name, teaching interests
        $firstname = $facultyProfile->Record->PCI->PFNAME;
        $teaching_interests = $facultyProfile->Record->PCI->TEACHING_INTERESTS;

        set_time_limit(45);
        //Name, Research Bio, Website, Netid
        //$firstname = $facultyProfile->Record->PCI->FNAME;
        $lastname = $facultyProfile->Record->PCI->LNAME;
        $name = $facultyProfile->Record->PCI->FNAME." ".$facultyProfile->Record->PCI->LNAME;
        $research_interests = $facultyProfile->Record->PCI->RESEARCH_INTERESTS;

        $bio = $facultyProfile->Record->PCI->BIO;
        $website = $facultyProfile->Record->PCI->WEBSITE;

        //Parse department
        $department = '';

        foreach($facultyProfile->Record->children('dmd', true)->IndexEntry as $indexentry) {
            if ((string)($indexentry->attributes()->{'indexKey'}) === 'DEPARTMENT') {
                $department = ((string)($indexentry->attributes()->{'text'}));
                break;
            }
        }

        $netid_attribute = 'username';
        $netid = (string)$facultyProfile->Record->attributes()->$netid_attribute;

        //Emeritus Status
        $status = $facultyProfile->Record->PCI->EMP_STATUS;
        if (strcmp($status, "Active") == 0) {
            $active = TRUE;
        } else {
            $active = FALSE;
        }

        //Rank, Adjunct Status
        $title = $facultyProfile->Record->PCI->RANK;
        if (strcmp($status, "Adjunct") == 0) {
            $adjunct = 'Yes';
        } else {
            $adjunct = 'No';
        }

        //Email, office, phone
        $email = $facultyProfile->Record->PCI->EMAIL;
        $office = $facultyProfile->Record->PCI->ADDRESS;
        $phone = '(' . $facultyProfile->Record->PCI->OPHONE1 . ') ' . $facultyProfile->Record->PCI->OPHONE2 . '-' . $facultyProfile->Record->PCI->OPHONE3;
        //Remove Provo, UT 84062 from office
        $office = preg_replace("/\s*,\s*provo\s*,*\s*ut\s*,*\s*84602/i", "", $office);

        //Awards
        //Name, Organization (Year) - Description
        $awards = "";
        foreach ($facultyProfile->Record->AWARDHONOR as $entry) {
            $award_entry = $entry->NAME . ', ' . $entry->ORG . ' (' . $entry->DTY_END . ')';
            if ((string)$entry->DESC) {
                $award_entry = $award_entry . ' - ' . $entry->DESC;
            }
            $awards = $awards."<li>$award_entry</li><br>";
        }

        //Degrees
        //Degree in Major, School, Location (Year)
        //Dissertation: ___
        //Area of Study: ___
        $education = "<ul><br>";
        foreach ($facultyProfile->Record->EDUCATION as $entry) {
            $education_entry = $entry->DEGREE_NAME . ' in ' . $entry->MAJOR . ', ' . $entry->SCHOOL . ', ' . $entry->LOCATION . ' (' . $entry->YR_COMP . ")";
            //If there is a dissertation present
            if ((string)$entry->DISSTITLE) {
                $education_entry = $education_entry . "<br>Dissertation: " . $entry->DISSTITLE;
            }
            //If there is an area of study present
            if ((string)$entry->SUPPAREA) {
                $education_entry = $education_entry . "<br>Area of Study: " . $entry->SUPPAREA;
            }
            $education = $education."<li>$education_entry</li><br>";
        }
        $education = $education."</ul>";

        //Committees
        //Role, Organization, Month Year to Month Year
        $committees = "<ul><br>";
        foreach ($facultyProfile->Record->SERVICE_DEPARTMENT as $entry) {
            $committee_entry = $entry->ROLE . ', ' . $entry->ORG;
            //If there's a starting year present
            if ((string)$entry->DTY_START) {
                $committee_entry = $committee_entry . ',';
                //If there's a starting month present
                if ((string)$entry->DTM_START) {
                    $committee_entry = $committee_entry . ' ' . $entry->DTM_START;
                }
                $committee_entry = $committee_entry . ' ' . $entry->DTY_START;
                //If there's an ending year present
                if ((string)$entry->DTY_END) {
                    $committee_entry = $committee_entry . ' to';
                    //If there's an ending month present
                    if ((string)$entry->DTM_END) {
                        $committee_entry = $committee_entry . ' ' . $entry->DTM_END;
                    }
                    $committee_entry = $committee_entry . ' ' . $entry->DTY_END;
                }
            }
            $committees = $committees."<li>$committee_entry</li><br>";
        }
        $committees = $committees."</ul>";

        //Organizations (1)
        //Name, Year to Year (Scope)
        //Description
        $organizations = "<ul><br>";
        foreach ($facultyProfile->Record->MEMBER as $entry) {
            $organization_entry = $entry->NAME;
            if ((string)$entry->DTY_START) {
                $organization_entry = $organization_entry . ', ' . $entry->DTY_START;
                if ((string)$entry->DTY_END) {
                    $organization_entry = $organization_entry . ' to ' . $entry->DTY_END;
                }
            }
            $organization_entry = $organization_entry . ' (' . $entry->SCOPE . ')';
            if ((string)$entry->DESC) {
                $organization_entry = $organization_entry . "<br>" . $entry->DESC;
            }
            $organizations = $organizations."<li>$organization_entry</li><br>";
        }

        //Organizations (2)
        //Role, Organization (Elected/Appointed, Compensation), Month Year to Month Year (Audience)
        //Description
        foreach ($facultyProfile->Record->SERVICE_PROFESSIONAL as $entry) {
            //If "role" is other, we need to get the actual role
            $org_role = $entry->ROLE;
            if (strcmp((string)$org_role, "Other") == 0) {
                $org_role = $entry->ROLEOTHER;
            }
            $org_elec_app = '';
            if (strcmp((string)$org_elec_app, "No, neither") == 0) {
                $org_elec_app = "";
            } elseif (strcmp((string)$org_elec_app, "Yes, appointed") == 0) {
                $org_elec_app = "Appointed";
            } else {
                $org_elec_app = "Elected";
            }
            $org_compensation = $entry->COMPENSATED;
            if (strcmp((string)$org_compensation, "Pro Bono") != 0) {
                $org_compensation = "";
            }
            $organization_entry = $org_role . ', ' . $entry->ORG;
            if (!empty((string)$org_elec_app)) {
                $organization_entry = $organization_entry . ' (' . $org_elec_app;
                if (!empty((string)$org_compensation)) {
                    $organization_entry = $organization_entry . ', ' . $org_compensation;
                }
                $organization_entry = $organization_entry . ')';
            } elseif (!empty((string)$org_elec_app)) {
                if (!empty((string)$org_compensation)) {
                    $organization_entry = $organization_entry . ' (' . $org_compensation . ')';
                }
            }
            //If there's a starting year present
            if ((string)$entry->DTY_START) {
                $organization_entry = $organization_entry . ',';
                //If there's a starting month present
                if ((string)$entry->DTM_START) {
                    $organization_entry = $organization_entry . ' ' . $entry->DTM_START;
                }
                $organization_entry = $organization_entry . ' ' . $entry->DTY_START;
                //If there's an ending year present
                if ((string)$entry->DTY_END) {
                    $organization_entry = $organization_entry . ' to';
                    //If there's an ending month present
                    if ((string)$entry->DTM_END) {
                        $organization_entry = $organization_entry . ' ' . $entry->DTM_END;
                    }
                    $organization_entry = $organization_entry . ' ' . $entry->DTY_END;
                }
            }
            if ((string)$entry->AUDIENCE) {
                $organization_entry = $organization_entry . ' (' . $entry->AUDIENCE . ')';
            }
            if ((string)$entry->DESC) {
                $organization_entry = $organization_entry . "\n" . $entry->DESC;
            }
            $organizations = $organizations."<li>$organization_entry</li><br>";
        }
        $organizations = $organizations."</ul>";


        //Publications (all on same line)
        //Lastname, Firstname, Lastname, Firstname, ... & Lastname, Firstname. (Day Month Year). Title. Type. Secondary Title, Publisher, City/State, Country.
        //Volume (Issue), Page, doi: DOI isbn: ISBN issn: ISSN
        $publications = "<ul><br>";
        foreach ($facultyProfile->Record->INTELLCONT as $entry) {
            //Get the name of each author
            //Format: Lastname, Firstname Initial
            $pub_authors = array();
            foreach ($entry->INTELLCONT_AUTH as $author) {
                $author_name = $author->LNAME . ", " . $author->FNAME;
                if ((string)$author->MNAME) {
                    $author_name = $author_name . " " . $author->MNAME;
                }
                $pub_authors[] = $author_name;
            }
            $pub_entry = '';
            $author_count = count($pub_authors);
            foreach ($pub_authors as $index => $auth_name) {
                if ($index != 0 && $index == $author_count - 1) {
                    $pub_entry = $pub_entry . ', & ';
                } elseif ($index != 0) {
                    $pub_entry = $pub_entry . ', ';
                }
                $pub_entry = $pub_entry . $auth_name;
            }
            $pub_entry = $pub_entry . '. (';
            if ((string)$entry->DTD_PUB) {
                $pub_entry = $pub_entry . $entry->DTD_PUB . ' ';
            }
            if ((string)$entry->DTM_PUB) {
                $pub_entry = $pub_entry . $entry->DTM_PUB . ' ';
            }
            if ((string)$entry->DTY_PUB) {
                $pub_entry = $pub_entry . $entry->DTY_PUB . '). ';
            } else {
                $pub_entry = $pub_entry . 'No date available). ';
            }
            $pub_entry = $pub_entry . $entry->TITLE . '. ' . $entry->CONTYPE . '. ';
            if ((string)$entry->TITLE_SECONDARY) {
                $pub_entry = $pub_entry . $entry->TITLE_SECONDARY . ', ';
            }
            $pub_entry = $pub_entry . $entry->PUBLISHER;
            if ((string)$entry->PUBCTYST) {
                $pub_entry = $pub_entry . ', ' . $entry->PUBCTYST;
            }
            if ((string)$entry->PUBCNTRY) {
                $pub_entry = $pub_entry . ', ' . $entry->PUBCNTRY;
            }
            $pub_entry = $pub_entry . '. ';
            if ((string)$entry->VOLUME) {
                $pub_entry = $pub_entry . $entry->VOLUME . ' ';
            }
            if ((string)$entry->ISSUE) {
                $pub_entry = $pub_entry . '(' . $entry->ISSUE . ') ';
            }
            if ((string)$entry->PAGENUM) {
                $pub_entry = $pub_entry . ', ' . $entry->PAGENUM . '. ';
            }
            if ((string)$entry->DOI) {
                $pub_entry = $pub_entry . 'doi:' . $entry->DOI . ' ';
            }
            if ((string)$entry->ISBN) {
                $pub_entry = $pub_entry . 'isbn:' . $entry->ISBN . ' ';
            }
            if ((string)$entry->ISSN) {
                $pub_entry = $pub_entry . 'issn:' . $entry->ISSN . ' ';
            }
            $publications = $publications."<li>$pub_entry</li><br>";
        }
        $publications = $publications."</ul>";

        //Courses Taught
        //Prefix Course Suffix Section - Term Year
        //e.g. ME EN 497R Section 026 - Fall 2017
        $courses = "<ul><br>";
        foreach ($facultyProfile->Record->SCHTEACH as $entry) {

            //Ignore everything before last year
            $curr_year = date('Y');
            if (($curr_year - $entry->TYY_TERM) <= 1){
                $course_entry = $entry->COURSEPRE . ' ' . $entry->COURSENUM;
                if ((string)$entry->COURSENUM_SUFFIX) {
                    $course_entry = $course_entry . $entry->COURSENUM_SUFFIX;
                }
                $course_entry = $course_entry . ' Section ' . $entry->SECTION . ' - ' . $entry->TYT_TERM . ' ' . $entry->TYY_TERM;
                $courses = $courses."<li>$course_entry</li><br>";
            }
        }
        $courses = $courses."</ul>";


        //CV and Image
        $vita_path = '';
        $photo_path = '';
        $photo_retrieved = False;
        $cv_retrieved = False;
        if ((string)$facultyProfile->Record->PCI->VITA_VISIBLE == 'true') {
            $vita_path = (string)$facultyProfile->Record->PCI->UPLOAD_VITA;
            if (strlen($vita_path) < 1){
                $cv_retrieved = False;
            }
            else {
                $filetype = pathinfo($vita_path)['extension'];
                $vita_filename = 'public://' . $netid . '_vita.' . $filetype;
                try {
                    file_put_contents($vita_filename, $this->getFileThroughProxy($vita_path));
                }catch (\Exception $e){
                    $msg = 'One or more faculty members failed to download. Proxy error - check proxy username, password, hostname, and port. More information: '.$e->getMessage();
                    throw new \Exception($msg);
                }
                $cv_retrieved = True;
            }
        }
        if ((string)$facultyProfile->Record->PCI->PHOTO_VISIBLE == 'true') {
            $photo_path = (string)$facultyProfile->Record->PCI->UPLOAD_PHOTO;
            if (strlen($photo_path) < 1){
                $photo_retrieved = False;
            }
            else {
                $filetype = pathinfo($photo_path)['extension'];
                $photo_filename = 'public://'.$netid.'_photo.'.$filetype;
                try {
                    file_put_contents($photo_filename, $this->getFileThroughProxy($photo_path));
                }catch (\Exception $e){
                    $msg = 'One or more faculty members failed to download. Proxy error - check proxy username, password, hostname, and port. More information: '.$e->getMessage();
                    throw new \Exception($msg);
                }
                $photo_retrieved = True;
            }
        }



        //Missing:
        //Profile Image
        //CV
        //"custom content"
        //"custom title"
        // 'website' as links? or have that manually entered?
        //office hours manual?
        //research short?
        //students?

        //need to add fields:
        //emeritus/adjunct
        //teaching interests
        //organizations



        //See if the faculty member already exists in the database
        $uid_query = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['field_byu_f_d_uid' => $netid]);

        //Found in database!
        if ($node = reset($uid_query)) {
            //Check the manual Override field for each field that OIT manages
            //If Override field is false, Override. If true, do nothing.

            if (!$node->field_byu_f_d_awards_or->value){
                $node->field_byu_f_d_awards = $awards;
            }

            if (!$node->field_byu_f_d_biography_or->value){
                $node->field_byu_f_d_biography = $bio;
            }

            if (!$node->field_byu_f_d_committees_or->value){
                $node->field_byu_f_d_committees = $committees;
            }

            if (!$node->field_byu_f_d_courses_or->value){
                $node->field_byu_f_d_courses = $courses;
            }

            if (!$node->field_byu_f_d_email_or->value){
                $node->field_byu_f_d_email = $email;
            }

            if (!$node->field_byu_f_d_active_or->value) {
                $node->field_byu_f_d_active = $active;
            }


            if (!$node->field_byu_f_d_first_name_or->value){
                $node->field_byu_f_d_first_name = $firstname;
            }

            if (!$node->field_byu_f_d_last_name_or->value){
                $node->field_byu_f_d_last_name = $lastname;
            }

            if (!$node->field_byu_f_d_links_or->value){
                $node->field_byu_f_d_links = $website;
            }

            if (!$node->field_byu_f_d_office_location_or->value){
                $node->field_byu_f_d_office_location = $office;
            }

            if (!$node->field_byu_f_d_phone_number_or->value){
                $node->field_byu_f_d_phone_number = $phone;
            }

            if (!$node->field_byu_f_d_publications_or->value){
                $node->field_byu_f_d_publications = $publications;
            }

            if (!$node->field_byu_f_d_research_long_or->value){
                $node->field_byu_f_d_research_long = $research_interests;
            }

            if (!$node->field_byu_f_d_education_or->value){
                $node->field_byu_f_d_education = $education;
            }

            if (!$node->field_byu_f_d_title_or->value){
                $node->field_byu_f_d_title = $title;
            }
            if (!$node->field_byu_f_d_department_or->value){
                $node->field_byu_f_d_department = $department;
            }
            if (!$node->field_byu_f_d_cv_or->value && $cv_retrieved){
                $files = \Drupal::entityTypeManager()
                    ->getStorage('file')
                    ->loadByProperties(['uri' => $vita_filename]);
                $file = reset($files);
                if (!$file){
                    $file = \Drupal\file\Entity\File::create([
                        'uri' => $vita_filename,
                    ]);
                    $file->save();
                }
                $node->field_byu_f_d_cv[] = [
                    'target_id' => $file->id(),
                ];
            }
            if (!$node->field_byu_f_d_profile_image_or->value && $photo_retrieved){
                $files = \Drupal::entityTypeManager()
                    ->getStorage('file')
                    ->loadByProperties(['uri' => $photo_filename]);
                $file = reset($files);
                if (!$file){
                    $file = \Drupal\file\Entity\File::create([
                        'uri' => $photo_filename,
                    ]);
                    $file->save();
                }
                $node->field_byu_f_d_profile_image[] = [
                    'target_id' => $file->id(),
                    'alt' => $name,
                    'title' => $name
                ];
            }
            $node->save();
        }
        else {
            $create_array = array();
            $create_array['type'] = 'byu_faculty_member';
            $create_array['langcode'] = 'en';
            $create_array['field_byu_f_d_awards'] = $awards;
            $create_array['field_byu_f_d_biography'] = $bio;
            $create_array['field_byu_f_d_committees'] = $committees;
            $create_array['field_byu_f_d_courses'] = $courses;
            $create_array['field_byu_f_d_email'] = $email;
            $create_array['field_byu_f_d_active'] = $active;
            $create_array['field_byu_f_d_first_name'] = $firstname;
            $create_array['field_byu_f_d_last_name'] = $lastname;
            $create_array['field_byu_f_d_links'] = $website;

            $create_array['field_byu_f_d_office_location'] = $office;
            $create_array['field_byu_f_d_phone_number'] = $phone;
            $create_array['field_byu_f_d_publications'] = $publications;
            $create_array['field_byu_f_d_research_long'] = $research_interests;
            $create_array['field_byu_f_d_education'] = $education;
            $create_array['field_byu_f_d_title'] = $title;
            $create_array['title'] = $name;
            $create_array['field_byu_f_d_uid'] = $netid;
            $create_array['field_byu_f_d_department'] = $department;

            if ($cv_retrieved){
                $file = \Drupal\file\Entity\File::create([
                    'uri' => $vita_filename,
                ]);
                $file->save();
                $create_array['field_byu_f_d_cv'] = [
                    'target_id' => $file->id(),
                ];

            }
            if ($photo_retrieved){
                $file = \Drupal\file\Entity\File::create([
                    'uri' => $photo_filename,
                ]);
                $file->save();
                $create_array['field_byu_f_d_profile_image'] = [
                    'target_id' => $file->id(),
                    'alt' => $name,
                    'title' => $name
                ];
            }

            //Future fields
            //$create_array['field_byu_f_d_research_short'] =
            //$create_array['field_byu_f_d_students'] =
            //$create_array['field_byu_f_d_office_hours'] =

            $node = \Drupal\node\Entity\Node::create($create_array);
            $node->save();
        }
    }

    private function getFileThroughProxy($path){
        $proxy_url = \Drupal::config('byu_faculty_directory.config')->get('oit_proxy_url');
        $proxy_port = \Drupal::config('byu_faculty_directory.config')->get('oit_proxy_port');
        $proxy_username = \Drupal::config('byu_faculty_directory.config')->get('oit_proxy_username');
        $proxy_password = \Drupal::config('byu_faculty_directory.config')->get('oit_proxy_password');

        $url = 'https://fp-vita.byu.edu/vita/'.str_replace(' ','%20', $path);
        $client = \Drupal::httpClient(array( 'curl' => array( CURLOPT_SSL_VERIFYPEER => false ),'verify' => false));
        try {
            $response = $client->request('GET', $url, [
                'verify' => false,
                'proxy' => "http://$proxy_username:$proxy_password@$proxy_url:$proxy_port"
            ]);
        }catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
        return $response->getBody()->getContents();
    }

    /**
     * Gets faculty members from Parent Module API and stores them in a file
     * Used by child mode
     * @throws \Exception upon failed connection to Parent API
     */
    private function getFacultyFromParent(bool $filter, string $departments){
        $api_key = \Drupal::config('byu_faculty_directory.config')->get('dept_api_key');
        $parent_url = \Drupal::config('byu_faculty_directory.config')->get('parent_url');

        if ($filter) {
            try {
                $original_departments = explode(',', $departments);
                $departments = array();
                foreach ($original_departments as $dept) {
                    $departments[] = trim($dept);
                }

                $request = array('departments' => $departments);

                $client = \Drupal::httpClient();
                $url = $parent_url.'byu-faculty-directory/filtered-faculty?applicationKey='.$api_key;
                $method = 'POST';
                $options = [
                    'json' => json_encode($request)
                ];
                $response = $client->request($method, $url, $options);
                $result = $response->getBody()->getContents();

            } catch (\Exception $e) {
                throw new \Exception('Could not connect to parent module - check API key, URL. more details: '.$e->getMessage());
            }

        }
        else{
            try {
                $result = file_get_contents($parent_url . 'byu-faculty-directory/all-faculty?applicationKey=' . $api_key);
                if ($result === FALSE) {
                    throw new \Exception('getFacultyFromParent(): Could not connect to parent module- No changes made to cached data. Check application key and URL of parent module.');
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

        $result = json_decode($result, true);

        //Process each recieved faculty member
        foreach ($result['data'] as $entry) {
            //See if the faculty member already exists in the database
            $uid = $entry['field_byu_f_d_uid'][0]['value'];
            $uid_query = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadByProperties(['field_byu_f_d_uid' => $uid]);
            $netid = $uid;

            //Get URLs of image and CV
            $result = json_decode(file_get_contents($parent_url.'byu-faculty-directory/faculty-files?applicationKey='
                .$api_key.'&netid='.$netid));
            //Download em
            $cv_retrieved = False;
            $photo_retrieved = False;
            if (!$result->error) {
                if ($result->data->vita){
                    $vita_filetype = pathinfo($result->data->vita)['extension'];
                    $vita_filename = 'public://' . $netid . '_vita.' . $vita_filetype;
                    file_put_contents($vita_filename, file_get_contents($result->data->vita));
                    $cv_retrieved = True;
                }
                if ($result->data->photo){
                    $photo_filetype = pathinfo($result->data->photo)['extension'];
                    $photo_filename = 'public://' . $netid . '_photo.' . $photo_filetype;
                    file_put_contents($photo_filename, file_get_contents($result->data->photo));
                    $photo_retrieved = True;
                }
            }

            if ($node = reset($uid_query)){

                if (!$node->field_byu_f_d_awards_or->value){
                    $node->field_byu_f_d_awards = $entry['field_byu_f_d_awards'][0]['value'];
                }

                if (!$node->field_byu_f_d_biography_or->value){
                    $node->field_byu_f_d_biography = $entry['field_byu_f_d_biography'][0]['value'];
                }

                if (!$node->field_byu_f_d_committees_or->value){
                    $node->field_byu_f_d_committees = $entry['field_byu_f_d_committees'][0]['value'];
                }

                if (!$node->field_byu_f_d_courses_or->value){
                    $node->field_byu_f_d_courses = $entry['field_byu_f_d_courses'][0]['value'];
                }

                if (!$node->field_byu_f_d_email_or->value){
                    $node->field_byu_f_d_email = $entry['field_byu_f_d_email'][0]['value'];
                }

                if (!$node->field_byu_f_d_active_or->value) {
                    $node->field_byu_f_d_active = $entry['field_byu_f_d_active'][0]['value'];
                }

                if (!$node->field_byu_f_d_first_name_or->value){
                    $node->field_byu_f_d_first_name = $entry['field_byu_f_d_first_name'][0]['value'];
                }

                if (!$node->field_byu_f_d_last_name_or->value){
                    $node->field_byu_f_d_last_name = $entry['field_byu_f_d_last_name'][0]['value'];
                }

                if (!$node->field_byu_f_d_links_or->value){
                    $node->field_byu_f_d_links = $entry['field_byu_f_d_links'][0]['value'];
                }

                if (!$node->field_byu_f_d_office_location_or->value){
                    $node->field_byu_f_d_office_location = $entry['office_location'][0]['value'];
                }

                if (!$node->field_byu_f_d_phone_number_or->value){
                    $node->field_byu_f_d_phone_number = $entry['field_byu_f_d_phone_number'][0]['value'];
                }

                if (!$node->field_byu_f_d_publications_or->value){
                    $node->field_byu_f_d_publications = $entry['field_byu_f_d_publications'][0]['value'];
                }

                if (!$node->field_byu_f_d_research_long_or->value){
                    $node->field_byu_f_d_research_long = $entry['field_byu_f_d_research_long'][0]['value'];
                }

                if (!$node->field_byu_f_d_education_or->value){
                    $node->field_byu_f_d_education = $entry['field_byu_f_d_education'][0]['value'];
                }

                if (!$node->field_byu_f_d_title_or->value){
                    $node->field_byu_f_d_title = $entry['field_byu_f_d_title'][0]['value'];
                }

                if (!$node->field_byu_f_d_department_or->value){
                    $node->field_byu_f_d_department = $entry['field_byu_f_d_department'][0]['value'];
                }
                $name = $entry['field_byu_f_d_first_name'][0]['value'].' '.$entry['field_byu_f_d_last_name'][0]['value'];
                if (!$node->field_byu_f_d_cv_or->value && $cv_retrieved){
                    $files = \Drupal::entityTypeManager()
                        ->getStorage('file')
                        ->loadByProperties(['uri' => $vita_filename]);
                    $file = reset($files);
                    if (!$file){
                        $file = \Drupal\file\Entity\File::create([
                            'uri' => $vita_filename,
                        ]);
                        $file->save();
                    }
                    $node->field_byu_f_d_cv[] = [
                        'target_id' => $file->id(),
                    ];
                }
                if (!$node->field_byu_f_d_profile_image_or->value && $photo_retrieved){
                    $files = \Drupal::entityTypeManager()
                        ->getStorage('file')
                        ->loadByProperties(['uri' => $photo_filename]);
                    $file = reset($files);
                    if (!$file){
                        $file = \Drupal\file\Entity\File::create([
                            'uri' => $photo_filename,
                        ]);
                        $file->save();
                    }
                    $node->field_byu_f_d_profile_image[] = [
                        'target_id' => $file->id(),
                        'alt' => $name,
                        'title' => $name
                    ];
                }

                $node->save();
            }
            else {
                $name = $entry['field_byu_f_d_first_name'][0]['value'].' '.$entry['field_byu_f_d_last_name'][0]['value'];
                $create_array = array();
                $create_array['type'] = 'byu_faculty_member';
                $create_array['langcode'] = 'en';
                $create_array['field_byu_f_d_awards'] = $entry['field_byu_f_d_awards'][0]['value'];
                $create_array['field_byu_f_d_biography'] = $entry['field_byu_f_d_biography'][0]['value'];
                $create_array['field_byu_f_d_committees'] = $entry['field_byu_f_d_committees'][0]['value'];
                $create_array['field_byu_f_d_courses'] = $entry['field_byu_f_d_courses'][0]['value'];
                $create_array['field_byu_f_d_email'] = $entry['field_byu_f_d_email'][0]['value'];
                $create_array['field_byu_f_d_active'] = $entry['field_byu_f_d_active'][0]['value'];
                $create_array['field_byu_f_d_first_name'] = $entry['field_byu_f_d_first_name'][0]['value'];
                $create_array['field_byu_f_d_last_name'] = $entry['field_byu_f_d_last_name'][0]['value'];
                $create_array['field_byu_f_d_links'] = $entry['field_byu_f_d_links'][0]['value'];
                $create_array['field_byu_f_d_office_location'] = $entry['office_location'][0]['value'];
                $create_array['field_byu_f_d_phone_number'] = $entry['field_byu_f_d_phone_number'][0]['value'];
                $create_array['field_byu_f_d_publications'] = $entry['field_byu_f_d_publications'][0]['value'];
                $create_array['field_byu_f_d_research_long'] = $entry['field_byu_f_d_research_long'][0]['value'];
                $create_array['field_byu_f_d_education'] = $entry['field_byu_f_d_education'][0]['value'];
                $create_array['field_byu_f_d_title'] = $entry['field_byu_f_d_title'][0]['value'];
                $create_array['title'] = $entry['field_byu_f_d_first_name'][0]['value']." ".$entry['field_byu_f_d_last_name'][0]['value'];
                $create_array['field_byu_f_d_uid'] = $entry['field_byu_f_d_uid'][0]['value'];
                $create_array['field_byu_f_d_department'] = $entry['field_byu_f_d_department'][0]['value'];


                if ($cv_retrieved){
                    $file = \Drupal\file\Entity\File::create([
                        'uri' => $vita_filename,
                    ]);
                    $file->save();
                    $create_array['field_byu_f_d_cv'] = [
                        'target_id' => $file->id(),
                    ];

                }
                if ($photo_retrieved){
                    $file = \Drupal\file\Entity\File::create([
                        'uri' => $photo_filename,
                    ]);
                    $file->save();
                    $create_array['field_byu_f_d_profile_image'] = [
                        'target_id' => $file->id(),
                        'alt' => $name,
                        'title' => $name
                    ];
                }


                $node = \Drupal\node\Entity\Node::create($create_array);
                $node->save();
            }
        }
    }

    /**
     * Stores $setting = $value in the BYU Faculty Directory configuration settings
     */
    private function storeSetting($setting, &$value){
        \Drupal::service('config.factory')
            ->getEditable("byu_faculty_directory.config")
            ->set(strval($setting), $value)
            ->save();
    }

}
