#Development Drupal Site

This (composer based) Drupal site is purposed to ease
development of the nodeletter module.
It bootstraps a fully configured Drupal site to
work on the module.

## Initialize

```
#> cd link_browser_widget/.machinery
#> composer install
#> composer drupal-scaffold
#> ./vendor/bin/drupal site:install \
#    --site-name "Nodeletter" \
#    nodeletter_profile
```