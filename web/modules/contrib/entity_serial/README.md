# Entity Serial

Per bundle computed serial number.

The difference with the
[Serial](https://www.drupal.org/project/serial) module 
is that the field value is not stored but computed.

The serial id is preserved if entities from the bundle
are being deleted.

## Use case

When a sequence id within a bundle is needed (e.g. an invoice number, ...).

## Configuration

Add the field to an entity type bundle (e.g. _Article_ content type).
Set the id to start from and the node id that will be used 
as the first entity to count from. 

## Roadmap

- Optionally initialize values for existing entities.
