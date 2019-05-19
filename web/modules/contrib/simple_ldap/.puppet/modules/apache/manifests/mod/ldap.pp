class apache::mod::ldap (
    $location = '/ldap-status',
    $authz_require = undef,
) {

    exec { 'a2enmod ldap':
        creates => ['/etc/apache2/mods-enabled/ldap.load', '/etc/apache2/mods-enabled/ldap.conf'],
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

    file { '/etc/apache2/mods-available/ldap.conf':
        content => template('apache/etc/apache2/mods-available/ldap.conf.erb'),
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
