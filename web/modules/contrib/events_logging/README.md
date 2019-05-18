This module implements an out-of-the-box events logging system with standard content entities and config entities.
It also provides a standard drupal view of events, so you can track performed operations and create your own custom displays of tracked events.
It's also possible to log your own custom events using the logger standard service provided by this module.
Storage backends for logs are defined as plugins so you can easily extend this module to create your own logging plugin.

PROVIDED PLUGINS:
- Database Plugin (enabled by default)

FUTURE PLUGINS :
- Cassandra Plugin
- Monolog Plugin