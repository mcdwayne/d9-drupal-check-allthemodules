Stripe Checkout

We intentionally do not use the Form API to generate the checkout form, 
because it is not compatible with Stripe Checkout.
When Checkout submits the form, it does not define a 'triggering_element',
which prevents Drupal core from firing the form's submit handlers.

@see https://www.drupal.org/node/1008644
@see https://www.drupal.org/node/2496197
