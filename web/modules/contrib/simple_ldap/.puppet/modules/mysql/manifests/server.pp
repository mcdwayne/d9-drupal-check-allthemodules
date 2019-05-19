class mysql::server (
    $server_id                      = undef,
    $max_allowed_packet             = '64M',
    $skip_name_resolve              = true,
    $bind_address                   = '127.0.0.1',
    $max_connections                = 100,
    $table_open_cache               = 400,
    $query_cache_size               = '16M',
    $join_buffer_size               = 131072,
    $default_storage_engine         = 'innodb',
    $innodb_flush_method            = undef,
    $innodb_flush_log_at_trx_commit = 2,
    $innodb_log_buffer_size         = '8M',
    $innodb_log_file_size           = '256M',
    $innodb_file_per_table          = true,
    $innodb_buffer_pool_size        = undef,
    $root_password                  = mypwgen(32),
) {

    if !$::memorysize_mb {
        $memorysize_mb = $::memorysize
    }

    if !defined(Package['mysql-server']) { package { 'mysql-server': } }

    service { 'mysql':
        ensure  => running,
        enable  => true,
        require => Package['mysql-server'],
    }

    file { '/etc/mysql/my.cnf':
        content => template('mysql/etc/mysql/my.cnf.erb'),
        require => Package['mysql-server'],
        notify  => Exec['mysql::ib_logfile'],
    }

    exec { 'mysql::ib_logfile':
        command     => 'rm -f /var/lib/mysql/ib_logfile*',
        refreshonly => true,
        notify      => Service['mysql'],
    }

    ### Everything below this does the same thing as mysql_secure_installation

    exec { 'mysql::flush_privileges':
        command     => 'mysql -e "FLUSH PRIVILEGES;"',
        refreshonly => true,
    }

    # Set the root user's password
    exec { 'mysql::set_root_password':
        command => "mysql -e \"UPDATE mysql.user SET password=PASSWORD('${root_password}') WHERE user='root';\"",
        unless      => 'grep client /root/.my.cnf',
        require => Service['mysql'],
        notify  => Exec['mysql::flush_privileges', 'mysql::root::my.cnf'],
    }

    exec { 'mysql::root::my.cnf':
        command     => "echo \"[client]\nuser=root\npassword=${root_password}\" > /root/.my.cnf",
        require     => Exec['mysql::flush_privileges'],
        refreshonly => true,
    }

    # Remove anonymous users
    exec { 'mysql::remove_anonymous_users':
        command => 'mysql --defaults-file=/root/.my.cnf -e "DELETE FROM mysql.user WHERE user=\'\';"',
        unless  => 'mysql --defaults-file=/root/.my.cnf -BN -e "SELECT COUNT(user) FROM mysql.user WHERE user=\'\';" | grep "^0$"',
        require => [Service['mysql'], Exec['mysql::root::my.cnf']],
    }

    # Remove remote root access
    exec { 'mysql::remove_remote_root':
        command => 'mysql --defaults-file=/root/.my.cnf -e "DELETE FROM mysql.user WHERE user=\'root\' AND host NOT IN (\'localhost\', \'127.0.0.1\', \'::1\');"',
        unless  => 'mysql --defaults-file=/root/.my.cnf -BN -e "SELECT COUNT(user) FROM mysql.user WHERE user=\'root\' AND host NOT IN (\'localhost\', \'127.0.0.1\', \'::1\');" | grep "^0$"',
        require => [Service['mysql'], Exec['mysql::root::my.cnf']],
    }

    # Remove test database
    exec { 'mysql::remove_test_database':
        command => 'mysql --defaults-file=/root/.my.cnf -e "DROP DATABASE test; DELETE FROM mysql.db WHERE db=\'test\' OR db=\'test_%\';"',
        onlyif  => 'mysql --defaults-file=/root/.my.cnf -e "SHOW DATABASES LIKE \'test\';" | grep test',
        require => [Service['mysql'], Exec['mysql::root::my.cnf']],
    }
}
