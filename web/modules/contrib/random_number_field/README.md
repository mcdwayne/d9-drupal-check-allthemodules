# Random Number Field

This module adds a new field type of 'Random Number Field'.

## What is a random number field?

An integer field that when a new node/entity is created will be populated with a random integer between the min and max defined when the field type was created.

## Known issues

When adding or updating a random number field's settings always ensure the default value is empty, by default this will be populated with its own random number, which if saved as a default will prevent the random number functionality.