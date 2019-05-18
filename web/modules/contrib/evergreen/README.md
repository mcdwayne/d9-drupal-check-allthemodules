# Evergreen module

Provides a means for tracking old content on a site. Entities can be configured
to use this module and content can then be marked as "Evergreen" (won't expire)
or "Perishable" (can expire). The configuration allows for different entities to
have different expiration times and can be overridden on a per-content basis.

## Wish list

- Views filter for whether or not the content is expired
- Views filter for whether the content is "Evergreen" or not
- Views relationship to the evergreen_config entity?
