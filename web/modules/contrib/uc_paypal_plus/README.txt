This is an Ubercart payment gateway module for PayPal Plus.

The module requires Ubercart 4.x and the based PayPal module installed and activated.

Steps:
1) Copy the module in: /modules/ubercart/payment
2) Install the module.
3) Create a twig for the Checkout review page, template name "page--cart--checkout--review.html.twig". (before placing the order)
4) Copy the following structure in the template from the previous step:

        {% if token_paypal_plus == 'false' %}
        {% else %}
          {% if paypal_type == 'live' %}
            {{scriptforppp|raw}}
            {{htmlforiframe|raw}}
            {{scriptforppplive|raw}}
          {% elseif paypal_type == 'sandbox' %}
            {{scriptforppp|raw}}
            {{htmlforiframe|raw}}
            {{scriptforpppsandbox|raw}}
          {% endif %}
        {% endif %}

5) Clear cache.
6) Configure PayPal Plus.
