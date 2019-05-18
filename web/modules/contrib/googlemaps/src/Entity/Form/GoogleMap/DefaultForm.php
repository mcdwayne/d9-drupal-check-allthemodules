<?php

namespace Drupal\googlemaps\Entity\Form\GoogleMap;

// Entity form.
use Drupal\Core\Entity\EntityForm;
// Form state.
use Drupal\Core\Form\FormStateInterface;
// Entity object.
use Drupal\googlemaps\Entity\GoogleMap;
// Link.
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\GeneratedLink;

class DefaultForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var GoogleMap $entity */
    $entity = $this->getEntity();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#default_value' => $entity->label,
    ];

    $form['name'] = [
      '#type' => 'machine_name',
      '#disabled' => !$entity->isNew(),
      '#default_value' => $entity->name,
      '#machine_name' => [
        'source' => ['label'],
        'exists' => [$entity, 'load'],
      ],
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $entity->key,
      '#description' => $this->t('How to create an application with API key? Read here: @href', [
        '@href' => static::externalLink('https://developers.google.com/maps/documentation/javascript/get-api-key'),
      ]),
    ];

    $form['libraries'] = [
      '#type' => 'select',
      '#title' => $this->t('Libraries'),
      '#multiple' => TRUE,
      '#default_value' => $entity->libraries,
      '#description' => $this->t('Detailed description about the libraries available here: @href', [
        '@href' => static::externalLink('https://developers.google.com/maps/documentation/javascript/libraries'),
      ]),
      '#options' => [
        'places' => $this->t('Places'),
        'drawing' => $this->t('Drawing'),
        'geometry' => $this->t('Geometry'),
        'visualization' => $this->t('Visualization'),
      ],
    ];

    $form['callback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initialization callback'),
      '#description' => $this->t('The name of JS function which will be triggered when API will be completely loaded.'),
      '#default_value' => $entity->callback,
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var GoogleMap $entity */
    $entity = $this->getEntity();

    drupal_set_message($this->t('The @entity_type %label has been @operation.', [
      '@entity_type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label' => $entity->label,
      '@operation' => $entity->isNew() ? $this->t('created') : $this->t('updated'),
    ]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

  /**
   * Create external link.
   *
   * @param string $href
   *   URL string.
   *
   * @return GeneratedLink
   *   Generated link object.
   */
  protected static function externalLink($href) {
    return Link::fromTextAndUrl($href, Url::fromUri($href, ['attributes' => ['target' => '_blank']]))->toString();
  }

}
