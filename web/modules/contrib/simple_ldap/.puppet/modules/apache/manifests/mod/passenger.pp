class apache::mod::passenger {

    if !defined(Package['libapache2-mod-passenger']) { package { 'libapache2-mod-passenger': } }

    exec { 'a2enmod passenger':
        creates => ['/etc/apache2/mods-enabled/passenger.load', '/etc/apache2/mods-enabled/passenger.conf'],
        require => Package['apache2', 'libapache2-mod-passenger'],
        notify  => Service['apache2'],
    }

}
