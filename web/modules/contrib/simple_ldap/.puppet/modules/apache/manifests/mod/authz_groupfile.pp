class apache::mod::authz_groupfile {

    exec { 'a2enmod authz_groupfile':
        creates => '/etc/apache2/mods-enabled/authz_groupfile.load',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
