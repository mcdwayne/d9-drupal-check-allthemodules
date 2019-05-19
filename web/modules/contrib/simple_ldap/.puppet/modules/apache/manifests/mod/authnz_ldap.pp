class apache::mod::authnz_ldap {

    exec { 'a2enmod authnz_ldap':
        creates => '/etc/apache2/mods-enabled/authnz_ldap.load',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
