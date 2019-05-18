# Devel Mode

Helper module for debugging purposes

- Set null cache backends for menu, render and dynamic_page_cache
- Disable js and css aggregation
- Enable twig debug mode
- Enable twig autoload
- Disable twig cache
- Enable drupal cache headers for debugging

## defaults

```
config.devel_mode:
  disable_preprocess_js: TRUE
  disable_preprocess_css: TRUE
  modules:
    - devel
    - webprofiler
  cache.bin:
    - render
  twig:
    debug: TRUE
    auto_reload: TRUE
    cache: FALSE
```


## Enable in services.yml

```
parameters:
  config.devel_mode:
    disable_preprocess_js: TRUE
    disable_preprocess_css: TRUE
    modules:
      - devel
      - webprofiler
  .....
```
