# teeny-php
A tiny, one-file PHP microframework.  
Teeny is a tiny framework made for simplicity and ease of use. It takes some basic principals from Slim and Laravel, but takes out the difficult installation and steep learning curve.<br><br>
**Core features:**
* Routing
* Validation
* Template engine
* Error handling

<br>
**Important note:** This project is in development, there may be weird bugs and hiccups. Use at your own discretion.

## Installation

First, create a new PHP file. This will be the main app file where all HTTP requests are routed to.
Then upload the teeny.php file anywhere on the server and include it in your main file.

**index.php**
```
<?php
 
//My new Teeny app
require 'path/to/teeny.php';
```

### Web server setup
You'll need to configure your web server to re-route all incoming requests to your main app file (eg. index.php).  
If your running Apache, you can add this .htaccess file to the root of your app's directory:
```
Options +FollowSymLinks -Indexes
RewriteEngine On

RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```
If running Nginx, you can add this directive to your site configuration:
```
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

...And that's it! Teeny is ready to go.
<br>
## Getting started


