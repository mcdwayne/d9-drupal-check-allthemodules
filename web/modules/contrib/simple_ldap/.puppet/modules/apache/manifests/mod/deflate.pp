class apache::mod::deflate {

    exec { 'a2enmod deflate':
        creates => ['/etc/apache2/mods-enabled/deflate.load', '/etc/apache2/mods-enabled/deflate.conf'],
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

    file { '/etc/apache2/mods-available/deflate.conf':
        content => template('apache/etc/apache2/mods-available/deflate.conf.erb'),
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
