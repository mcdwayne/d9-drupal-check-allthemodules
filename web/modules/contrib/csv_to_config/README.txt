About
=====

This module converts CSV to configuration.
It is very useful when you need to create a large number of configurations.

** Since, its the first version, it only supports one level structure key/value pairs. **

ps: The imported options will override the full configuration. There is no
support for merging with existing configurations.

e.g.
"foo","bar"
"foo2","bar2"

We have plans to support more complex structures, i.e. nested configurations.

Usage
=====
Go to /admin/config/development/csv_to_config/import/upload and upload a CSV file.
Its advisable to export the CSV using quotes around values and comma as separator.
The first row must be the header.

The format of the CSV rows is Key/Value e.g.
"foo","bar"
"foo2","bar2"

This will generate a config with this structure:
foo: bar
foo2: bar2

On the next page, you will see a table with the contents of the CSV and a text
box to provide the configuration name.
You can use replacement tokens to get the values from the table.
If you open sample.csv, there is a column that contains a key called site_machine_name
If you fill in Configuration name with: domain.config.[site_machine_name].config_token.tokens
The table will be processed and each column will be a configuration.

For multiple configurations, you can repeat the value many times e.g.
"Config",    "Config 1",  "Config 2"
"conf_name", "conf1",     "conf2"
"key1",      "val1,       "val3"
"key2",      "val2",      "val4"

This will generate two config entries
If you export them using drush cex, you will have these two files

file: conf1.yml
key1: val1
key2: val2

file: conf2.yml
key1: val3
key2: val4
