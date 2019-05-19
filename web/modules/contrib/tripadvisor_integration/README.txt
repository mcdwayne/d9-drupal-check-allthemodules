/**
 * @file
 * README file for TripAdvisor Integration.
 */

TripAdvisor Integration
Field type for retrieving data from the TripAdvisor Content API with the ID
provided.

----

1.  Introduction

TripAdvisor Integration currently provides a field type for storing and
displaying data retrieved from the TripAdvisor Content API.

----

2. Installation

Enable the module and the new TripAdvisor field type will become available.

----

3. Configuration

Add TripAdvisor API key to the admin configuration page
(/admin/config/content/tripadvisor-integration) and also optionally update cache
expiration setting.

When creating content, add the TripAdvisor ID in the TripAdvisor field in order
to retrieve the data for that ID from the TripAdvisor Content API. Override the
default template provided as with any other theme template, see template
documentation for more information.
