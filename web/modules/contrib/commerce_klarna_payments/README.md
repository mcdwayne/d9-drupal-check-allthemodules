# Commerce Klarna Payments

[![Build Status](https://gitlab.com/tuutti/commerce_klarna_payments/badges/8.x-1.x/pipeline.svg)](https://gitlab.com/tuutti/commerce_klarna_payments)

## Description

This module integrates [Klarna Payments](https://www.klarna.com/) payment method with Drupal Commerce.

## Installation

`$ composer require drupal/commerce_klarna_payments`

## Usage

#### How to add/alter values before sending them to Klarna

See `\Drupal\commerce_klarna_payments\Event\Events` for available events and `\Drupal\commerce_klarna_payments\Event\RequestEvent` for available methods.
