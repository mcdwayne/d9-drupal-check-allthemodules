<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm.
 *
 */

namespace Drupal\generator_ui\Form;

//Use the necessary classes
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\generator_ui\Controller\GeneratorController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * Class GeneratorForm
 * @package Drupal\generator_ui\Form
 */
class Generator extends FormBase
{


    protected $twig;

    /**
     * The constructor.
     *
     * @param \Drupal\Core\Template\TwigEnvironment $twig
     *   used to load templates from the file system or other locations.
     */
    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Instantiation the twig service which we can use in this class.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container .
     *   container dependency injection
     * @return
     *   The twig service.
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('twig')

        );
    }

    public static function get_module_name(FormStateInterface $form_state)
    {
        return $form_state->getValue('module_name');
    }

    public static function validate_module($element, FormStateInterface $form_state)
    {
        $module_name = $element['#value'];
        $form_state->setValueForElement($element, $module_name);
        if(!in_array($module_name,GeneratorController::getListModules())){
          $form_state->setError($element, t('The module " %module_name " doesn\'t exist.', array('%module_name' => $module_name)));
        }
    }

    // Callback for rendering dynamically a generator form inside another.

    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId()
    {
    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
      $form['module_name'] = array(
            '#type' => 'textfield',
            '#autocomplete_route_name' => 'generator.autocomplete',
            '#title' => t('Module machine name'),
            '#required' => TRUE,
            '#element_validate' => array(array(get_class($this), 'validate_module')),
            "#weight" => -1,
            '#machine_name' => array(
                'source' => array('module_'),
            ),
        );
        $form['help'] = array(
            '#type' => 'checkbox',
            '#title' => t('Show comments in the code'),
            '#description' => t('You can add comments when generating code to help you understand !'),
            "#weight" => -2
        );
        $wrapper = "highlighter_" . $form['#attributes']['class'][0] . '-ajax';
        $form['btn_generate'] = array(
            '#type' => 'button',
            '#executes_submit_callback' => TRUE,
            '#button_type' => 'submit',
            '#value' => t('Generate code(s)'),
            '#ajax' => array(
                'callback' => '::generateCodes',
                'wrapper' => $wrapper,
            ),
        );
        $form['btn_create'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Create files'),
            '#ajax' => array(
                'callback' => '::createFiles',
                'wrapper' => $form['#attributes']['class'][0],
            ),
        );
        $form['btn_download'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Download files'),
            '#submit' => array('::downloadFiles')
        );
        $form['ajx'] = array(
            '#prefix' => '<pre id=' . $wrapper . ' class=" brush php syntaxhighlighter ">',
            '#suffix' => '</pre>',
            '#markup' => '<p>' . $this->t('Click on Generate Code(s) to show all completed files here.') . '</p>',
        );

        return $form;
    }

    public function renderGenerator(array $form, FormStateInterface $form_state)
    {
        // The ID of the generator is defined as the ID of the triggering element.
        $triggered_element = $form_state->getTriggeringElement();
        $generator_id = $triggered_element['#array_parents'][0];
        // If checkbox is checked, call the form.
        if ($form_state->getValue('call_' . $generator_id)) {
            $form_id = 'Drupal\generator_ui\Form\\' . $generator_id;
            $form[$generator_id]['form'] = \Drupal::formBuilder()
                ->getForm($form_id);
            // Fill the form with the mother generator form values.
            // @todo : autofill ?
            // @todo : Does not work for help ?
            $form[$generator_id]['form']['help']['#value'] = $form_state->getValue('help');
            $form[$generator_id]['form']['module_name']['#value'] = $form_state->getValue('module_name');
            // Specific for permission. Todo : to be changed.
            $form[$generator_id]['form']['key']['#value'] = $form_state->getValue('permission');
            // Removes download button.
            unset($form[$generator_id]['form']['btn_download']);
        }
        return $form[$generator_id];
    }

    // Validation of existence of module in modules folder

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $module_name = $form_state->getValue('module_name');
        $all_files_to_create = $this->pathFiles($form_state);

        // Check existence of the files.
        foreach ($all_files_to_create AS $file) {
            //delete DRUPAL_ROOT from paths
            $file = str_replace(DRUPAL_ROOT . "/", "", $file);
            if (!strpos($file, '.routing.yml')) {
                // If file already exists, we cannot create (do not erase previous file for now).
                if (GeneratorController::exist_file($file, $module_name)) {
                    $form_state->setErrorByName('module_name', $this->t('The file ' . $file . ' is already exists.'));
                }
            }
        }
    }

    // Return textareas with code.

    /**
     * Find paths when generating files.
     *
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     * @return
     *   All path that we want generate files.
     */
    public function pathFiles(FormStateInterface $form_state)
    {
        // extract all path of twig files
        $path_ = "";
        $extracted_twig_path_files = $this->getRealPathNamesTwigFiles();
        $module_name = $form_state->getValue('module_name');
        //Names of twig files
        $twig_files = $form_state->getValue('twig_file');
        // Filter twig files from key ( $twig_file = array($key => $value) ).
        foreach ($twig_files as $twig_file => $value) {
            $twigg_files[] = $value;
        }

        // Get all paths and name file and extension(s) for every templates.
        // name class php files
        $name_files = $this->extractNamePhpFiles($form_state);
        for ($i = 0; $i < count($twigg_files); $i++) {
            // Get all path from twig files
            $path = $extracted_twig_path_files[$twigg_files[$i]];
            // Delete /templates/generator_ui from paths
            $path_file = substr($path, strpos($path, 'templates') + 10);
            if (substr_count($path_file, '.id_action') != 0) {
                $path_file = str_replace('id_action', $form_state->getValue('id_action'), $path_file);
            }
            // Get the right path for file creation.
            // Get all the internal path and name of the file (last element of the array).
            $path_chunks = explode('/', $path_file);
            // Delete the name of twig file from path
            array_pop($path_chunks);

            // Get the extentions & file name (last element of the array).
            $all_extensions = explode('.', $twigg_files[$i]);
            if ($all_extensions[0] != "system") {
                array_shift($all_extensions);
            }
            // Remove name file.
            // If the file is module_name.install or module_name.module then delete yml extension
            if ($all_extensions[0] == 'install' || $all_extensions[0] == 'module') {
                unset($all_extensions[1]);
            }
            // Remove the .twig extention.
            array_pop($all_extensions);
            // if the path has an yml extension then generate the right file
            $pathh[$i] = implode("/", $path_chunks);

            if (substr_count($path_file, '.yml') != 0) {
                if ($pathh[$i] != "") {
                    $path_ = '/' . $pathh[$i];
                }
                //TODO : correct . 05-11-2015
                if (substr_count($path_file, '.action') != 0) {

                    $path_file = str_replace('.twig', "", $path_file);


                    $path_file = str_replace('.twig', "", $path_file);
                    $path_file = str_replace(explode(".", $path_file)[0], $form_state->getValue('module_name'), $path_file);
                    $final_path[] = DRUPAL_ROOT . "/modules/" . $module_name . '/' . $path_file;
                } else {
                    $final_path[] = DRUPAL_ROOT . "/modules/" . $module_name . $path_ . '/' . $module_name . '.' . implode('.', $all_extensions);
                }
            } else {
                if (substr_count($path_file, '.json') != 0) {
                    $path_file = str_replace('.twig', "", $path_file);
                    $final_path[] = DRUPAL_ROOT . "/modules/" . $module_name . '/' . $path_file;
                } else {

                    $final_path[] = DRUPAL_ROOT . "/modules/" . $module_name . '/' . $pathh[$i] . '/' . $name_files[$i] . '.' . implode('.', $all_extensions);
                }
            }
        }
        return $final_path;
    }

    /**
     * Find paths of all twig files.
     *
     * @return
     *   All twig files.
     */
    public function getRealPathNamesTwigFiles()
    {
        //path of templates files
        //find directories and files in template directory
        $dir_templates = drupal_get_path('module', 'generator_ui') . '/templates';
        $iter = new \RecursiveDirectoryIterator($dir_templates);
        foreach (new \RecursiveIteratorIterator($iter) as $file) {
            //if the file has a twig extension
            if (strpos($file->getFilename(), '.twig')) {
                $filename = $file->getFilename();
                // declare an array contains the list of twig files
                $list[$filename] = $file->getPath() . '/' . $filename;
            }
        }
        return $list;
    }

    public function extractNamePhpFiles(FormStateInterface $form_state)
    {
        $twig_files = $form_state->getValue('twig_file');
        $php_file = array();
        // look over all twig names
        foreach ($twig_files as $twig_file => $value) {
            // if the twig names is php twig file
            if (!is_numeric($twig_file)) {
                // if the class name is not null
                if ($form_state->getValue($twig_file) != NULL) {
                    // Save all class php names
                    $php_file[] = $form_state->getValue($twig_file);
                } else {
                    //TODO change entity_class to class
                    $php_file[] = $this->addPrefixToClass($form_state->getValue('entity_class'), $twig_file);
                }
            }
        }
        return $php_file;
    }

    // Return textareas with code.

    public function addPrefixToClass($php_class, $php_file)
    {
        $php_files = $php_class . $php_file;
        return $php_files;
    }

    // get right paths of all twig files

    /**
     * Form submission handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $form_state->setRebuild();
    }

    public function generateCodes(array &$form, FormStateInterface $form_state)
    {
        // Get the module name.
        $module_name = $form_state->getValue('module_name');
        $params = $this->getAllValues($form_state);
        // Get all twig paths of generator_ui.
        $paths_all_twig = $this->getFullPathTwig($form_state);
        $all_files_to_create = $this->pathFiles($form_state);
        // We'll put all the code into the ajx markup field.
        $form['ajx']['#markup'] = '';
        foreach (array_combine($paths_all_twig, $all_files_to_create) as $path => $real_path) {
            // @todo : revoir les chemins.
            $input = $this->loadFromTwig($params, $path);
            // Reformat the path as it should be generated.
            // eliminate the Root Directory from the file
            $real_path = str_replace(DRUPAL_ROOT . "/", "", $real_path);
            // Add label with the path where it should be generated and the file name.
            $form['ajx']['#markup'] .= '<div class="title_name_file"><span>' . t('Generates : ') . '</span>' . $real_path . '</div>';
            $form['ajx']['#markup'] .= "<pre onclick='this.select()' class='code'>";
            $form['ajx']['#markup'] .= "<div class='select-text' title='select text'></div>";
            $form['ajx']['#markup'] .= '<code class="' . $this->getPathInfo($real_path) . '">' . ($input) . '</code></pre>';
            // Print the code that should be copied and pasted.
        }
        return $form['ajx'];
    }
    // This method extract the name of PHP File classes

    /**
     * get All values.
     *
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   Dependencies.
     */
    public function getAllValues(FormStateInterface $form_state)
    {
        $values_changes = $form_state->getValues();
        foreach ($values_changes as $key => $value) {
            if (is_string($value) and strpos($value, ",") == TRUE) {
                $values_changes[$key] = explode(",", $value);
            }
        }
        return $values_changes;
    }

    // add to classes Prefixes :

    /**
     * Find paths of specific twig files.
     *
     * @return
     *   All path that we want generate files.
     */
    public function getFullPathTwig(FormStateInterface $form_state)
    {
        // return
        $extracted_twig_path_files = $this->getRealPathNamesTwigFiles();
        //Names of twig files
        $twig_files = $form_state->getValue('twig_file');
        // Filter twig files from key ( $twig_file = array($key => $value) ).
        foreach ($twig_files as $twig_file => $value) {
            $twigg_files[] = $value;
        }
        for ($i = 0; $i < count($twigg_files); $i++) {
            // Get all path from twig files
            $path[] = $extracted_twig_path_files[$twigg_files[$i]];
        }
        return $path;
    }

    /**
     * Load and render twig files.
     *
     * @param array $params
     *   An  array containing variables which will passed in twig files.
     * @param $path
     *   The current path of twig file.
     * @return string
     *   Get the string which contains the code to generate.
     */
    protected function loadFromTwig($parameters, $pathTemplate)
    {
        $template = $this->twig->loadTemplate($pathTemplate);
        $output = array(
            $template->render($parameters)
        );
        $outputFinal = implode("\n", $output);

        return $outputFinal;
    }

    // This function allow to add route(if doesn't exist)in routing file

    protected function getPathInfo($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        switch ($ext):
            case 'yml':
                $ext = 'yaml';
            case 'module':
                $ext = 'php';
        endswitch;
        return $ext;
    }

    function check_markup_textarea($text, $format_id = NULL, $langcode = '')
    {
        $build = array(
            '#type' => 'text_format',
            '#text' => $text,
            '#format' => $format_id,
            '#langcode' => $langcode,
        );
        return \Drupal::service('renderer')->renderPlain($build);
    }

    public function generateCdodes(array &$form, FormStateInterface $form_state)
    {
        // Get the module name.
        $params = $this->getAllValues($form_state);
        // Get all twig paths of generator_ui.
        $paths_all_twig = $this->getFullPathTwig($form_state);
        $all_files_to_create = $this->pathFiles($form_state);
        // We'll put all the code into the ajx markup field.
        //   $form['ajx']['#markup'] = '';

        foreach (array_combine($paths_all_twig, $all_files_to_create) as $path => $real_path) {
            $i = count($all_files_to_create);

            // eliminate the Root Directory from the file
            $real_path = str_replace(DRUPAL_ROOT . "/", "", $real_path);
            $input = $this->loadFromTwig($params, $path);
            // Add label with the path where it should be generated and the file name.
            $form['ajx']['#markup'] .= '<label>' . t('Generates : ') . $real_path . '</label>';
            // Print the code that should be copied and pasted.
            $form['ajx']['#value'] .= ('div onclick="this.select()" rows="15" cols="10" style="width:100%"' . $input);
            return $form['ajax'];

        }
        // Return the ajx field with all the code.
    }

    /**
     * create the package which contain all files generated.
     *
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     * @param $twig_file
     *   Name of twig file declarated in templates.
     */
    public function createFiles(array &$form, FormStateInterface $form_state)
    {
        $params = $this->getAllValues($form_state);
        $module = $form_state->getValue('module_name');
        // twig files
        $twigFiles = $this->path($form_state);
        //the right path
        $files_to_create = $this->pathFiles($form_state);
        $new_files_to_create = $this->is_not_null($files_to_create);
        $exist_routing_file = GeneratorController::exist_file('modules/' . $module . '/' . $module . '.routing.yml', $module);
        for ($i = 0; $i < count($new_files_to_create); $i++) {
            $this->createFile(
                $twigFiles[$i],
                $files_to_create[$i],
                $params, $module
            );
            drupal_set_message(t('The File @filepath was created successfully !', array('@filepath' => $new_files_to_create[$i])));
        }
        drupal_flush_all_caches();
        drupal_set_message(t('Caches cleared after creation of files.'));

        $status_messages = array('#type' => 'status_messages');
        return array(
            '#markup' => \Drupal::service('renderer')
                ->renderRoot($status_messages)
        );
    }

    public function path(FormStateInterface $form_state)
    {
        //name of all twig files
        $twig_filess = $form_state->getValue('twig_file');
        foreach ($twig_filess as $twig_file => $value) {
            $twigg_files[] = $value;
        }
        $all_twig_files = $this->getRealPathNamesTwigFiles();
        foreach ($twigg_files as $twigg_file) {
            $list = $all_twig_files[$twigg_file];
            $list_all_twig_files[] = $list;
        }
        return $list_all_twig_files;
    }

    // this method delete from an array null values

    public function is_not_null($arrays = array())
    {
        foreach ($arrays as $array) {
            if ($array != NULL) {
                $new_array[] = $array;
            }
        }
        return $new_array;
    }

    /**
     * Creating files.
     *
     * @param  $pathTemplate
     *  Path of template file.
     * @param $target
     *   Target to create file.
     * @param $parameters
     *   values passed to twig files.
     */
    protected function createFile($pathTemplate, $target, $parameters, $module, $flag = NULL)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0770, TRUE);
            chmod(dirname($target), 0770);
        }
        if (file_get_contents($target) != "") {
            if (strpos($target, '.routing.yml')) {
                $updated_routing_file = $this->updateRoutingFile($pathTemplate, $parameters, $target, $module);
                $files_to_create = file_put_contents($target, $updated_routing_file, $flag);
            }
        } else {
            $files_to_create = file_put_contents($target, $this->loadFromTwig($parameters, $pathTemplate), $flag);
        }
        return $files_to_create;
    }

    public function updateRoutingFile($pathTemplate, $parameters, $target, $module)
    {
        $data_to_add = "";
        $content_to_add = $this->loadFromTwig($parameters, $pathTemplate);
        $routing_file = drupal_get_path('module', $module) . '/' . $module . '.routing.yml';
        //Convert yml file to an array
        $routing_array = Yaml::parse($routing_file);
        $files_exist = file_get_contents($target);
        if (is_array($routing_array)) {
            foreach ($routing_array as $item) {
                if (!strpos($files_exist, $item)) {
                    if (strpos($pathTemplate, '.routing.yml')) {
                        // Adding data to routing file
                        $data_to_add = $files_exist . "\n" . $content_to_add;
                    }
                }
            }
        }
        return $data_to_add;
    }

    /**
     * download  the package which contain all files generated.
     *
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function downloadFiles(array &$form, FormStateInterface $form_state)
    {
        unset($form['module_name']['#element_validate']);
        $string_to_eleminate = DRUPAL_ROOT . "/modules/";
        $files_to_create = $this->pathFiles($form_state);
        $new_files_to_create = $this->is_not_null($files_to_create);
        foreach ($new_files_to_create as $new_file_to_create) {
            // eliminate the Root Directory from the file
            $new_files[] = str_replace($string_to_eleminate, "", $new_file_to_create);
        }
        // Create new zip object
        $zip = new \ZipArchive();
        // Create a temp file & open it
        $tmp_file = tempnam('.', '');
        $zip->open($tmp_file, \ZipArchive::CREATE);
        $params = $this->getAllValues($form_state);
        $paths_all_twig = $this->getFullPathTwig($form_state);

        for ($i = 0; $i < count($paths_all_twig); $i++) {
            $input[] = $this->loadFromTwig($params, $paths_all_twig[$i]);
        }
        // Zip all files.
        for ($i = 0; $i < count($new_files); $i++) {
            #add it to the zip
            $zip->addFromString($new_files[$i], $input[$i]);
        }
        // Close zip
        $zip->close();
//        $response->headers->set('Content-length', filesize($tmp_file));

        // Send the file to the browser as a download
//        header('Content-Disposition: attachment; filename=' . basename('files_generated.zip'));
//        header('Content-type: application/zip');

        $response = new Response();
        $response->setStatusCode(200);
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', sprintf('attachment;filename="%s.zip"', "files_generated"));
        $response->headers->set('Content-Length', filesize($tmp_file));

        $response->setContent(readfile($tmp_file));
// prints the HTTP headers followed by the content
        $response->send();

        unlink($tmp_file);
        return $response;
        exit;
    }

    public function getContainer($services)
    {
        $containers = array();
        if (empty($services) ) return $services;

        if (is_string($services)):
            $services = explode(',', $services);
        endif;
        if (is_array($services)):
            $services = array_map('trim', $services);
            array_unique($services);
            foreach ($services as $service):
                try {
                    $containers[] = array(
                        'service' => $service,
                        'name' => str_replace(".", "_", $service),
                        'class' => new \ReflectionClass(get_class(\Drupal::getContainer()->get($service))),
                    );
                } catch (\Exception $e) {

                }
            endforeach;
        else:
            return gettype($services);
        endif;

        return $containers;
    }

    public function getEventsList()
    {
        $list = [];
        foreach (\Drupal::getContainer()->get('event_dispatcher')->getlisteners() as $key => $listners):
            $list[$key] = $key;
        endforeach;
        return $list;
    }
}