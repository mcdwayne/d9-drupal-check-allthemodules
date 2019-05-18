## Introduction

TopoJSON is an extension of GeoJSON that encodes topology. Rather than representing geometries discretely, geometries in TopoJSON files are stitched together from shared line segments called arcs. Read more about the [TopoJSON format](https://github.com/topojson/topojson).

The leaflet library and Drupal module are not able to parse TopoJSON files so this helper module extends leaflet to handle TopoJSON.

## About this module

This module provides:

- TopoJSON extension to the leaflet Drupal library functions
- Includes the external topojson library from d3 to make leaflet topojson aware

## Installation

Installation can be done using any method but the recommended approach is to use composer.

## Dependencies

The only dependency is on the [leaflet module](https://drupal.org/project/leaflet).

## Usage

This module is currently only used by the [leaflet countries](https://www.drupal.org/project/leaflet_countries) module. There are no settings to configure.
