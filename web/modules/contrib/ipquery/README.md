Provides a service that uses a simple database query to geo locate an IP address. It is highly performant in that it can be used to query the IP address on every single page load. It uses a single indexed query to do this.

This module only provides a service, it does nothing else. To use, you can add kernel onRequest event subscriber and then query the service to get the current country/timezone and do whatever you want with it.

This module currently downloads the CSV file into the database from the ip2location.com commercial service. It could be improved to support all the ip2location products or any other service. The download is done on cron. The initial file is downloaded and imported on the first cron. Updated files are downloaded monthly per ip2location.com's publication schedule. It can also be done by drush.

## Related Drupal 8 modules
* [smart_ip](http://www.drupal.org/project/smart_ip) supports multiple services. However it treats each service as a black box rather than a data source, and thus the lookup must read from a large data file. It's the large data files that ultimate lead to the development of this module, Pantheon wouldn't support them. The performance issues of large data files are mitigated by ip2location by the use of shared memory.
* [ip2country](http://www.drupal.org/product/ip2country) does not handle timezones.
* [hostip](http://www.drupal.org/project/hostip) uses a services, not suitable for looking up every user page or even every session.
