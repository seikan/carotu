# Carotu

Carotu is a lightweight inventory system to keep track of your VPS and dedicated servers. The system is written in PHP language with Bootstrap 5 as the front-end framework.

I wrote this in my leisure time as a hobby. Please do not expect frequent updates. Any feature requests or bug reports can be submitted to [here](https://github.com/seikan/carotu/issues).



### Requirements

* PHP 8.0 and above
* SQLite PDO extension enabled
* Apache or Nginx web server



### Apache vhost Example

```
<VirtualHost *:80>
	ServerName		carotu.example.com
	DocumentRoot	/var/www/carotu.example.com

	<Directory /var/www/carotu.example.com>
		AllowOverride	All
		Options			Indexes FollowSymLinks
		Order			allow,deny
		Allow			From all
	</Directory>
</VirtualHost>

<VirtualHost *:443>
	ServerName		carotu.example.com
	DocumentRoot	/var/www/carotu.example.com

	SSLEngine				On
	SSLCertificateFile		/etc/ssl/carotu.example.com.pem
	SSLCertificateKeyFile	/etc/ssl/carotu.example.com-key.pem

	<Directory /var/www/carotu.example.com>
		AllowOverride	All
		Options			Indexes FollowSymLinks
		Order			allow,deny
		Allow			From all
	</Directory>
</VirtualHost>
```

#### .htaccess

```
Options -Indexes
RewriteEngine on

<FilesMatch "\.(log|htaccess|sqlite)$">
  Require all denied
</FilesMatch>

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !=/favicon.ico
RewriteRule ^(.*)$ index.php [L,QSA]
```





### Nginx vhost Example

```
server {
	listen 80;
	server_name carotu.example.com;
	return 301 https://carotu.example.com$request_uri;
}

server {
	listen 443 ssl;
	server_name carotu.example.com;
	root /var/www/carotu.example.com;
	
	access_log /var/log/nginx/carotu.example.com-access.log;
	error_log /var/log/nginx/carotu.example.com-error.log;

	index index.php index.html;

	ssl_certificate /etc/ssl/carotu.example.com.pem;
	ssl_certificate_key /etc/ssl/carotu.example.com-key.pem;

	ssl_ciphers 'AES128+EECDH:AES128+EDH:!aNULL';

	ssl_protocols TLSv1.2;
	ssl_session_cache shared:SSL:10m;

	location ~ \.php$ {
		try_files	$uri =404;
		fastcgi_pass	127.0.0.1:9000;
		fastcgi_index	index.php;
		fastcgi_param	SCRIPT_FILENAME	$document_root$fastcgi_script_name;
		include		fastcgi_params;
	}
	
	# Protect log and database files
	location ~* \.(log|sqlite)$ {
		deny all;
	}

	location / {
		try_files $uri $uri/ @rewrite;
	}
	
	# Rewrite rules
	location @rewrite {
		rewrite ^/(.*)$ /index.php last;
	}
	
	# Error pages
	error_page 403 /403.html;
	error_page 404 /404.html;
	error_page 500 502 503 504 /50x.html;

	location = /403.html {
		root /var/www/carotu.example.com;
		internal;
	}

	location = /404.html {
		root /var/www/carotu.example.com;
		internal;
	}

	location = /50x.html {
		root /var/www/carotu.example.com;
		internal;
	}
}
```





### Installation

1. Download the latest version from here.
2. Decompress the package and upload it to web server.
3. Access the system from web browser to continue the setup.



### Screenshots

![](https://github.com/seikan/carotu/assets/73107/cacc491c-70c0-4161-bbb7-d23ab119ad5d)



![](https://github.com/seikan/carotu/assets/73107/b9c5ea2f-295a-4016-a156-54346e9c1723)



![](https://github.com/seikan/carotu/assets/73107/087b344e-20b7-4f37-bfb9-f72fd2f541e7)