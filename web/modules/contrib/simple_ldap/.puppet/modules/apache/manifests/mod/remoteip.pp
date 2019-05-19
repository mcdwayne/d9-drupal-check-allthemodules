class apache::mod::remoteip {

    exec { 'a2enmod remoteip':
        creates => '/etc/apache2/mods-enabled/remoteip.load',
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
