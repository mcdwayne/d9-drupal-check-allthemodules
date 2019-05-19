define apache::vhost (
    $template = undef,
    $priority = 200,
    $servername = $title,
    $serveralias = undef,
    $documentroot = undef,
    $errordocument = undef,
    $options = 'FollowSymLinks',
    $allowoverride = 'All',
    $directoryindex = 'index.html index.cgi index.pl index.php index.xhtml index.htm',
    $proxy = undef,
    $proxyaddheaders = 'On',
    $authz_require = undef,
    $http_port = 80,
    $https_port = 443,
    $normalize = true,
    $rewrite = undef,
    $modpagespeed = undef,
    $https = 'force',
    $htpasswd = false,
    $hsts = 15552000,
    $php_value = undef,
    $php_flag = undef,
    $allowencodedslashes = undef,
    $sslcertificatefile = undef,
    $sslcertificatekeyfile = undef,
    $sslcertificatechainfile = undef, 
    $sslusestapling = 'On',
) {

    $docroot = $documentroot ? {
        undef   => "/var/www/${servername}/docroot",
        default => $documentroot,
    }

    $site = $priority ? {
        undef   => "${servername}",
        default => "${priority}-${title}",
    }

    $template_file = $template ? {
        undef   => 'apache/etc/apache2/sites-available/default.conf.erb',
        default => $template,
    }

    file { "/etc/apache2/sites-available/${site}.conf":
        content => template($template_file),
        require => Package['apache2'],
        notify  => Service['apache2'],
    }

    file { "/etc/apache2/sites-enabled/${site}.conf":
        ensure  => link,
        require => Exec["apache::vhost::a2ensite::${title}"],
    }

    exec { "apache::vhost::a2ensite::${title}":
        command => "a2ensite ${site}",
        creates => "/etc/apache2/sites-enabled/${site}.conf",
        require => File["/etc/apache2/sites-available/${site}.conf"],
        notify  => Service['apache2'],
    }

    if $htpasswd {
        file { "/etc/apache2/htpasswd/${servername}":
            ensure  => present,
            require => File['/etc/apache2/htpasswd'],
        }
    }

}
