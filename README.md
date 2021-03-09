# teeny-php
A tiny, one-file PHP microframework.  
Teeny is a tiny framework made for simplicity and ease of use. It takes some basic principals from Slim and Laravel, but takes out the difficult installation and steep learning curve.<br><br>
**Core features:**
* Routing
* Validation
* Template engine
* Error handling

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
If you're running Apache, you can add this .htaccess file to the root of your app's directory:
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

In your main app file, you'll need to create a new instance of the teeny_app class.  
You can run your app with `$app->run();`, but you'll need to define all your routes first.  

eg.
```
<?php
 
require 'path/to/teeny.php';
$app = new teeny_app();

$app->get('/test',function(){
  echo "Hello world!";
});

$app->run();
```

This creates a new route for the URL */test*. If you visit domain.com/test, you should see the text "Hello world!" displayed.

**Please note:** If you are handling requuests not from the root directory for your domain name, you'll may wish to set a new root directory in your app.
Eg. If you are handling pages within a subdirectory domain.com/dir/, you can add `$app->set_root('/dir/');`.

Otherwise, when creating routes you will need to prepend '/dir/' before every path.

## Routes

Routes allow you to determine certain actions based on the URL requested by the user. <br>
A basic example will accept a file path (string) and a closure. The closure will be run if the incoming HTTP request URL matches the given file path.
```
$app->req('/file/path/',function(){
  //Do something.
});
```
You can create routes with:  
**$app->req($url,$options,$function,$error)** - Will catch all types of HTTP request (GET, POST, PUT etc.)
**$app->get($url,$options,$function,$error)** - Will only catch GET requests  
**$app->post($url,$options,$function,$error)** - Will only catch POST requests  

**$url** - String denoting the file path. 
* You can use an astericks (\*) to create wildcard rule. eg. `/wild/*` will match any path beginning with `/wild/`.
* You can also create URL parameters with two curly braces. eg. `/country/{{country_name}}` - which will catch `/country/Iceland` and parse `Iceland` as the parameter `country_name`

**$options** *(optional)*- An array containing additional options such as GET, POST and URL parameter validation and custom validators. See the validation section later on for full details.

**$function** - This is the closure which will handle all requests via the route. You can optionally add a single parameter to the closure to pass data into it. Eg.
```
$app->req($url,$options,function($data){

   $data = [
     "url_params"=>["key"=>"value"],
     "get"=>["key"=>"value"],
     "post"=>["key"=>"value"],
     "json"=>["key"=>"value"]
  ];
 
},$error);
```
$data is an array containing four items. Each of these items contains an associative array of key-value pairs.
* **url_params** - The URL paramters as specified in the $url
* **get** - The GET paramters
* **post** - The POST paramters
* **json** - If JSON is sent via a POST request it will appear, decoded, here

**$error** *(optional)* - Accepts a second closure which will called instead of the $function closure if an error occurs during validation. This is optional, and if not specified any error will be handled via the default error page or global error page set by you (See error handling later on). You can also pass a single parameter $message which will contain the error message.

## Validation

Within the **$options** parameter of every route, you can specify validations to be performed on GET, POST, JSON and URL parameters passed in that request. Eg.
```
$app->req($url,[
 "get"=>[
   "param1",
   "param2"=>[
     "validate"=>"email",
     "required"=>false
   ]
 ]
],$function,$error);
```






