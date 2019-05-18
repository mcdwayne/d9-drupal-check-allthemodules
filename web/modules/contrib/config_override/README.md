# Config override

The idea of the module is to make it easier to override configuration.

## Override mechanisms

Currently there are two override mechanisms

* A site wide override folder, you need to specify 'override' in your
  settings.php
* A module provided override mechanism. A module can override config by
  providing some yml files.
* A way to override configuration using environment variables:
   In order to use it you need the following steps:
   - composer require symfony/dotenv
   - Provide a .env(ironment) or sites/default/.env)ironment\_ file with entries
    which look like:
    ```
    CONFIG___NAME__OF_CONFIG___KEY=overridden_VALUE
    CONFIG___NAME2___KE2Y=overridden_VALUE2
    ```
   - Due to limitations in environment variables overriding something with dots/underscores
     requires the following rules:
     1. the config name and config key are separated with three underscores
     2. If you want to replace a dot, use two underscores.
     3. If your config name or key has an underscore, use an underscore.

   - If needed you can specify dynamic environment variables, for example
     provided by the hoster or something dynamic in settings.php.
   - Keep in mind that you need the entry in the .env file so the pickup of the
     env vars is fast.
   - You can specify a JSON encoded object as value, in which case you can override an entire subtree
     of configuration.
