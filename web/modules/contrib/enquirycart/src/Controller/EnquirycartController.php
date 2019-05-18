<?php

namespace Drupal\enquirycart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enquiry contoller class.
 */
class EnquirycartController extends ControllerBase {

  private $config;

  private $request;

  /**
   * Constructor to set the config.
   */
  public function __construct(RequestStack $request_stack) {
    $this->config = $this->config('enquirycart.settings');
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
            $container->get('request_stack')
            );
  }

  /**
   * Getconfig title and return  .
   */
  public function getTitle() {

    $title = $this->config->get('title');

    return $title;
  }

  /**
   * Get enquiry basket.
   */
  public  function getEnquiryBasket() {

    $session = $this->request->getSession();

    $arraychgeck = NULL;
    $value = $session->get('enquire');

    if (!empty($value)) {

      $values['addproducts'] = [
        '#type' => 'markup',
        '#prefix' => '<div class="enquiremessge-full">',
        '#suffix' => '</div>',
        '#markup' => $this->config->get('instructions.basketfull'),
      ];

      $arraychgeck = array_chunk($value, 1);

      $arraykeys = array_keys($value);
      foreach ($arraychgeck as $key => $value) {
        $options['attributes'] = ['rel' => 'nofollow'];
        $value['operation'] = Link::fromTextAndUrl($this->t('Delete'), Url::fromRoute('enquirycart.deleteEnquiryBasket', ['eid' => $arraykeys[$key]], $options));
        $arraychgeck[$key] = $value;
      }

      $values['basket'] = [
        '#type' => 'table',
        '#header' => [$this->t('Product Names')],
        '#default' => 'No products have been added to the basket',
        '#rows' => (!empty($arraychgeck)) ? $arraychgeck : ['No products have been added to the basket'] ,
      ];

      $builtForm = $this->formBuilder()->getForm('Drupal\enquirycart\Form\EnquiryForm');
      $values['form'] = $builtForm;

    }
    else {

      $values['noproductsinbasket'] = [
        '#type' => 'markup',
        '#prefix' => '<div class="enquiremessge-empty">',
        '#suffix' => '</div>',
        '#markup' => $this->config->get('instructions.basketempty'),
        '#weight' => -1,
      ];

    }

    return $values;
  }

  /**
   * Delete item from the basket.
   *
   * @param int $eid
   *   URL value passsed from enquirybasket/{eid}/delete.
   */
  public function deleteFromEnquiryBasket($eid) {

    $session = $this->request->getSession();

    $value = $session->get('enquire');

    if (isset($value[$eid])) {
      $message = $this->t("'@prod' has been removed from the enquiry basket.", ["@prod" => $value[$eid]]);
      unset($value[$eid]);
      $session->set('enquire', $value);
      drupal_set_message($message);

    }

    return $this->redirect('enquirycart.getEnquiryBasket');
  }

}
