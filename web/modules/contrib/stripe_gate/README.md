# Overview
This module provides a bridge between Stripe and Drupal on a decoupled approach, to manage subscriptions.

# Installation and configuration
Follow the steps bellow to have the module up and running.
- Install the Stripe PHP SKD by running `composer require stripe/stripe-php`
- Download and install the Drupal module with `composer require drupal/stripe_gate`
- Register to Stripe if you haven't yet
- Copy your keys from https://dashboard.stripe.com/account/apikeys
- Add the test and live keys to `/admin/config/services/stripe` and save

# Creating new subscriptions
In order to create new subscriptions, we need to create products and add a pricing plan to this product. In order to do so, navigate to `/admin/config/services/stripe/products` and add a new product (the fields descriptions are very explanatory).
After having your first product, navigate to `admin/config/services/stripe/plans` and add a new plan based on that product. Done. Your subscription is now created.

# API Endpoints
This module make several endpoints available, so the application layer does not have to request any information directly from Stripe.

## Get the Publishable key
```
# GET: Returns the PKey
/api/stripe/get-pkey
```

## Get the subscription plans
```
# GET: Returns the subscription plans
/api/stripe/get-plans
```

## Creating new customers
```
# POST: Creates a new customer.
# Body needs to contain { id: token_id_generated_by_stripe, email: user_email }
/api/stripe/create-customer
```

## Get the customer given an ID
```
# POST: Gets the customer info.
# Body needs to contain { id: customer_id }
/api/stripe/get-customer
```
