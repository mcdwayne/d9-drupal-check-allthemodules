<?php

namespace Drupal\saudi_identity_field\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\saudi_identity_field\Plugin\Validation\Constraint\SaudiIdentityCheckConstraint;

/**
 * Plugin implementation of the 'saudi_identity_type' field type.
 *
 * @FieldType(
 *   id = "saudi_identity",
 *   label = @Translation("Saudi identity"),
 *   description = @Translation("Saudi identity check"),
 *   default_widget = "number",
 *   default_formatter = "number_integer"
 * )
 */
class SaudiIdentityType extends \Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'saudi_identity_type' => array(
        '#type' => 'select',
        '#title' => t('Saudi/Iqama Validator'),
        '#default_value' => 'both',
        '#options' => [],
        '#description' => t('Options above are for you if you only want Saudis ID number select 1st option, for only Muoqeen resident identity number select 2nd one, the 3rd for both of them.'),
      ),
        ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $saudi_identity_options = array(
      'saudi' => t('Saudi Identity Only'),
      'iqama' => t('Iqama Identity Only'),
      'both' => t('Both'),
    );
    return array(
      'saudi_identity_type' => array(
        '#type' => 'select',
        '#title' => t('Saudi/Iqama Validator'),
        '#default_value' => $this->getSetting('saudi_identity_type'),
        '#options' => $saudi_identity_options,
        '#description' => t('Options above are for you if you only want Saudis ID number select 1st option, for only Muoqeen resident identity number select 2nd one, the 3rd for both of them.'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          // Expose the 'unsigned' setting in the field item schema.
          'unsigned' => TRUE,
          // Expose the 'size' setting in the field item schema. For instance,
          // supply 'big' as a value to produce a 'bigint' type.
          'size' => 'normal',
        ),
      ),
    );
  }
  
    /**
   * @todo use property defintion instead of RedirectResponse obj.
   * {@inheritdoc}
   */
  public function preSave() {
    $return = $this->saudi_identity_field_valid($this->value, $this->getSetting('saudi_identity_type'));
    if ( ! $return[0]) {

      drupal_set_message($return[1]->__toString(), 'error');
      $uri = \Drupal::service('path.current')->getPath();

      global $base_url;
      $response = new RedirectResponse($base_url . $uri);
      $response->send();
      exit;
    }
  }

  /**
   * This algorithm validation was designed by:Eng.Abdul-Aziz Al-Oraij @top7up.
   */
  function saudi_identity_field_valid($saudi_identity, $id_type) {
    if (strlen($saudi_identity) !== 10) {
      return array(FALSE, t('Saudi ID numbers must be exactly 10 digits long'));
    }
    $sum = 0;
    $type = substr($saudi_identity, 0, 1);
    if ($type != 2 && $type != 1) {
      return array(FALSE, t('Invalid Saudi identity number'));
    }
    if ($type != 1 && $id_type == 'saudi') {
      return array(FALSE, t('Invalid Saudi ID number'));
    }
    if ($type != 2 && $id_type == 'iqama') {
      return array(FALSE, t('Invalid Iqama number'));
    }

    for ($i = 0; $i < 10; $i ++) {
      if ($i % 2 == 0) {
        $zfodd = str_pad((substr($saudi_identity, $i, 1) * 2), 2, "0", STR_PAD_LEFT);
        $sum += substr($zfodd, 0, 1) + substr($zfodd, 1, 1);
      } else {
        $sum += substr($saudi_identity, $i, 1);
      }
    }
    return ($sum % 10) ? array(FALSE, t('Invalid Saudi identity number')) : array(TRUE, $type);
  }
}
