# twig_killswitch_trigger

This is a response to https://drupal.stackexchange.com/q/271802/57183

## Problem:

How to invalidate/disable (internal) page cache from inside a twig template?

## Answer:

Provide a Twig function that wraps a call to \Drupal::service(''page_cache_kill_switch'')->trigger();

## How to use:

Call `{{ killswitchTrigger() }}` inside your twig template.

## GitHub link:
https://github.com/stefanospetrakis/twig_killswitch_trigger
