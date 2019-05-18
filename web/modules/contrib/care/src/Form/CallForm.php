<?php

namespace Drupal\care\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a test form object.
 */
class CallForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'care_call_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('care.settings');

    // Set defaults.
    if (!$form_state->getValue('method', '')) {
      $form_state->setValue('method', 'SelectContactData');
      $form_state->setValue('parameters', '');
      $form_state->setValue('getlookupdata_type', 'xldtTitles');
      $form_state->setValue('selectcontactdata_type', 'xcdtContactInformation');
      $form_state->setValue('other_method', 'SelectPaymentPlanData');
      $form_state->setValue('other_type_name', 'pSelectDataType');
      $form_state->setValue('other_type_value', 'xpdtPaymentPlanPayments');
    }
    $doc_root = $config->get('care_doc_root');

    $form['intro'] = array(
      '#prefix' => '<p>',
      '#markup' => t('Use this form to test CARE calls. Refer to the @documentation for details of the required call types and parameters.', array(
        '@documentation' => Link::fromTextAndUrl(t('CARE API documentation'), Url::fromURI($doc_root . '/WEBServicesSummary.htm'))->toString(),
      )),
      '#suffix' => '</p>',
    );

    if ($form_state->getValue('care_call_result', FALSE)) {
      $form['care_call_result_display'] = array(
        '#title' => t('CARE call result'),
        '#type' => 'textarea',
        '#rows' => 20,
        '#attributes' => array(
          'class' => array(
            'carexmlresult',
          ),
        ),
        '#value' => $form_state->getValue('care_call_result'),
      );
    }

    $form['method'] = array(
      '#title' => t('Call Method'),
      '#type' => 'radios',
      '#options' => array(
        'SelectContactData' => t('SelectContactData (@doc)', array(
          '@doc' => Link::fromTextAndUrl(t('Documentation'), Url::fromURI($doc_root . '/SelectContactData.htm'))->toString(),
        )),
        'GetLookupData' => t('GetLookupData (@doc)', array(
          '@doc' => Link::fromTextAndUrl(t('Documentation'), Url::fromURI($doc_root . '/GetLookupData.htm'))->toString(),
        )),
        'FindContacts' => t('FindContacts (@doc)', array(
          '@doc' => Link::fromTextAndUrl(t('Documentation'), Url::fromURI($doc_root . '/FindContacts.htm'))->toString(),
        )),
        'FindMembers' => t('FindMembers (@doc)', array(
          '@doc' => Link::fromTextAndUrl(t('Documentation'), Url::fromURI($doc_root . '/FindMembers.htm'))->toString(),
        )),
        'other' => t('Other'),
      ),
      '#default_value' => $form_state->getValue('method'),
    );
    $form['selectcontactdata_type'] = array(
      '#title' => t('SelectDataType'),
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('selectcontactdata_type'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array(
            'value' => 'SelectContactData',
          ),
        ),
      ),
    );
    $form['getlookupdata_type'] = array(
      '#title' => t('LookupDataType'),
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('getlookupdata_type'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array(
            'value' => 'GetLookupData',
          ),
        ),
      ),
    );
    $form['other_method'] = array(
      '#title' => t('Method Name'),
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('other_method'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array(
            'value' => 'other',
          ),
        ),
      ),
    );
    $form['other_type_name'] = array(
      '#title' => t('Data Type Name'),
      '#type' => 'radios',
      '#options' => array(
        'pSelectDataType' => 'SelectDataType',
        'pDataType' => 'DataType',
      ),
      '#description' => t('See documentation for the specific method, but this is usually SelectDataType.'),
      '#default_value' => $form_state->getValue('other_type_name'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array(
            'value' => 'other',
          ),
        ),
      ),
    );
    $form['other_type_value'] = array(
      '#title' => t('Data Type Value'),
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('other_type_value'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array(
            'value' => 'other',
          ),
        ),
      ),
    );
    $form['parameters'] = array(
      '#title' => t('XMLParameters'),
      '#description' => t('Enter parameters and values one per line, separated by a colon and optional space.'),
      '#type' => 'textarea',
      '#default_value' => $form_state->getValue('parameters'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Call CARE'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $method = $form_state->getValue('method');
    $lines = preg_split("/\n/", $form_state->getValue('parameters'));
    $data = array();
    $typedata = NULL;
    // Sort out parameter data.
    foreach ($lines as $line) {
      preg_match("/([^:]*):(.*)/", $line, $matches);
      if (isset($matches[1])) {
        $data[trim($matches[1])] = trim($matches[2]);
      }
    }
    // Sort out call type data.
    switch ($method) {
      case 'SelectContactData':
        $typedata = array(
          'pSelectDataType' => $form_state->getValue('selectcontactdata_type'),
        );
        break;

      case 'GetLookupData':
        $typedata = array(
          'pLookupDataType' => $form_state->getValue('getlookupdata_type'),
        );
        break;

      case 'other':
        $method = $form_state->getValue('other_method');
        if ($form_state->getValue('other_type_name')) {
          $typedata = array(
            $form_state->getValue('other_type_name') => $form_state->getValue('other_type_value'),
          );
        }
        break;
    }
    // Do the actual CARE method call.
    $result = care_call_method($method, $data, $typedata);
    $form_state->setValue('care_call_result', _care_pretty_xml($result));
    $form_state->setRebuild();
  }

}
