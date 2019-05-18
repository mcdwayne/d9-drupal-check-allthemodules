<?php

namespace Drupal\file_ownage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Defines the admin settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'file_ownage_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['file_ownage.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('file_ownage.settings');

    $form['overview'] = [
      '#type' => 'details',
      '#title' => 'Overview',
      '#open' => TRUE,
      '#collapsible' => FALSE,
    ];

    $form['overview']['font'] = [
      '#type' => 'markup',
      '#prefix' => '<pre>',
      '#suffix' => '</pre>',
      '#markup' => 'stuff goes here',
    ];

    $form = $this->settingsForm($config->get());

    return parent::buildForm($form, $form_state);
  }

  /**
   * Subform that can be used to set the file_ownage preferences.
   *
   * Normally this is called as a straight system_settings_form,
   * but it may also be used as a subsettings config page when using rules,
   * if you need to make rules-based processes that have different, um, rules.
   *
   * @param array $settings
   *   Configs and context.
   *
   * @return array
   *   FAPI form.
   */
  public function settingsForm(array $settings) {

    // Although I use a lot of fieldsets here for organization,
    // Not all of them are tree parents, so pay attention to #tree and #parents.
    $form = [];

    $form['embeds'] = [
      '#type' => 'fieldset',
      // Short-circuit the tree-nesting of values,
      // this fieldset is just a visual wrapper, not structural.
      '#parents' => [],
      '#title' => t('Processing rules'),
      '#description' => t('File Ownage can process text fields and improve file handling for links and embeds found in the markup.'),
    ];

    $form['embeds']['node_save'] = [
      '#type' => 'checkbox',
      '#title' => t('Automatically process for embedded files on every node save.'),
      '#default_value' => $settings['node_save'],
    ];

    $form['embeds']['import_remote'] = [
      '#type' => 'checkbox',
      '#title' => t('Import remote files'),
      '#description' => t('When a reference to a remote file is found, copy it to our files directory. This is the primary function of the module.'),
      '#default_value' => $settings['import_nearby'],
    ];
    $form['embeds']['import_nearby'] = [
      '#type' => 'checkbox',
      '#title' => t('Import nearby files'),
      '#description' => t('When an embedded file is found that is local to the <em>website</em> (It probably still works), but not in our expected "files" directory, copy it to the correct files directory. This can help rationalize handmade "hybrid" drupal sites that contain legacy quirks.'),
      '#default_value' => $settings['import_nearby'],
    ];

    //
    // Image ownage settings
    // .
    $form['image_ownage'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#attributes' => [
        'id' => 'image_attach',
        'class' => ['filtering-fieldset'],
      ],
      '#title' => t('Image management'),
      '#description' => t('
      When an unattached image is found in an items markup,
      it\'s often useful to <em>attach</em> that image to the entity directly.
      Among other things, this helps ensure the files <em>usage</em> counter
      is kept up to date.
      Doing so is optional, but recommmended.
      <br/>
      Some of these methods can actually alter the markup - eg by removing
      an image that was pasted into the markup and instead attaching
      it as a feature image for more consistent displays.
      <br/>
      Additional custom ways of handling images can be added by other modules.
    '),
    ];

    $attachment_methods = file_ownage_image_attachment_methods();
    $options = [];

    foreach ($attachment_methods as $method => $details) {
      $options[$method] = $details['label'];
      // If the method defines the need for further settings,
      // add those subsettings from the form callback.
      if (isset($details['subform']) && function_exists($details['subform'])) {
        $subform_func = $details['subform'];
        $subform = $subform_func(isset($settings[$method]) ? $settings[$method] : []);
      }
      else {
        $subform = [];
      }
      $form['image_ownage'][$method] = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $details['label'],
        '#description' => $details['description'],
        '#states' => [
          'visible' => [
            'select[name="image_ownage[attach_method]"]' => ['value' => $method],
          ],
        ],
      ] + $subform;
    }

    $form['image_ownage']['attach_method'] = [
      '#weight' => -1,
      '#title' => "How to attach found images to the containing entity",
      '#type' => 'select',
      '#options' => $options,
      '#element_validate' => [[$this, 'validateAttachMethod']],
    ];

    // File ownage also.
    $form['file_ownage'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => 'Linked file management',
      '#description' => t(
        '
      Steps to try to manage <em>linked</em> files - (anchor references, not embeds)
      <small><code>&lt;a href="path/to/doc.pdf"&gt;pdf file&lt;/a&gt; ,
      &lt;a href="path/to/fullsize.jpg"&gt;large image&lt;/a&gt;</code></small>.
      If found, the contents of links like this should be absorbed and
      owned also.
      ', []
      ),
    ];
    $form['file_ownage']['import_files'] = [
      '#weight' => -5,
      '#type' => 'checkbox',
      '#title' => t('Try to absorb linked files.'),
      '#default_value' => $settings['file_ownage']['import_files'],
    ];
    $form['file_ownage']['suffix_list'] = [
      '#weight' => -4,
      '#type' => 'textfield',
      '#title' => t('List of suffixes to treat as file resources'),
      '#default_value' => $settings['file_ownage']['suffix_list'],
      '#description' => t(
        '
      As we can\'t tell what file type is at the end of a link request,
      guess based on apparent suffix.
      Enter a comma-separated list, eg <code>pdf,doc,jpg</code>
      Be aware that this can cause direct links to remotely hosted resources
      to get copied locally.
      '
      ),
      '#states' => [
        'visible' => [
          'input[name="file_ownage[import_files]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $attachment_methods = file_ownage_file_attachment_methods();
    $options = [];
    foreach ($attachment_methods as $method => $details) {
      $options[$method] = $details['label'];
      // If the method defines the need for further settings,
      // add those subsettings from the form callback.
      if (isset($details['subform']) && function_exists($details['subform'])) {
        $subform_func = $details['subform'];
        $subform = $subform_func(isset($settings[$method]) ? $settings[$method] : []);
      }
      else {
        $subform = [];
      }
      $form['file_ownage'][$method] = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $details['label'],
        '#states' => [
          'visible' => [
            'select[name="file_ownage[attach_method]"]' => ['value' => $method],
            'input[name="file_ownage[import_files]"]' => ['checked' => TRUE],
          ],
        ],
        '#description' => $details['description'],
      ] + $subform;
    }

    $form['file_ownage']['attach_method'] = [
      '#weight' => -1,
      '#title' => "How to attach found images to the containing node",
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $settings['file_ownage']['attach_method'],
      '#element_validate' => [[$this, 'validateAttachMethod']],
      '#states' => [
        'visible' => [
          'input[name="file_ownage[import_files]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // File ownage also.
    $form['filepaths'] = [
      '#type' => 'fieldset',
      '#title' => 'Filepath Options',
    ];

    $form['filepaths']['storage_path'] = [
      '#title' => 'Storage path',
      '#type' => 'textfield',
      '#default_value' => $settings['storage_path'],
      '#description' => t('
      Optionally define a folder inside your files folder
      for all imported files to be put. EG <code>imported</code>.
      Fragments of the old folder hierarchy will also be built underneath here
      when importing, so it\'s usually OK to leave this blank.
    '),
    ];
    $form['filepaths']['prettyfiles'] = [
      '#title' => 'Pretty files',
      '#type' => 'checkbox',
      '#default_value' => $settings['prettyfiles'],
      '#description' => t('
      Support local filepaths of the form
      <code>http://example.com/files/filename.jpg</code> .
      Normal Drupal behaviour refers to files as being deep in your
      sites/site.name/files folder,
      but if you are using <a href="https://drupal.org/node/1960806#aegir_prettyfiles">Aegir or an .htaccess tweak</a>,
      you can shorten that.
      It\'s a lot more portable.
      ONLY use this if you know your server supports this option.
    '),
    ];

    // Additional options.
    $strip_paths = array_filter((array) $settings['strip_paths']);
    $strip_paths[] = '';
    $form['filepaths']['strip_paths'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => 'Filepath cleanup',
      '#description' => t('
      You can provide a regular expression that could match part of a filepath 
      to strip when calculating the new import file storage path. 
      EG, if the target has paths like <code>_data/assets/image</code> 
      you can choose to throw away those extra layers.
      ', []
      ),
    ];

    foreach ($strip_paths as $pattern) {
      $form['filepaths']['strip_paths'][] = [
        '#type' => 'textfield',
        '#default_value' => $pattern,
      ];
    }
    $form['filepaths']['strip_paths']['help'] = [
      '#type' => 'markup',
      '#markup' => t("
      By default, a folder structure reflecting where the original was fetched 
      from will be built to store the retrieved file in. 
      This preserves provenance and eliminates duplicates.
      However, if you want all files to be dropped in an directory unstructured,
      a filepath cleanup pattern of <code>|^.*/|</code> should remove that for you'
    "),
    ];

    $form['domains'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => 'Domain management',
    ];
    $form['domains']['domain_handler'] = [
      '#type' => 'radios',
      '#title' => t('Restrict source domains'),
      '#options' => [
        'ownage' => 'Own everything we can get. If references to external images are found, they will get localized',
        'exclude' => 'Do not own images from the listed domains',
        'include' => 'Own images only from listed domains',
      ],
      '#default_value' => $settings['domains']['domain_handler'],
    ];

    $form['domains']['domain_list'] = [
      '#type' => 'container',
      '#title' => t('Importing from external domains'),
      '#description' => t('
      Add one URL per line. Start with http:// and feel
      free to include paths
      '
      ),
      '#states' => [
        'invisible' => [
          'input[name="domains[domain_handler]"]' => ['value' => 'ownage'],
        ],
      ],
    ];
    $domain_list = $settings['domains']['domain_list'];
    // Eliminate blank values and add one blank at the end.
    $domain_list = array_filter($domain_list, 'trim');
    $domain_list[] = '';
    foreach ($domain_list as $delta => $path) {
      $form['domains']['domain_list'][] = [
        '#title' => t('Domain'),
        '#type' => 'textfield',
        '#default_value' => isset($domain_list[$delta]) ? $domain_list[$delta] : '',
      ];
    }

    $form['lost_files'] = [
      '#type' => 'fieldset',
      '#title' => t('Lost Files'),
    ];
    $form['lost_files']['help'] = [
      '#type' => 'markup',
      '#markup' => '<p>
      If the linked file cannot be found where expected,
      we can try looking in alternative locations. This could help
      with importing old content, or just fixing up broken links.
      </p><p>
      Enter some paths to scan to see if a matching file is found there.
      This can even be a remote URL.
      Paths will be searched in order, first match being the best.
      </p><p>
      To <em>search</em> for files, enter a path with a wildcard <b>*</b>
      and that location will be scanned for the best match to the expected
      filename. Wildcard searches will not work on URLs.
      If a match is found, the processing rules above will be used and the
      source link will be updated to the correct location.
      </p>
    ',
    ];

    $seek_paths = $settings['seek_paths'];
    // Eliminate blank values and add one blank at the end.
    $seek_paths = array_filter($seek_paths, 'trim');
    $seek_paths[] = '';

    $form['lost_files']['seek_paths'] = [
      '#tree' => TRUE,
      '#description' => t('
      Paths to scan for this file, by filename and parentage.
      Can be a local filestream identifier (eg public://oldfiles)
      or a system path (eg /var/www/backup/assets)
      or even an URL (http://example.com/images/).

      Remote URL retrieval requires the <a href="https://www.drupal.org/project/remote_stream_wrapper">remote stream wrapper module</a>.

      For local filestreams, you can add a wildcard at the end also,
      eg public://imported/* .
      Without a wildcard, only reasonably precise matches will be checked.

      Include trailing slash.
    '),
    ];
    foreach ($seek_paths as $delta => $path) {
      $form['lost_files']['seek_paths'][] = [
        '#title' => t('Path'),
        '#type' => 'textfield',
        '#default_value' => isset($seek_paths[$delta]) ? $seek_paths[$delta] : '',
      ];
    }

    $form['lost_files']['do_directory_scan'] = [
      '#type' => 'checkbox',
      '#title' => t('Perform a recursive directory scan under the given paths.'),
      '#description' => 'This can be incredibly slow and it runs for every file',
      '#default_value' => $settings['do_directory_scan'],
    ];

    $form['lost_files']['relink_behaviour'] = [
      '#title' => t('When found:'),
      '#type' => 'radios',
      '#options' => [
        'move_file' => 'Move file to expected location',
        'update_db' => 'Change reference in the database',
        'copy_file' => 'Copy file to expected location',
      ],
      '#required' => TRUE,
      '#default_value' => $settings['relink_behaviour'],
      '#description' => t('
      No matter what choices you set here,
      some behaviours may change depending on context.
      EG, if the file was found in a read-only location,
      it will not "move" but only copy.
      If an existing record for an expected file is already in the database
      (looks like a duplicate) then the database record may be merged.
    '),
    ];

    $form['loglevel'] = [
      '#title' => t('Log level'),
      '#description' => t('
      For debugging, messages at this level or higher
      will be echoed to the screen.
      "Notice" is probably the most informative as it describes changes
      that get made during the process.
    '),
      '#type' => 'select',
      '#options' => RfcLogLevel::getLevels(),
      '#default_value' => $settings['loglevel'],
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Tidy some things up without complaining.
    // Search path should usually end in /, for later path concatenations.
    foreach ($form_state->getValue('seek_paths') as $delta => $search_path) {
      if (empty($search_path) || preg_match('%\*$%', $search_path)) {
        continue;
      }
      $search_path = preg_replace('/\/$/', '', $search_path) . '/';
      $form_state->setValue(['seek_paths', $delta], $search_path);
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * Local element validator.
   */
  public function validateAttachMethod(&$element, &$form_state) {

    // Ensure some of the options have the required dependencies.
    switch ($element['#value']) {

      case 'file_ownage_attach_filefield':
        if (!\Drupal::moduleHandler()->moduleExists('file')) {
          form_error($element, t('This method requires the filefield module to be enabled.'));
        }
        break;

      case 'file_ownage_attach_fileholder_nodereference':
        if (!\Drupal::moduleHandler()->moduleExists('nodereference')) {
          form_error($element, t('This method requires the nodereference module to be enabled.'));
        }
        break;

    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Bulk save anything submitted by that form with a key I recognise.
    // This could probably be compacted more. I miss the D7 settings form
    // that used to do this for us.
    $config = $this->config('file_ownage.settings');
    $settings = $config->get();
    $form_values = $form_state->getValues();
    foreach ($settings as $key => $val) {
      if (isset($form_values[$key])) {
        $config->set($key, $form_values[$key]);
      }
    }
    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
