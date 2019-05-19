# XHProf Sample

Provides integration with the XHProf PHP extension and a simple interface for viewing/downloading XHProf samples and profiling information. These samples can be used for visual analysis with external software, or directly in Drupal with the [XHProf Flamegraph module](https://www.drupal.org/project/xhprof_flamegraph).

As the name implies, this module *only* provides sampling output rather than the full profiling integration of the [XHProf module](https://www.drupal.org/project/xhprof). This is preferable when lower overhead is required during performance monitoring and diagnostics. Sampling is suitable for use on production sites, and can be left enabled for analysis with minimal impact.

## Requirements

- [XHProf](https://pecl.php.net/package/xhprof) PHP extension
- [CTools](https://www.drupal.org/project/ctools)

## Configuration

The module's configuration can be found at `admin/config/development/xhprof-sample`.

By default the sampling output uses the filesystem for storage (using the `XHProfSampleRunsFile` class). Alternate storage mechanisms (Redis, MongoDB, etc) can be implemented by conforming to the `XHProfSampleRunInterface` which this module defines.

The default location for file-backed storage is the private filesystem in `private://xhprof_samples`. Ensure the private filesystem is configured, or specify a different output directory in the settings prior to enabling sampling.

### Sampling Options

There are several options available to control the collection of samples. These options can work together for granular control of when (and at which paths) sampling is enabled.

- Global switch: sampling can be toggled on/off with a global switch.
- HTTP Header: Sampling can be conditionally enabled based on the presence of a `X-XHProf-Sample-Enable` HTTP header. If this option is enabled, only requests that contain this header will be eligible for sample collection.
- Path-based sampling: Sampling can be enabled (or disabled) for specific paths. This can be useful when you want to analyze a specific page, or scope sampling to a section of the site based on a path prefix. This option supports wildcards.
