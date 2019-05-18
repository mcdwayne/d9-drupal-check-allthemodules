<?php

namespace Drupal\field_validation\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * PhoneFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "phone_field_validation_rule",
 *   label = @Translation("Phone"),
 *   description = @Translation("Verifies that user-entered values are phone number.")
 * )
 */
class PhoneFieldValidationRule extends ConfigurableFieldValidationRuleBase {

  /**
   * {@inheritdoc}
   */
   
  public function addFieldValidationRule(FieldValidationRuleSetInterface $field_validation_rule_set) {

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = array(
      '#theme' => 'field_validation_rule_summary',
      '#data' => $this->configuration,
    );
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'country' => NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $countries = $this->phone_countries();
    $country_options = array();
    foreach ($countries as $country_code => $country) {
      $country_options[$country_code] = isset($country['name']) ? $country['name'] : '';
    }  
    $form['country'] = array(
      '#title' => $this->t('Country'),
      '#type' => 'select',
      '#options' => $country_options, 
      '#default_value' => $this->configuration['country'],
    );
	
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['country'] = $form_state->getValue('country');
  }
  
  public function validate($params) {
    $value = isset($params['value']) ? $params['value'] : '';
	$rule = isset($params['rule']) ? $params['rule'] : null;
	$context = isset($params['context']) ? $params['context'] : null;
	$settings = array();
	if(!empty($rule) && !empty($rule->configuration)){
	  $settings = $rule->configuration;
	}
    if ($value !== '' && !is_null($value)) {
      $country_code = isset($settings['country']) ? $settings['country'] : '';
      $country_regex = '';
      $countries = $this->phone_countries();
      $country_regex = isset($countries[$country_code]['regex']) ? $countries[$country_code]['regex'] : '';
      if (!preg_match($country_regex, $value)) {
        $context->addViolation($rule->getErrorMessage());
      }

    }	
	
  }
  
  public function phone_countries() {
   $countries = array(
      'fr' => array(
        'name' => $this->t('France'),
        'regex' => '/(\+33|0)([1-9]\d{8}|85\d{7}|87[0-57-9]\d{6})$/',
      ),
      'be' => array(
        'name' => $this->t('Belgium'),
        'regex' => '/^(\+32|0)[1-9]\d{7,8}$/i',
      ),
      'it' => array(
        'name' => $this->t('Italy'),
        'regex' => "/^(\+39)?[ ]?([0-9]{2,3}(\/|-| )?[0-9]{6,7})$/i",
      ),
      'el' => array(
        'name' => $this->t('Greece'),
        'regex' => "/^(\+30)?[ ]?([0-9]{3,4}(\/|-| )?[0-9]{6,7})$/i",
      ),
      'ch' => array(
        'name' => $this->t('Switzerland'),
        'regex' => "%(\+41|0|0041)([2-9]\d{8})$%",
      ),
      'ca' => array(
        'name' => $this->t('US & Canada'),
        'regex' => '/\D*(\d*)\D*[2-9][0-8]\d\D*[2-9]\d{2}\D*\d{4}\D*\d*\D*/x',
      ),
      'cr' => array(
        'name' => $this->t('Costa Rica'),
        'regex' => "/(00)?[\s|-]?((\+)?[\s|-]?[0-9]{3})?[\s|-]?([0-9]{2})[\s|-]?([0-9]{2})[\s|-]?([0-9]{2})[\s|-]?([0-9]{2})[\s|-]?/",
      ),
      'pa' => array(
        'name' => $this->t('Panama'),
        'regex' => '/((00|\+)?[0-9]{3}[\s])?([0-9]{3,4})[\s|-]?([0-9]{4})/',
      ),
      'gb' => array(
        'name' => $this->t('Great Britain - United Kingdom'),
        'regex' => "/((^\+44\s?(\(0\))?\d{4}|^\(?0\d{4}\)?){1}\s?\d{3}\s?\d{3}|(^\+44\s?(\(0\))?\d{3}|^\(?0\d{3}\)?){1}\s?\d{3}\s?\d{4}|(^\+44\s?(\(0\))?\d{2}|^\(?0\d{2}\)?){1}\s?\d{4}\s?\d{4}|(^\+44\s?(\(0\))?\d{1}|^\(?0\d{1}\)?){1}\s?\d{5}\s?\d{5})(\s?\#\d*)?/x",
      ),
      'ru' => array(
        'name' => $this->t('Russia'),
        'regex' => "/\D*[78]?\D*\d{3,5}\D*\d{1,3}\D*\d{2}\D*\d{2}\D*/x",
      ),
      /*
      'ua' => array(
        'name' => t('Ukraine'),
        'regex' => '',
      ), */
      'es' => array(
        'name' => $this->t('Spain'),
        'regex' => '/^[0-9]{2,3}-? ?[0-9]{6,7}$/',
      ),
      /*
      'au' => array(
        'name' => t('Australia'),
        'regex' => '',
      ), */
      'cs' => array(
        'name' => $this->t('Czech Republic'),
        'regex' => '/^((?:\+|00)420)? ?(\d{3}) ?(\d{3}) ?(\d{3})$/',
      ),
      'hu' => array(
        'name' => $this->t('Poland - mobiles only'),
        'regex' => "/\D*(?:\+?36|06)?(\d\d?)\D*(\d{3})\D*(\d{3,4})\D*$/x",
      ),
      'pl' => array(
        'name' => $this->t('Poland'),
        'regex' => "/^(\+48\s+)?\d{3}(\s*|\-)\d{3}(\s*|\-)\d{3}$/i",
      ),
      'nl' => array(
        'name' => $this->t('Netherland'),
        'regex' => '/([0]{1}[6]{1}[-\s]+[1-9]{1}[\s]*([0-9]{1}[\s]*){7})|([0]{1}[1-9]{1}[0-9]{2}[-\s]+[1-9]{1}[\s]*([0-9]{1}[\s]*){5})|([0]{1}[1-9]{1}[0-9]{1}[-\s]+[1-9]{1}[\s]*([0-9]{1}[\s]*){6})/x',
      ),
      'se' => array(
        'name' => $this->t('Sweden'),
        'regex' => "/^(([+]\d{2}[ ][1-9]\d{0,2}[ ])|([0]\d{1,3}[-]))((\d{2}([ ]\d{2}){2})|(\d{3}([ ]\d{3})*([ ]\d{2})+))$/i",
      ),
      'za' => array(
        'name' => $this->t('South Africa'),
        'regex' => '/^((?:\+27|27)|0)[ ]*((\d{2})(-| )?(\d{3})(-| )?(\d{4})|(\d{2})( |-)(\d{7}))$/',
      ),
      /*
      'il' => array(
        'name' => t('Israel'),
        'regex' => '',
      ), */
      /*
      'nz' => array(
        'name' => t('New Zealand'),
        'regex' => '',
      ), */
      'br' => array(
        'name' => $this->t('Brazil'),
        'regex' => "/^(\+|0{2}|)?(55|0|)[ -.]?((\(0?[1-9][0-9]\))|(0?[1-9][0-9]))[ -.]?([1-9][0-9]{2,3})[ -.]?([0-9]{4})$/",
      ),
      'cl' => array(
        'name' => $this->t('Chile'),
        'regex' => "/^((\(\d{3}\) ?)|(\d{3}-)|(\(\d{2}\) ?)|(\d{2}-)|(\(\d{1}\) ?)|(\d{1}-))?\d{3}-(\d{3}|\d{4})$/i",
      ),
      'cn' => array(
        'name' => $this->t('China'),
        'regex' => '/^(\+86|86)?( |-)?([0-9]{11}|([0-9]{3,4}(\-|\.| )[0-9]{3,8})|[0-9]{2}( |\-)[0-9]{4}[ ][0-9]{4}|[0-9]{2}\.[0-9]{2}\.[0-9]{2}\.[0-9]{2}\.[0-9]{2})$/',
      ),
      /*
      'hk' => array(
        'name' => t('Hong-Kong'),
        'regex' => '',
      ), 
      'mo' => array(
        'name' => t('Macao'),
        'regex' => '',
      ),  */
      'ph' => array(
        'name' => $this->t('The Philippines'),
        'regex' => "/((^\+63\s?\(?\d{5}\)?|^\(?\d{5}\)?){1}\s?\d{3}(\S?|\s?)?\d{4}|(^\+63\s?\(?\d{4}\)?|^\(?\d{4}\)?){1}\s?\d{3}(\S?|\s?)?\d{4}|(^\+63\s?\(?\d{3}\)?|^\(?\d{3}\)?){1}\s?\d{3}(\S?|\s?)?\d{4}|(^\+63\s?\(?\d{2}\)?|^\(?\d{2}\)?){1}\s?\d{3}(\S?|\s?)?\d{4}|(^\+63\s?\(?\d{1}\)?|^\(?\d{1}\)?){1}\s?\d{3}(\S?|\s?)?\d{4})(\s?\#\d*)?/x",
      ),
      'sg' => array(
        'name' => $this->t('Singapore'),
        'regex' => '/^(\+65)?\s?[689]\d{7}$/i',
      ),
      'jo' => array(
        'name' => $this->t('Jordan'),
        'regex' => "/(^(\+962|00962|962|0)[-\s]{0,1}[7]{1}[7-9]{1}[0-9]{7}$) | (^(\+962|00962|962|0)[-\s]{0,1}[2-6][-\s]{0,1}[0-9]{7}$)/x",
      ),
      /*
      'eg' => array(
        'name' => t('Egypt'),
        'regex' => '',
      ), */
      'pk' => array(
        'name' => $this->t('Pakistan'),
        'regex' => "/^(\+)?([9]{1}[2]{1})?-? ?(\()?([0]{1})?[1-9]{2,4}(\))?-? ??(\()?[1-9]{4,7}(\))?$/i",
      ),
      'in' => array(
        'name' => $this->t('India'),
        'regex' => "/^((\+*)((0[ -]+)*|(91 )*)(\d{12}+|\d{10}+))|\d{5}([- ]*)\d{6}$/i",
      ),
      'dk' => array(
        'name' => $this->t('Denmark'),
        'regex' => "/^(([+]\d{2}[ ][1-9]\d{0,2}[ ])|([0]\d{1,3}[-]))((\d{2}([ ]\d{2}){2})|(\d{3}([ ]\d{3})*([ ]\d{2})+))$/i",
      ),
    );
  
    return $countries;
  }  
}
