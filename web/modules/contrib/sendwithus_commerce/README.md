# sendwithus (commerce)
[![Build Status](https://travis-ci.org/tuutti/sendwithus_commerce.svg?branch=8.x-1.x)](https://travis-ci.org/tuutti/sendwithus_commerce)

## Description

This modules integrates [Sendwithus](https://drupal.org/project/sendwithus) with Drupal commerce.

## Variable replacement

This module provides the following variable replacements:

| Variable | Description | Examples |
|----------|-------------|----------|
| {{ order.id }} | The order ID | 1 |
| {{ order.mail }} | The email address associated with the order  | admin@example.com |
| {{ order.type }} | The order type | default |
| {{ order.customer.id }} | The customer ID | 5 |
| {{ order.customer.name }} | The customer's account name | admin |
| {{ order.customer.ip }} | The IP address of the order | 127.0.0.1 |
| {{ order.store.id }} | The store ID | 1 |
| {{ order.store.label }} | Store's name | Default store |
| {{ order.store.type }} | Store's type | online |
| {{ order.adjustments }} | An array containing adjustments. See [Adjustments](#adjustments) for more information | [] |
| {{ order.items }} | An array containing order items. See [Order items](#order-items) for more information | [] |
| {{ order.is_locked }} | Boolean indicating whether the order is locked | false |
| {{ order.created }} | Unix timestamp of order's created date | 1519278424 |
| {{ order.placed }} | Unix timestamp of when order was placed | 1519278424 |
| {{ order.completed }} | Unix timestamp of when order was completed | 1519278424 |
| {{ order.state.label }} | The order state | Draft |
| {{ order.state.value }} | Machine name of the order state | draft |
| {{ order.totals.subtotal }} | The subtotal price. See [Price](#price) for more information | |
| {{ order.totals.total }} | The total price. See [Price](#price) for more information | |
| {{ order.totals.adjustments }} | The order adjustments. See [Adjustment](#adjustments) for more information | |
| {{ order.payment_method.label }} | The payment method's label | Visa |
| {{ order.payment_method.id }} | The payment method's ID | 1 |
| {{ order.payment_method.created | The creation timestamp | 1519278424 |
| {{ order.payment_method.type.id | | 2 |
| {{ order.payment_method.type.label }} | | Credit card |
| {{ order.payment_gateway.label }} | The payment gateway's label | Klarna |
| {{ order.payment_gateway.id }} | The payment gateway's ID | klarna |
| {{ order.billing.address.country_code }} | The two-letter country code | US |
| {{ order.billing.address.administrative_area }} | IE. the state or province | DC |
| {{ order.billing.address.locality }} | IE. City | Washington |
| {{ order.billing.address.dependent_locality }} |  The dependent locality (i.e neighbourhood) | Whaley, Langwith |
| {{ order.billing.address.postal_code }} | The postal code | 00100 |
| {{ order.billing.address.sorting_code }} | The sording code | CEDEX |
| {{ order.billing.address.address_line1 }} | The first line of address block | The White House |
| {{ order.billing.address.address_line2 }} | The second line of address block | 1600 Pennsylvania Avenue |
| {{ order.billing.address.organization }} | The organization | Druid Oy |
| {{ order.billing.address.given_name }} | The given name (i.e first name) | Matti |
| {{ order.billing.address.additional_name }} | The additional name (i.e middle name) | Juhani |
| {{ order.billing.address.family_name }} | The family name (i.e last name) | Meikäläinen |
| {{ order.billing.address.locale }} | The locale | |

## Price 

Shared fields that every price field contains:

| Variable | Description | Examples |
|----------|-------------|----------|
| {{ variable.number }} | The amount | 125.55 |
| {{ variable.currency_code }} | The currency code | EUR |

Replace `variable` with the variable name you wish to use, for example: `order.totals.total`.



## Adjustments

This assumes that you are iterating through an array of adjustments, for example: 

```
{% for item in order.adjustments %}
{% endfor %}
```
| Variable | Description | Examples |
|----------|-------------|----------|
| {{ item.type }} | The adjustment type| tax |
| {{ item.amount }} | The adjustment amount. See [Price](#price) for more information | |
| {{ item.percentage }} | The percentage. This is used for promotions / taxes | 5 |
| {{ item.label }} | The adjustment's label | Promotion -50% |

## Order items

This assumes that you are iterating through an array of order items: 

```
{% for item in order['items'] %}
{% endfor %}
```

| Variable | Description | Examples |
|----------|-------------|----------|
| {{ item.id }} | The order item ID | 1 |
| {{ item.label }} | The order item label | Green T-Shirt |
| {{ item.quantity }} | The quantity | 2 |
| {{ item.unit_price }} | The unit price. See [Price](#price) for more information | |
| {{ item.is_unit_price_overridden }} | Boolean indicating whether the unit price is overridden | false |
| {{ item.adjusted_unit_price }} | The adjusted unit price. See [Price](#price) for more information | |
| {{ item.adjustments }} | See [Adjustments](#adjustments) for more information | |
| {{ item.total_price }} | The total price (without adjustments). See [Price](#price) for more information | |
| {{ item.adjusted_total_price }} | The adjusted total price (including adjustments). See [Price](#price) for more information | |
| {{ item.created }} | Unix timestamp of when order item was created | 1519278424 |
| {{ item.purchased_entity.id }} | Purchased entity's ID (i.e product variation ID) | 128 |
| {{ item.purchased_entity.label }} | The purchased entity's label | Green T-Shirt |
| {{ item.purchased_entity.type }} | The bundle | default |
| {{ item.purchased_entity.price }} | The purchased entity's price. See [Price](#price) for more information | |

## Replicate order receipt

Create the following template (/admin/config/services/sendwithus):

| Template ID | Key | Module |
|-------------|-----|--------|
| *your template id* | receipt  | Commerce order |

```twig
{% macro format_currency(value, currency_code) -%}
   {# Replace locale with the one you wish to use (fi_FI for example) #} 
   {{ swu.lib.babel.numbers.format_currency(value, currency_code|default('EUR'), locale='eu') }}
{%- endmacro %}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  </head>

  <body>
<table style="margin: 15px auto 0 auto; max-width: 768px; font-family: arial,sans-serif">
  <tbody>
  <tr>
    <td>
      <table style="margin-left: auto; margin-right: auto; max-width: 768px; text-align: center;">
        <tbody>
        <tr>
          <td>
            <a data-click-track-id="3076" href="{{ site.url }}" style="color: #0e69be; text-decoration: none; font-weight: bold; margin-top: 15px;">{{ order.store.label }}</a>
          </td>
        </tr>
        </tbody>
      </table>
      <table style="text-align: center; min-width: 450px; margin: 5px auto 0 auto; border: 1px solid #cccccc; border-radius: 5px; padding: 40px 30px 30px 30px;">
        <tbody>
        <tr>
          <td style="font-size: 30px; padding-bottom: 30px">{% trans %}Order Confirmation{% endtrans %}</td>
        </tr>
        <tr>
          <td style="font-weight: bold; padding-top:15px; padding-bottom: 15px; text-align: left; border-top: 1px solid #cccccc; border-bottom: 1px solid #cccccc">
            {% set order_number = order.order_number %}
            {% trans %}Order #{{ order_number }} details:{% endtrans %}
          </td>
        </tr>
        <tr>
          <td>
            <table style="padding-top: 15px; padding-bottom:15px; width: 100%">
              <tbody style="text-align: left;">
              {% for item in order['items'] %}
              <tr>
                <td>
                  {{ item.quantity }} x
                </td>
                <td>
                  <span>{{ item.label }}</span>
                  <span style="float: right;">{{ format_currency(item.total_price.number, item.total_price.currency_code) }}</span>
                </td>
              </tr>
              {% endfor %}
              </tbody>
            </table>
          </td>
        </tr>
        <tr>
          <td>
            {% if order.billing %}
            <table style="width: 100%; padding-top:15px; padding-bottom: 15px; text-align: left; border-top: 1px solid #cccccc; border-bottom: 1px solid #cccccc">
              <tbody>
              <tr>
                {% if order.billing.address %}
                  <td style="padding-top: 5px; font-weight: bold;">{% trans %}Billing Information{% endtrans %}</td>
                {% endif %}
              </tr>
              <tr>
                  <td>
                    <p>
                      <span>{{ order.billing.address.given_name }}</span>
                      {% if order.billing.address.additional_name %} 
                      <span>{{ order.billing.address.additional_name }} </span>
                      {% endif %} 
                      <span>{{ order.billing.address.family_name }}</span>
                    </p>
                    <p>{{ order.billing.address.address_line1 }}</p>
                      {% if order.billing.address.address_line2 %}
                    <p>{{ order.billing.address.address_line2 }}</p>
                      {% endif %}
                    <p>
                      <span>{{ order.billing.address.postal_code }}</span>
                      <span>{{ order.billing.address.locality }}</span>
                    </p>
                  </td>
              </tr>
              {% if order.payment_method or order.payment_gateway %}
                <tr>
                  <td style="font-weight: bold; margin-top: 10px;">{% trans %}Payment Method{% endtrans %}</td>
                </tr>
                <tr>
                  <td>
                    {% if order.payment_method %}
                      {{ order.payment_method.label }}
                    {% endif %}
                    {% if order.payment_gateway %}
                      {{ order.payment_gateway.label }}
                    {% endif %}
                  </td>
                </tr>
              {% endif %}
              </tbody>
            </table>
            {% endif %}
          </td>
        </tr>
        <tr>
          <td>
            <p style="margin-bottom: 0;">
              {% trans %}Subtotal:{% endtrans %} {{ format_currency(order.totals.subtotal.number, order.total.subtotal.currency_code) }}
            </p>
          </td>
        </tr>
        {% for adjustment in order.totals.adjustments %}
        <tr>
          <td>
            <p style="margin-bottom: 0;">
              {{ adjustment.label }}: {{ format_currency(adjustment.total.number, adjustment.total.currency_code) }}
            </p>
          </td>
        </tr>
        {% endfor %}
        <tr>
          <td>
            <p style="font-size: 24px; padding-top: 15px; padding-bottom: 5px;">
              {% trans %}Order Total:{% endtrans %} {{ format_currency(order.totals.total.number, order.totals.total.currency_code) }}
            </p>
          </td>
        </tr>
        <tr>
          <td>
              {% trans %}Thank you for your order!{% endtrans %}
          </td>
        </tr>
        </tbody>
      </table>
    </td>
  </tr>
  </tbody>
</table>
  </body>
</html>

```
