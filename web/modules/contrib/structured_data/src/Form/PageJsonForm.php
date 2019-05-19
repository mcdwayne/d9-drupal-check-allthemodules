<?php

namespace Drupal\structured_data\Form;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PageJsonForm extends FormBase
{

  public function getFormId()
  {
    return 'structured_data_page_json_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $entity = $this->getEntity();

    $form['route_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Route Name'),
      '#required' => TRUE,
      '#default_value' => $entity->route_name,
    );

    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('Url'),
      '#required' => FALSE,
      '#default_value' => $entity->url,
    );

    $form['bundle'] = array(
      '#type' => 'textfield',
      '#title' => 'Bundle',
      '#required' => FALSE,
      '#default_value' => $entity->bundle == \Drupal\structured_data\Core\Helper::emptyBundle ? '' : $entity->bundle,
    );

    $form['entity_id'] = array(
      '#type' => 'textfield',
      '#title' => 'Entity Id',
      '#required' => FALSE,
      '#default_value' => $entity->entity_id == '0' ? '' : $entity->entity_id,
    );

    $form['json'] = array(
      '#type' => 'textarea',
      '#title' => t('Json'),
      '#required' => FALSE,
      '#default_value' => $entity->json,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#button_type' => 'primary',
    );

    return ($form);
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $entity = array(
      'route_name' => $form_state->getValue('route_name'),
      'url' => $form_state->getValue('url'),
      'bundle' => $form_state->getValue('bundle'),
      'entity_id' => $form_state->getValue('entity_id'),
      'json' => $form_state->getValue('json'),
      'updated_by' => \Drupal::currentUser()->id(),
      'updated_time' => time(),
    );

    \Drupal\structured_data\Core\Helper::updatePageJson($entity);

    drupal_set_message(t('Page Json updated successfully.'));

    if (!empty($entity['url']))
    {
      $url = Url::fromUri('internal:' . $entity['url']);
      $form_state->setRedirectUrl($url);
    }
  }

  private function getEntity()
  {
    $route_name = \Drupal::routeMatch()->getParameter('sd_route_name');
    $url = \Drupal::routeMatch()->getParameter('sd_url');
    $bundle = \Drupal::routeMatch()->getParameter('sd_bundle');
    $entity_id = \Drupal::routeMatch()->getParameter('sd_entity_id');

    $url = str_replace('|', '/', $url);
    $url = base64_decode($url);

    $entity = \Drupal\structured_data\Core\Helper::getPageJson([
        'route_name' => $route_name,
        'url' => $url,
        'bundle' => $bundle,
        'entity_id' => $entity_id,
    ]);

    if (empty($entity))
    {
      $entity = new \stdClass();
      $entity->route_name = $route_name;
      $entity->url = $url;
      $entity->bundle = $bundle;
      $entity->entity_id = $entity_id;
      $entity->json = '';
    }

    return($entity);
  }

}
