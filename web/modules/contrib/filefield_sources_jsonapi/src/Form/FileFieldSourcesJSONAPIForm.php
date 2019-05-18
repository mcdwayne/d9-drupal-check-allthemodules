<?php

namespace Drupal\filefield_sources_jsonapi\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class FileFieldSourcesJSONAPIForm.
 *
 * @package Drupal\filefield_sources_jsonapi\Form
 */
class FileFieldSourcesJSONAPIForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\filefield_sources_jsonapi\Entity\FileFieldSourcesJSONAPI $config */
    $config = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config->label(),
      '#description' => $this->t("Label for the JSON API file field sources settings."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\filefield_sources_jsonapi\Entity\FileFieldSourcesJSONAPI::load',
      ],
      '#disabled' => !$config->isNew(),
    ];

    $form['apiUrl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JSON Api URL'),
      '#default_value' => $config->getApiUrl(),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('The JSON API Url for browser.'),
    ];
    $form['params'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Params'),
      '#description' => $this->t('The query parameters. Enter one per line, in the format key|value.<br />E.g.<br />include|field_image<br />fields[media--image]|name,field_category,field_image'),
      '#default_value' => $config->getParams(),
      '#rows' => 10,
    ];
    $form['urlAttributePath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL attribute path'),
      '#description' => $this->t('Enter attribute name for the file URL. E.g. data->relationships->field_image->included->attributes->url'),
      '#default_value' => $config->getUrlAttributePath(),
    ];
    $form['thumbnailUrlAttributePath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnail URL attribute path'),
      '#description' => $this->t('Enter attribute name for the thumbnail file URL. E.g. data->relationships->field_image->included->attributes->thumbnail_url'),
      '#default_value' => $config->getThumbnailUrlAttributePath(),
    ];
    $form['titleAttributePath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title attribute path'),
      '#description' => $this->t('Enter attribute name for the title. E.g. data->field_image->data->meta->title'),
      '#default_value' => $config->getTitleAttributePath(),
    ];
    $form['altAttributePath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alt attribute path'),
      '#description' => $this->t('Enter attribute name for the alt. E.g. data->relationships->field_image->data->meta->alt'),
      '#default_value' => $config->getAltAttributePath(),
    ];
    $form['sortOptionList'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sorting option list'),
      '#description' => $this->t('The possible values for sorting. Enter one value per line, in the format key|label. The first value will be the default. Selector will be displeyed only if you enter more than one.<br />E.g.<br />-created|Newest first<br />name|Name'),
      '#default_value' => $config->getSortOptionList(),
      '#rows' => 5,
    ];
    $form['searchFilter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search filter attribute name'),
      '#description' => $this->t('Enter attribute name for search field. On empty, the search filter will not be active. Multiple fields can be added separated with comma. E.g.: filename,field_category'),
      '#default_value' => $config->getSearchFilter(),
    ];
    $form['itemsPerPage'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Items to display'),
      '#description' => $this->t('Number of items per page for browser.'),
      '#default_value' => $config->getItemsPerPage(),
    ];
    $form['basicAuthentication'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send basic authentication header'),
      '#description' => $this->t('Select if files can be accessed only with basic authentication.'),
      '#default_value' => $config->getBasicAuthentication(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $config = $this->entity;
    $status = $config->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label config.', [
          '%label' => $config->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label config.', [
          '%label' => $config->label(),
        ]));
    }
    $form_state->setRedirectUrl(Url::fromRoute('entity.filefield_sources_jsonapi.collection'));
  }

}
