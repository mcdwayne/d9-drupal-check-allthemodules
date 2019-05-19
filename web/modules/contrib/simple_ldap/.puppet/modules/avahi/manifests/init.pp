class avahi {

    if !defined(Package['avahi-daemon']) { package { 'avahi-daemon': } }

    service { 'avahi-daemon':
        ensure  => running,
        enable  => true,
        require => Package['avahi-daemon'],
    }

}
