<?php

namespace Drupal\fillpdf\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\fillpdf\FillPdfFormInterface;
use Drupal\fillpdf\Service\FillPdfAdminFormHelper;

/**
 * Defines the entity for managing uploaded FillPDF forms.
 *
 * @ContentEntityType(
 *   id = "fillpdf_form",
 *   label = @Translation("FillPDF form"),
 *   handlers = {
 *     "views_data" = "Drupal\fillpdf\FillPdfFormViewsData",
 *     "form" = {
 *       "edit" = "Drupal\fillpdf\Form\FillPdfFormForm",
 *       "delete" = "Drupal\fillpdf\Form\FillPdfFormDeleteForm",
 *       "duplicate" = "Drupal\fillpdf\Form\FillPdfFormDuplicateForm",
 *       "export" = "Drupal\fillpdf\Form\FillPdfFormExportForm",
 *       "import" = "Drupal\fillpdf\Form\FillPdfFormImportForm",
 *     },
 *     "list_builder" = "Drupal\fillpdf\FillPdfFormListBuilder",
 *     "access" = "Drupal\fillpdf\FillPdfFormAccessControlHandler",
 *   },
 *   admin_permission = "administer pdfs",
 *   base_table = "fillpdf_forms",
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "admin_title",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/fillpdf/{fillpdf_form}",
 *     "edit-form" = "/admin/structure/fillpdf/{fillpdf_form}",
 *     "delete-form" = "/admin/structure/fillpdf/{fillpdf_form}/delete",
 *     "duplicate-form" = "/admin/structure/fillpdf/{fillpdf_form}/duplicate",
 *     "export-form" = "/admin/structure/fillpdf/{fillpdf_form}/export",
 *     "import-form" = "/admin/structure/fillpdf/{fillpdf_form}/import",
 *     "collection" = "/admin/structure/fillpdf",
 *   }
 * )
 */
class FillPdfForm extends ContentEntityBase implements FillPdfFormInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('FillPDF Form ID'))
      ->setDescription(t('The ID of the FillPdfForm entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the FillPdfForm entity.'))
      ->setReadOnly(TRUE);

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('The associated managed file.'))
      ->setSetting('file_extensions', 'pdf')
      ->setDescription(t('The associated managed file.'));

    $overview_url = Url::fromUri('base://admin/structure/fillpdf')->toString();
    $fields['admin_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Administrative title'))
      ->setDescription(t('Enter an administrative title to help identifying this FillPDF Form on the <a href="@overview_url">form overview page</a> and in some other places.', ['@overview_url' => $overview_url]))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filename pattern'))
      ->setDescription(new TranslatableMarkup('This pattern will be used for deciding the filename of your PDF. This field supports tokens.'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 10,
      ]);

    // Form element is set up in FillPdfFormForm.
    $fields['default_entity_type'] = BaseFieldDefinition::create('string');

    // Form element is set up in FillPdfFormForm.
    $fields['default_entity_id'] = BaseFieldDefinition::create('integer');

    $fields['destination_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Destination path'))
      ->setDescription(new TranslatableMarkup('You may specify a subdirectory for storing filled PDFs. This field supports tokens.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 21,
        'settings' => [
          'size' => 38,
        ],
      ]);

    // @todo: add post_save_redirect field for where to send the browser by default after they generate a PDF

    $fields['scheme'] = BaseFieldDefinition::create('list_string')
      ->setLabel('File storage')
      ->setSettings([
        'allowed_values_function' => [get_called_class(), 'getStorageSchemeOptions'],
      ])
      ->setDefaultValueCallback(static::class . '::getStorageSchemeDefault')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 20,
      ]);

    $fields['destination_redirect'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Redirect browser directly to saved PDF'))
      ->setDescription(t("Instead of redirecting your visitors to the front page, this will redirect them directly to the PDF. However, if you pass Drupal's <em>destination</em> query string parameter, that will override this setting."))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 30,
        'settings' => [
          'display_label' => TRUE,
        ],
      ]);

    // Restore database and make this be string_long instead like
    // FillPdfFormField.
    $fields['replacements'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Change text before sending to PDF (Transform values)'))
      ->setDescription(FillPdfAdminFormHelper::getReplacementsDescription())
      ->setDisplayOptions('form', [
        'type' => 'string_long',
        'weight' => 40,
      ]);

    return $fields;
  }

  /**
   * Acts on FillPdfForms before they are deleted and before hooks are invoked.
   *
   * Deletes the FillPdfForm's FillPdfFormFields.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param \Drupal\fillpdf\FillPdfFormInterface[] $entities
   *   An array of FillPdfForms.
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $fillpdf_form) {
      \Drupal::entityTypeManager()->getStorage('fillpdf_form_field')->delete($fillpdf_form->getFormFields());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormFields() {
    $field_ids = \Drupal::entityQuery('fillpdf_form_field')
      ->condition('fillpdf_form', $this->id())
      ->execute();
    $field_storage = \Drupal::entityTypeManager()->getStorage('fillpdf_form_field');
    return $field_storage->loadMultiple($field_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageScheme() {
    return $this->get('scheme')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoragePath() {
    return $this->get('destination_path')->value;
  }

  /**
   * Allowed values callback for 'scheme' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return string[]
   *   Associative array of storage scheme descriptions, keyed by the scheme.
   */
  public static function getStorageSchemeOptions() {
    $options = [];
    foreach (self::getAllowedStorageSchemes() as $scheme) {
      $options[$scheme] = $scheme . '://';
    }
    return $options;
  }

  /**
   * Default value callback for 'scheme' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return string
   *   The initial default storage scheme.
   */
  public static function getStorageSchemeDefault() {
    $allowed = self::getAllowedStorageSchemes();
    return array_key_exists('private', $allowed) ? 'private' : key($allowed);
  }

  /**
   * Gets a list of all storage schemes that are both available and allowed.
   *
   * @return string[]
   *   List of storage schemes that are both available and allowed.
   */
  protected static function getAllowedStorageSchemes() {
    $available = array_keys(\Drupal::service('stream_wrapper_manager')->getWrappers(StreamWrapperInterface::WRITE_VISIBLE));
    $allowed = \Drupal::config('fillpdf.settings')->get('allowed_schemes') ?: [];
    return array_intersect($available, $allowed);
  }

}
