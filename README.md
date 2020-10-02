# teeny-php
A tiny, one-file PHP microframework.
Teeny is a tiny framework made for simplicity and ease of use. It takes some basic principals from Slim and Laravel, but takes out the difficult installation and steep learning curve. It's simple enough that you don't need to spend days learning, but still offers features to significantly improve your PHP development.

## Installation

First, create a new PHP file. This will be the main app file where all HTTP requests are routed to.
Then upload the teeny.php file anywhere on the server and include it in your main file.

**index.php**
```
<?php
 
//My new Teeny app
include 'path/to/teeny.php';
```

### Web server setup
You'll need to configure your web server to re-route all incoming requests to your main app file (eg. index.php)
