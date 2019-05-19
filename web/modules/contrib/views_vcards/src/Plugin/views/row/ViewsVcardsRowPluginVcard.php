<?php

namespace Drupal\views_vcards\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;

/**
 * Renders a vCard item based on fields.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "views_vcard_fields",
 *   title = @Translation("vCard"),
 *   help = @Translation("Combines fields into a vCard."),
 *   theme = "views_vcards_view_row_vcard",
 *   display_types = {"views_vcard"}
 * )
 */
class ViewsVcardsRowPluginVcard extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();
    
    $options['name_email']['contains'] = [
      'first' => ['default' => ''],
      'middle' => ['default' => ''],
      'last' => ['default' => ''],
      'full' => ['default' => ''],
      'title' => ['default' => ''],
      'email' => ['default' => ''],
      'email2' => ['default' => ''],
      'email3' => ['default' => ''],
      'photo' => ['default' => ''],
    ];

    $options['home']['contains'] = [
      'home_address' => ['default' => ''],
      'home_city' => ['default' => ''],
      'home_state' => ['default' => ''],
      'home_zip' => ['default' => ''],
      'home_country' => ['default' => ''],
      'home_phone' => ['default' => ''],
      'home_cellphone' => ['default' => ''],
      'home_website' => ['default' => ''],
    ];
    
    $options['work']['contains'] = [
      'work_title' => ['default' => ''],
      'work_company' => ['default' => ''],
      'work_address' => ['default' => ''],
      'work_city' => ['default' => ''],
      'work_state' => ['default' => ''],
      'work_zip' => ['default' => ''],
      'work_country' => ['default' => ''],
      'work_phone' => ['default' => ''],
      'work_fax' => ['default' => ''],
      'work_website' => ['default' => ''],
    ];
    
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Fetch field labels for the options form.
    $view_fields_labels = $this->displayHandler->getFieldLabels();

    // Do only show image fields for the photo.
    $photo_options = [];
    $fields = $this->displayHandler->getOption('fields');
    foreach ($fields as $field_name => $field) {
      if (!empty($field['type']) && $field['type'] == 'image') {
        $photo_options[$field_name] = $view_fields_labels[$field_name];
      }
    }

    $form['name_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Name and E-mail'),
      '#open' => TRUE,
    ];

    $form['name_email']['first'] = [
      '#type' => 'select',
      '#title' => $this->t('First name'),
      '#description' => $this->t('The field that is going to be used as the first name for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['name_email']['first'],
      '#required' => TRUE,
      '#empty_value' => '',
    ];

    $form['name_email']['middle'] = [
      '#type' => 'select',
      '#title' => $this->t('Middle name'),
      '#description' => $this->t('The field that is going to be used as the middle name for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['name_email']['middle'],
      '#required' => FALSE,
      '#empty_value' => '', 
  ];

    $form['name_email']['last'] = [
      '#type' => 'select',
      '#title' => $this->t('Last name'),
      '#description' => $this->t('The field that is going to be used as the last name for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['name_email']['last'],
      '#required' => TRUE,
      '#empty_value' => '',
    ];

    $form['name_email']['full'] = [
      '#type' => 'select',
      '#title' => $this->t('Full name'),
      '#description' => $this->t('The field that is going to be used as the full name for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['name_email']['full'],
      '#required' => TRUE,
      '#empty_value' => '',
    ];

    $form['name_email']['title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The field that is going to be used as the title for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['name_email']['title'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['name_email']['email'] = [
      '#type' => 'select',
      '#title' => $this->t('Primary E-mail'),
      '#description' => $this->t('The field that is going to be used as the primary e-mail address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['name_email']['email'],
      '#required' => TRUE,
      '#empty_value' => '',
    ];

    $form['name_email']['email2'] = [
      '#type' => 'select',
      '#title' => $this->t('Secondary E-mail'),
      '#description' => $this->t('The field that is going to be used as the secondary e-mail address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['name_email']['email2'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['name_email']['email3'] = [
      '#type' => 'select',
      '#description' => $this->t('The field that is going to be used as the tertiary e-mail address for each vCard.'),
      '#title' => $this->t('Tertiary E-mail'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['name_email']['email3'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['name_email']['photo'] = [
      '#type' => 'select',
      '#description' => $this->t('The field that is going to be used as the photo for each vCard.'),
      '#title' => $this->t('Photo'),
      '#options' => $photo_options,
      '#default_value' => $this->options['name_email']['photo'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['home'] = [
      '#type' => 'details',
      '#title' => $this->t('Home'),
      '#open' => FALSE,
    ];

    $form['home']['home_address'] = [
      '#type' => 'select',
      '#title' => $this->t('Address'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['home']['home_address'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['home']['home_city'] = [
      '#type' => 'select',
      '#title' => $this->t('City'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['home']['home_city'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['home']['home_state'] = [
      '#type' => 'select',
      '#title' => $this->t('State/Province'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['home']['home_state'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['home']['home_zip'] = [
      '#type' => 'select',
      '#title' => $this->t('Zip'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['home']['home_zip'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['home']['home_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['home']['home_country'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['home']['home_phone'] = [
      '#type' => 'select',
      '#title' => $this->t('Phone'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['home']['home_phone'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['home']['home_cellphone'] = [
      '#type' => 'select',
      '#title' => $this->t('Cellphone'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['home']['home_cellphone'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['home']['home_website'] = [
      '#type' => 'select',
      '#title' => $this->t('Website'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['home']['home_website'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work'] = [
      '#type' => 'details',
      '#title' => $this->t('Work'),
      '#open' => FALSE,
    ];

    $form['work']['work_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_title'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work']['work_company'] = [
      '#type' => 'select',
      '#title' => $this->t('Company'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_company'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work']['work_address'] = [
      '#type' => 'select',
      '#title' => $this->t('Address'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_address'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work']['work_city'] = [
      '#type' => 'select',
      '#title' => $this->t('City'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_city'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work']['work_state'] = [
      '#type' => 'select',
      '#title' => $this->t('State/Province'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_state'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work']['work_zip'] = [
      '#type' => 'select',
      '#title' => $this->t('Zip'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_zip'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work']['work_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_country'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work']['work_phone'] = [
      '#type' => 'select',
      '#title' => $this->t('Phone'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_phone'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work']['work_fax'] = [
      '#type' => 'select',
      '#title' => $this->t('Fax'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_fax'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['work']['work_website'] = [
      '#type' => 'select',
      '#title' => $this->t('Website'),
      '#description' => $this->t('The field that is going to be used as the address for each vCard.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['work']['work_website'],
      '#required' => FALSE,
      '#empty_value' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $required_options = [
      'name_email' => [
        'first',
        'last',
        'full',
        'email',
      ],
    ];

    foreach ($required_options as $group => $options) {
      foreach ($options as $required_option) {
        if (empty($this->options[$group][$required_option])) {
          $errors[] = $this->t('Some required fields are missing to construct a valid vCard. Check the vCard formatter settings.');
          break;
        }
      }
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }

    // Create the vCard item object.
    $item = new \stdClass();
    $item->first_name     = $this->getField($row_index, $this->options['name_email']['first']);
    $item->middle_name    = $this->getField($row_index, $this->options['name_email']['middle']);
    $item->last_name      = $this->getField($row_index, $this->options['name_email']['last']);
    $item->full_name      = $this->getField($row_index, $this->options['name_email']['full']);
    $item->title          = $this->getField($row_index, $this->options['name_email']['title']);
    $item->email          = $this->getField($row_index, $this->options['name_email']['email']);
    $item->email2         = $this->getField($row_index, $this->options['name_email']['email2']);
    $item->email3         = $this->getField($row_index, $this->options['name_email']['email3']);
    $item->home_address   = $this->getField($row_index, $this->options['home']['home_address']);
    $item->home_city      = $this->getField($row_index, $this->options['home']['home_city']);
    $item->home_state     = $this->getField($row_index, $this->options['home']['home_state']);
    $item->home_zip       = $this->getField($row_index, $this->options['home']['home_zip']);
    $item->home_country   = $this->getField($row_index, $this->options['home']['home_country']);
    $item->home_phone     = $this->getField($row_index, $this->options['home']['home_phone']);
    $item->home_cellphone = $this->getField($row_index, $this->options['home']['home_cellphone']);
    $item->home_website   = $this->getField($row_index, $this->options['home']['home_website']);
    $item->work_title     = $this->getField($row_index, $this->options['work']['work_title']);
    $item->work_company   = $this->getField($row_index, $this->options['work']['work_company']);
    $item->work_address   = $this->getField($row_index, $this->options['work']['work_address']);
    $item->work_city      = $this->getField($row_index, $this->options['work']['work_city']);
    $item->work_state     = $this->getField($row_index, $this->options['work']['work_state']);
    $item->work_zip       = $this->getField($row_index, $this->options['work']['work_zip']);
    $item->work_country   = $this->getField($row_index, $this->options['work']['work_country']);
    $item->work_phone     = $this->getField($row_index, $this->options['work']['work_phone']);
    $item->work_fax       = $this->getField($row_index, $this->options['work']['work_fax']);
    $item->work_website   = $this->getField($row_index, $this->options['work']['work_website']);
    $item->photo          = NULL;

    $photo_field = $this->options['name_email']['photo'];
    if (!empty($photo_field)) {
      // Obtain the raw field value.
      $fid = $this->view->style_plugin->getFieldValue($row_index, $photo_field);

      if ($file = File::load($fid)) {

        // Obtain desired image style
        $image_style = $this->view->field[$photo_field]->options['settings']['image_style'];

        // Mimetype
        $type = strtoupper(str_replace('image/', '', $file->getMimeType()));

        // Use the default uri, unless there is an image style.
        $url = $file->getFileUri();
        if ($image_style) {
          $url = ImageStyle::load($image_style)->buildUrl($url);
        }

        $imgbinary = file_get_contents($url);
        $photo = "\nPHOTO;TYPE=" . $type . ';ENCODING=B:' . base64_encode($imgbinary);
        $item->photo = substr($photo, 0, 64) . "\r\n ";
        $item->photo .= chunk_split(substr($photo, 64), 63, "\r\n ");
        $item->photo = rtrim($item->photo); // Remove last newline.
      }
    }

    $row_index++;

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    ];

    return $build;
  }

  /**
   * Retrieves a views field value from the style plugin.
   *
   * @param $index
   *   The index count of the row as expected by views_plugin_style::getField().
   * @param $field_id
   *   The ID assigned to the required field in the display.
   *
   * @return string|null|\Drupal\Component\Render\MarkupInterface
   *   An empty string if there is no style plugin, or the field ID is empty.
   *   NULL if the field value is empty. If neither of these conditions apply,
   *   a MarkupInterface object containing the rendered field value.
   */
  public function getField($index, $field_id) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($field_id)) {
      return '';
    }
    // Convert plaintext into render array.
    $field = $this->view->style_plugin->getField($index, $field_id);
    return is_array($field) ? $field : ['#markup' => $field];
  }

}
