# Commerce paytrail
[![Build Status](https://travis-ci.org/tuutti/commerce_paytrail.svg?branch=8.x-2.x)](https://travis-ci.org/tuutti/commerce_paytrail)

## Description
This module integrates [Paytrail](https://www.paytrail.com/en) payment method with Drupal Commerce.

## Installation
`$ composer require drupal/commerce_paytrail`

## Usage

#### How to alter values submitted to the Paytrail

Create new event subscriber that responds to \Drupal\commerce_paytrail\Events\PaytrailEvents::FORM_ALTER.
