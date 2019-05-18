# Compressed Database Cache Backend

This package provides a cache backend which leverages gzip compression for the
stored data.

Upon cache set the cache data will be gzipped, if the gzipped data is smaller
than the original data the compressed data will be stored in the cache bin.

This Database Backend adds two additional states to serialized column of Drupal
cores DatabaseBackend.

- SERIALIZED_COMPRESSED (2): a serialized object, compressed.
- STRING_SERIALIZED_COMPRESSED (3): a compressed string.

# Requirements

php functions: gzcompress, gzuncompress come with zlib extension.

# Installation

in settings.php:

    // Default cache bin
    $settings['cache']['default'] = 'cache.backend.database_compressed_cache';
    // Specific cache bins
    $settings['cache']['bins']['data'] = 'cache.backend.database_compressed_cache';
    $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.database_compressed_cache';
    $settings['cache']['bins']['render'] = 'cache.backend.database_compressed_cache';
    $settings['cache']['bins']['page'] = 'cache.backend.database_compressed_cache';

# Advanced settings

    /**
     * Compression level
     * default = 6
     * @see http://php.net/gzcompress
     */
    $settings['compressed_cache']['cache_compression_ratio'] = 1;

    /**
     * Minimum string length to add compression.
     * Seems to be completely based on gut feeling. can not find any sources googling this topic.
     */
    $settings['compressed_cache']['cache_compression_size_threshold'] = 100;
    
    /**
     * Whether garbage collection is enabled or not. Defaults to TRUE.
     */
    $settings['compressed_cache']['garbage_collection_enabled'] = TRUE;
