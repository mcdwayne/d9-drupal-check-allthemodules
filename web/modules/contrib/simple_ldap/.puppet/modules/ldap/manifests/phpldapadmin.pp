class ldap::phpldapadmin (
    $installdir = '/usr/local/share/phpldapadmin',
    $custom_templates_only = false,
    $hide_template_warning = false,
    $friendly_attrs = {
        'facsimileTelephoneNumber' => 'Fax',
        'gid'                      => 'Group',
        'mail'                     => 'Email',
        'telephoneNumber'          => 'Telephone',
        'uid'                      => 'User Name',
        'userPassword'             => 'Password',
    },
    $server_name = 'My LDAP Server',
    $server_host = '127.0.0.1',
    $server_port = 389,
    $server_tls = false,
    $login_bind_id = undef,
    $login_bind_pass = undef,
    $password_hash = undef,
    $login_attr = undef,
    $login_base = [],
    $login_fallback_dn = false,
    $login_anon_bind = true,
    $custom_pages_prefix = undef,
    $unique_attrs = undef,
) {

    if !defined(Package['git']) { package { 'git': } }

    exec { 'ldap::phpldapadmin::install':
        command => "git clone https://github.com/leenooks/phpLDAPadmin.git ${installdir}",
        creates => "${installdir}",
        require => Package['git'],
    }

    file { "${installdir}/config/config.php":
        content => template('ldap/usr/local/share/phpldapadmin/config/config.php.erb'),
        require => Exec['ldap::phpldapadmin::install'],
    }
}
