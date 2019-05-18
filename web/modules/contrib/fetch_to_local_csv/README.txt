Fetch To Local CSV

When using Migrate Source CSV as a source, if your csv file is remote, you can't specify a header row because it uses PHP's rewind(), which will only work on a local file.

This wraps the csv plugin, allowing you to specify a remote source as well as a local source. The remote will be fetched and saved as the local, overwriting it if it exists.

Usage:

source:
  plugin: fetchtolocalcsv
  remote: https://example.com/file.csv
  path: public://file.csv