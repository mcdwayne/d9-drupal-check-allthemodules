class apache::mod::ssl (
    $sslprotocol = 'all',
    $sslhonorcipherorder = 'Off',
    $sslciphersuite = 'HIGH:MEDIUM:!aNULL:!MD5',
) {

    exec { 'a2enmod ssl':
        creates => ['/etc/apache2/mods-enabled/ssl.conf', '/etc/apache2/mods-enabled/ssl.load'],
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

    file { '/etc/apache2/mods-available/ssl.conf':
        content => template('apache/etc/apache2/mods-available/ssl.conf.erb'),
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

}
