class mysql::client {
    if !defined(Package['mysql-client']) { package { 'mysql-client': } }
}
