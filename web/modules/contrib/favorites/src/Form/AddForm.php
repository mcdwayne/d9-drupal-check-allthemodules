<?php
/**
 * @file
 * Contains Drupal\favorites\Form\AddForm
 */

namespace Drupal\favorites\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\favorites\FavoriteStorage;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AddForm.
 *
 * @package Drupal\favorites\Form\AddForm
 */
class AddForm extends FormBase {

  use StringTranslationTrait;

  protected $account;

  public function __construct(){
    $this->account = \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'favorites_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // @todo necessary? D8 requires >=5.5.9 anyway?
    if(function_exists('version_compare') && version_compare(PHP_VERSION, '5.1.0', '>=')) {
      $request = \Drupal::request();
      $route_match = \Drupal::routeMatch();
      $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
    }
    if (!isset($title)) {
      $title = \Drupal::config('core.site_information')->get('site_name');
    }
    if ($title == '') {
      $title = $this->t('Home', array(), array('context' => 'Home page'));
    }
    $title = strip_tags($title);
    $path = \Drupal::service('path.current')->getPath();
    $query = (isset($_GET['keys']))?UrlHelper::buildQuery($_GET):'';
    $form = array(
      'add' => array(
        '#type' => 'details',
        '#title' => $this->t('Add this page'),
        'title' => array(
          '#type' => 'textfield',
          '#size' => 20,
          '#maxlength' => 255,
          '#default_value' => $title,
          '#attributes' => array(
            'style' => 'width: 90%',
            'class' => array('favorites-add-textfield'),
          ),
        ),
        'path' => array(
          '#type' => 'hidden',
          '#value' => $path,
        ),
        'query' => array(
          '#type' => 'hidden',
          '#value' => $query,
        ),
        'submit' => array(
          '#type' => 'submit',
          '#value' => $this->t('Add', array(), array('context' => 'Add a favorite to the list')),
          '#ajax'  => array(
            'url' => Url::fromRoute('favorites.add'),
          ),
        ),
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   * @todo obsolete?
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   * @todo obsolete?
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
