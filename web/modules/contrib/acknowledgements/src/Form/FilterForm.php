<?php

namespace Drupal\sign_for_acknowledgement\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sign_for_acknowledgement\Service\AcknowledgementsDatabase;


/**
 * Form builder for the sign_for_acknowledgement basic settings form.
 */
class FilterForm extends FormBase {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   *
   */
  public function __construct() {
    $this->config = \Drupal::config('sign_for_acknowledgement.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sign_for_acknowledgement_filter_form';
  }

/**
 * Creates session name.
 *
 * @param object $node
 *   the current node
 *
 * @return string session name
 */
public static function sessionName($node)
{
  return 'acknowledgements_' . $node->id() . '_filter';
}

/**
 * List user administration filters that can be applied.
 */
public static function filters($node) {
  $filters = array();
  $config = \Drupal::config('sign_for_acknowledgement.settings');
  $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
  $options = array();
  $options['any'] = t('any');
  $options[AcknowledgementsDatabase::SIGNED_OK] = $dbman->getCustomMessage(AcknowledgementsDatabase::SIGNED_OK);
  $options[AcknowledgementsDatabase::TO_BE_SIGNED] = $dbman->getCustomMessage(AcknowledgementsDatabase::TO_BE_SIGNED);
  if (count($node->get('expire_date'))) {
    $options[AcknowledgementsDatabase::TERMS_EXPIRED] = $dbman->getCustomMessage(AcknowledgementsDatabase::TERMS_EXPIRED);
    $options[AcknowledgementsDatabase::OUT_OF_TERMS] = $dbman->getCustomMessage(AcknowledgementsDatabase::OUT_OF_TERMS);
  }
  $filters['status'] = array(
    'title' => t('status'),
    'options' => $options,
  );
  if ($node->alternate_form->value) {
	$labels = str_replace(["\r\n", "\r"], "\n", $node->alternate_form_text->value);
	$labels = explode( "\n", $labels);//$node->get('alternate_form_text')->getValue();
    $options = array('any' => t('any'));
	$first_key = FALSE;
	foreach ($labels as $label) {
	  $val = \Drupal\Component\Utility\Xss::filter($label);
	  if (empty(trim($val))) {
        continue;
	  }
	  $options[$val] = $val;
	  if ($first_key === FALSE) {
		$first_key = $val;
	  }
	}
    $filters['agreement'] = array(
      'title' => t('agreement'),
      'options' => $options,
    );
  }
  $custom_fields = $config->get('fields');
  foreach ($custom_fields as $key => $value) {
	if ($value !== $key) {
	  continue;
	}
	$array = \Drupal::config('field.storage.user.'.$key)->get();
//	echo "<pre>";print_r($array);exit;
    if ($array['type'] != 'list_string') {
      continue;
    }
    if ($array && count($array)) {
      $array = $array['settings']['allowed_values'];
    }
    $options = array('any' => t('any'));
	foreach($array as $sub) {
	  $options[$sub['value']] = $sub['value'];
	}
    $filters[$key] = array(
      'title' => $value,
      'options' => $options,
    );
  }
  /* TODO fields sanitize!
  foreach ($custom_fields as $key => $value) {
    $array = field_info_field($key);
    if ($array && count($array)) {
      $array = array_map('check_plain', $array['settings']['allowed_values']);
    }
    $options = array('any' => t('any')) + $array;
  }
  */
  return $filters;
}

  /**
   * {@inheritdoc}
   */
public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $node = NULL) {
  $session_name = self::sessionName($node);
  $session = isset($_SESSION[$session_name]) ? $_SESSION[$session_name] : array();
  $filters = self::filters($node);

  $form['filters'] = array(
    '#type' => 'fieldset',
    '#title' => t('Show only users where'),
    '#attributes' => array('class' => array('exposed-filters')),
  );

  $form['filters']['node'] = array(
    '#type' => 'value',
    '#name' => 'node',
    '#value' => $node->id(),
  );
  $form['filters']['session'] = array(
    '#type' => 'value',
    '#name' => 'session',
    '#value' => $session_name,
  );
  $form['filters']['status'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('clearfix')),
  );
  $form['filters']['status']['filters'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('filters')),
  );
  foreach ($filters as $key => $filter) {
    $value = count($session) && isset($session[$key]) && $session[$key] ? $session[$key] : 'any';
	//echo $value;exit;
    $form['filters']['status']['filters'][$key] = array(
      '#type' => 'select',
      '#options' => $filter['options'],
      '#title' => $filter['title'],
      '#attributes' => array(
        'title' => $value == 'any' ? t('any') : $value,
      ),
      '#default_value' => $value,
    );
  }
  $form['filters']['status']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
    '#submit' => array('Drupal\sign_for_acknowledgement\Form\FilterForm::submitFilter'),
  );
  $form['filters']['status']['to_default'] = array(
    '#type' => 'submit',
    '#value' => t('Reset filters'),
    '#submit' => array('Drupal\sign_for_acknowledgement\Form\FilterForm::submitReset'),
  );
  $form['filters']['status']['export'] = array(
    '#type' => 'submit',
    '#value' => t('Export to CSV'),
    '#submit' => array('Drupal\sign_for_acknowledgement\Form\FilterForm::submitCsv'),
  );

  return $form;
}
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
	  //echo $form_state->getValue('export').'='.$_POST['submit'] ;exit;
  }
  public static function submitFilter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $node =  \Drupal\node\Entity\Node::load($form_state->getValue('node'));
	$filters = self::filters($node);
	$session_name = self::sessionName($node);
    foreach ($filters as $filter => $options) {
      if ($form_state->getValue($filter) != '[any]') {
        // Merge an array of arrays into one if necessary.
        $options = $filters[$filter]['options'];
        // Only accept valid selections offered on the dropdown, block bad input.
        if (isset($options[$form_state->getValue($filter)])) {
          $_SESSION[$session_name][$filter] = $form_state->getValue($filter);
        }
      }
    }
  }
  public static function submitReset(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $node =  \Drupal\node\Entity\Node::load($form_state->getValue('node'));
	$filters = self::filters($node);
	$session_name = self::sessionName($node);
    foreach ($filters as $filter => $options) {
      $_SESSION[$session_name][$filter] = 'any';
    }
  }
  public static function submitCsv(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = \Drupal::config('sign_for_acknowledgement.settings');
    $node =  \Drupal\node\Entity\Node::load($form_state->getValue('node'));
    $session_name = self::sessionName($node);
    $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
    $fieldman = \Drupal::service('sign_for_acknowledgement.field_manager');
    $timestamp = $fieldman->expirationDate(TRUE, $node->id(), $node);
    $header_cells = array();
    $rows = array();
    $dbman->outdata($node, $timestamp, $session_name, $header_cells, $rows, TRUE);

    $filename = $node->getTitle() . '.csv';
    $filename = self::convertToFilename($filename);

    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Description: File Transfer');
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename={$filename}");
    header("Expires: 0");
    header("Pragma: public");

    $separator = $config->get('separator');
    $fh = @fopen('php://output', 'w');
    $firstrow = '"' . $node->getTitle() . '"'; //trim(theme('sign_for_acknowledgement_pre_csv', array('node' => $node)));
    if (!(empty($firstrow))) {
      fwrite($fh, $firstrow);
      for ($i = 0; $i < count($header_cells) - 1; $i++) {
        fwrite($fh, $separator);
      }
      fwrite($fh, "\n");
    }
    fputcsv($fh, $header_cells, $separator);
    foreach ($rows as $row) {
      // Put the data into the stream
      fputcsv($fh, $row, $separator);
    }
    // Close the file
    fclose($fh);
    // Make sure nothing else is sent, our file is done
    exit;
  }
  /**
  * @param string original string
  * @return string the input string without accents
  */   
  public static function removeAccents($str)
  {
    return \Drupal::service('transliteration')->transliterate($str);
  }
 /**
  * @param string original string
  * @return string the input string converted to filename
  */   
  public static function convertToFilename($string) {
    //$string = utf8_decode(strtolower($string));
    $string = self::removeAccents(strtolower($string));
    $string = str_replace  (" ", "_", $string);
    $string = str_replace  ("..", ".", $string);
    preg_replace  ("/[^0-9^a-z^_^.]/", "", $string);
    return $string;
  }
}
