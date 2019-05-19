<?php

namespace Drupal\smallads\Form;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\smallads\Entity\SmalladInterface;
use Drupal\smallads\Entity\Smallad;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Form to create/edit a smallad entity.
 */
class SmalladEdit extends ContentEntityForm {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $params;

  /**
   * @var DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a SmalladEdit object.
   */
  public function __construct($entity_manager, $entity_type_bundle_info, TimeInterface $time, RouteMatchInterface $routeMatch, RequestStack $requestStack, DateFormatter $date_formatter) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->routeMatch = $routeMatch;
    $this->params = $requestStack->getCurrentRequest()->query;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('date.formatter')
    );
  }



  /**
   * Overrides Drupal\Core\Entity\ContentEntityForm::form().
   *
   * @todo find out what happened to drupal_set_title
   * @todo set the min and max dates on $this->entity->expires - but how?
   */
  public function form(array $form, FormStateInterface $form_state) {
//$form['#title'] = 'test title';
    if ($this->params->has('title')) {
      $this->entity->title->value = $this->params->get('title');
    }
    $form = parent::form($form, $form_state);
//mdump($form['expires']);

    if ($this->entity->isNew() && $this->routeMatch->getParameter('taxonomy_term')) {
      $form['type'] = [
        '#type' => 'value',
        '#value' => $this->routeMatch->getParameter('taxonomy_term'),
      ];
    }
    // @todo shouldn't this be done in a field hook?
    $form['external_link']['widget'][0]['uri']['#field_prefix'] = 'http://';

    // Move the scope and expires widgets into one details field.
    $form['published'] = [
      '#title' => $this->t('Visibilty'),
      '#description' => $this->t("When the ad expires, the scope reverts to 'Owner only'."),
      '#type' => 'details',
      '#open' => $this->entity->scope->value > 0,
      '#weight' => 25,
      'expires' => $form['expires'],
      'scope' => $form['scope'],
    ];
    unset($form['expires']);
    unset($form['scope']);

    $form['uid']['widget'][0]['target_id']['#access'] = \Drupal::currentUser()->hasPermission('edit all smallads');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {

    // entity->expires->getValue() is a dateTime Object
    // this does createDateFromFormat.
    $this->entity = parent::validate($form, $form_state);
    // @todo entity->expires->getValue()is [0] => Array([value] => 2015-06-24)

    // couldn't be bothered to do entity validation, only form validation.
    $diff = strtotime($this->entity->expires->value) > REQUEST_TIME;
    // Expires in the future.
    if ($diff > 0) {
      if ($this->entity->scope == SmalladInterface::SCOPE_PRIVATE) {
        $message = $this->t('If the expiry date is after now, the scope must not be private');
        $form_state->setErrorByName('scope', $message);
      }
    }
    // Expires in the past.
    else {
      if ($this->entity->scope != SmalladInterface::SCOPE_PRIVATE) {
        $message = $this->t('If the article is to be visible, the expiry must be AFTER now.');
        $form_state->setErrorByName('scope', $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $now = $this->time->getRequestTime();
    $type_label = $this->entity->type->entity->label();
    if ($this->entity->expires->value > $now) {
      $diff = $this->entity->expires->value - $now;
      $this->messenger()->addStatus(
        $this->t(
          'Your @ad_type will expire in @interval.',
          [
            '@ad_type' => $type_label,
            '@interval' => $this->dateFormatter->formatInterval($diff, 2)
          ]
        )
      );
    }
    else {
      $this->entity->expire();
      $this->messenger()->addStatus(
        t(
          'This @ad_type is invisible because it expired on @date',
          ['@ad_type' => $type_label, '@date' => date("d M Y", $this->entity->expires->value)]
        ),
        'warning'
      );
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirect('entity.smallad.canonical', ['smallad' => $this->entity->id()]);
  }

}
