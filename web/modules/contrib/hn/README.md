# Headless Ninja API

This Drupal module provides an all-in-one endpoint, so you can easily build a completely headless website powered by Drupal.

> ⚠ This module is still under development. Output of the endpoint can change at any time.

## Roadmap

Before we can move to a stable release, there are a few things that need to be fixed.

- [X] Rename module names to use Headless Ninja (hn) in their names.
- [ ] Use more generic way to get entities
    - Maybe use Drupal\rest\Plugin\rest\resource\EntityResource kind-of-way, and overwrite Normalizers if necessary.
    - Otherwise, create our own Plugin type that replaces the entity hook.
- [ ] Return entities and references at root level, to make sure references aren't returned multiple times
