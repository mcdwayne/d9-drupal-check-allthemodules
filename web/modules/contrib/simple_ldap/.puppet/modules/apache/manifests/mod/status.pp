class apache::mod::status (
    $location = '/server-status',
    $authz_require = undef,
) {

    exec { 'a2enmod status':
        creates => ['/etc/apache2/mods-enabled/status.load', '/etc/apache2/mods-enabled/status.conf'],
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

    file { '/etc/apache2/mods-available/status.conf':
        content => template('apache/etc/apache2/mods-available/status.conf.erb'),
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
