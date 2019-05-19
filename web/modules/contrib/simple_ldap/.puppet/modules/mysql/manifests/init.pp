class mysql {
    class { 'mysql::client': }
    class { 'mysql::server': }
}
