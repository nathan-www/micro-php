<?php

/*******
Teeny PHP Micro-framework
Copyright (C) Nathan Arnold <nathanarnold.co.uk> 2020
*******/

class teeny_app{

    //Checks whether the request URL matches a pattern
    function url_match($pattern,$url){

        preg_match_all('/({)[^{}]+(})/', $pattern,$params);

        $regext = $pattern;
        foreach($params[0] as $p){
            $regext = str_replace($p,"*",$regext);
        }

        $regex = "^";
        foreach(str_split($regext) as $c){
            if($c == "*"){
                $regex .= "(.+)";
            }
            else{
                if($c == "/"){
                    $c = "\/";
                }
                $regex .= "(".$c.")";
            }
        }

        $regex .= "$";

        return preg_match('/'.$regex.'/', $url);
    }

    //Extracts the url parameters from request URL eg. /enterName/{name}
    function url_params($pattern,$url){

        $pattern = $this->rem_slash($pattern);
        $url = $this->rem_slash($url);

        preg_match_all('/({)[^{}]+(})/', $pattern,$params);

        $regext = $pattern;
        foreach($params[0] as $p){
            $regext = str_replace($p,"*",$regext);
        }

        $regex = "";
        foreach(str_split($regext) as $c){
            if($c == "*"){
                $regex .= "(.+)";
            }
            else{
                if($c == "/"){
                    $c = "\/";
                }
                $regex .= "(?>".$c.")";
            }
        }

        preg_match_all('/'.$regex.'/', $url,$matches);
        unset($matches[0]);

        $url_params = [];
        $i = 0;
        foreach($matches as $m){
            if(isset($params[0][$i])){
                $pname = str_replace("}","",str_replace("{","",$params[0][$i]));
                $url_params[str_replace("}","",str_replace("{","",$params[0][$i]))] = $m[0];
            }
            $i++;    
        }
        return $url_params;
    }

    //Stores all the routes
    public $routes = [];

    //Stores all the error routes
    public $error_routes = [];
    
    //Project directory
    public $project_dir = "";


    //Adds slashes before and after a string
    function add_slashes($str){
        if($str[0] !== "/"){
            $str = "/" . $str;
        }
        if($str[strlen($str)-1] !== "/"){
            $str = $str . "/";
        }
        return $str;
    }

    //Removed last slash from string
    function rem_slash($str){
        if($str[strlen($str)-1] == "/"){
            $str = substr($str, 0, -1);
        }   
        return $str;
    }

    //Creates a new GET route
    function get($url,$p1,$p2=null,$p3=null){
        $this->new_route("GET",$url,$p1,$p2,$p3);
    }

    //Creates a new (GET or POST) route
    function req($url,$p1,$p2=null,$p3=null){
        $this->new_route("any",$url,$p1,$p2,$p3);
    }

    //Creates a new POST route
    function post($url,$p1,$p2=null,$p3=null){
        $this->new_route("POST",$url,$p1,$p2,$p3);
    }

    //Creates a new route
    function new_route($method,$url,$p1,$p2,$p3){
        if($p2 !== null){
            $options = $p1;
            $func = $p2;
        }
        else{
            $options = false;
            $func = $p1;
        }
        if($p3 !== null){
            $error = $p3;
        }
        else{
            $error = null;
        }

        $this->routes[] = [
            "method"=>$method,
            "url"=>$this->rem_slash($this->project_dir).$this->add_slashes($url),
            "function"=>$func,
            "options"=>$options,
            "error"=>$error
        ];    
    }

    //Creates new error route
    function error($code,$func){
        $this->error_routes[$code] = [
            "function"=>$func   
        ];     
    }

    //Sets the project directory, if its not the root domain
    function set_root($dir){
        $this->project_dir = $dir;
    }

    //Creates the request object to serve to handling function
    function request_object($route,$url_params){

        if(!function_exists('get_items')){
            function get_items($source,$type,$route){
                $items = [];
                if(isset($route['options'][$type])){
                    $allowed = [];

                    foreach($route['options'][$type] as $k=>$v){
                        if(is_array($v)){
                            $allowed[] = $k;
                        }
                        else{
                            $allowed[] = $v;
                        }
                    }

                    foreach($source as $k=>$v){
                        if(in_array($k,$allowed)){
                            $items[$k] = $v;
                        }
                    }
                }
                return $items;
            }
        }

        $post = get_items($_POST,'post',$route);
        $get = get_items($_GET,'get',$route);
        $json = [];


        return [
            "url_params"=>$url_params,
            "get"=>$get,
            "post"=>$post,
            "json"=>json_decode(file_get_contents('php://input'))
        ];
    }

    //Runs the app, searches available routes for a match to the request
    function run(){
        $urlpath = strtok($_SERVER["REQUEST_URI"], '?');
        $served = false;
        foreach($this->routes as $r){
            if((($_SERVER['REQUEST_METHOD'] == $r['method']) || ($r['method'] == "any")) && $this->url_match($this->add_slashes($r['url']),$this->add_slashes($urlpath))){

                $served = true;

                $url_params = $this->url_params($this->add_slashes($r['url']),$this->add_slashes($urlpath));

                if($r['options'] !== false){
                    $validator = $this->validate_request($r,$this->add_slashes($urlpath));
                }
                if(($r['options'] == false) || $validator['valid']){

                    //Success, serve page
                    $r['function']($this->request_object($r,$url_params));
                }
                else{
                    //Error 400
                    if(!isset($r['error'])){
                        $this->throw_error(400,$validator['error']);
                    }
                    else{
                        header("HTTP/1.1 400");
                        $r['error']($validator['error']);
                    }
                }

                break;
            }
        }

        if(!$served){
            //Error 404
            $this->throw_error(404,"");    
        }
    }


    //Serve an error page
    function throw_error($http_code,$message=""){

        header("HTTP/1.1 " . $http_code);

        if(isset($this->error_routes[$http_code])){
            $this->error_routes[$http_code]['function']($message);    
        }
        else{

            $messages = [
                400=>"Bad Request",
                401=>"Unauthorized",
                403=>"Forbidden",
                404=>"Not found"
            ];

            if(isset($messages[$http_code])){
                echo "<h1>".$http_code." ".$messages[$http_code]."</h1>
                <hr><p><i>Teeny PHP</i></p>";
            }
            else{
                echo "<h1>Error ".$http_code."</h1>";
            }
            echo "<p>".$message."</p>";    
        }

    }

    //Validate that all the options for a route is satisfied
    function validate_request($route,$url){
        $options = $route['options'];
        $valid = true;
        $error = "";
        $validation = ["error"=>""];

        function validate_type($route,$type,$urlparams=null){

            $options = $route['options'];
            $valid = true;
            $error = "";

            if($type == "get"){
                $valarr = $_GET;
            }
            elseif($type == "post"){
                $valarr = $_POST;
            }
            elseif($type == "url"){
                $valarr = $urlparams;
            }
            elseif($type == "json"){
                $valarr = json_decode(file_get_contents('php://input'), true);
            }


            function re_add_slashes($str){
                if($str[0] !== "/"){
                    $str = "/" . $str;
                }
                if($str[strlen($str)-1] !== "/"){
                    $str = $str . "/";
                }
                return $str;
            }

            foreach($options[$type] as $key=>$value){

                if(is_array($value)){
                    if(!isset($valarr[$key]) && (!isset($value['required']) || $value['required'])){
                        $valid = false;
                    }
                    elseif(isset($value['validate'])){

                        if(($value['validate'] == "email") && (!filter_var($valarr[$key], FILTER_VALIDATE_EMAIL))){
                            $valid = false;
                        }
                        else if(($value['validate'] == "url") && (!filter_var($valarr[$key], FILTER_VALIDATE_URL))){
                            $valid = false;
                        }
                        else if(($value['validate'] == "float") && (!filter_var($valarr[$key], FILTER_VALIDATE_FLOAT))){
                            $valid = false;
                        }
                        else if(($value['validate'] == "int") && (!filter_var($valarr[$key], FILTER_VALIDATE_INT))){
                            $valid = false;
                        }
                        elseif(($value['validate'] == "name") && (!preg_match("/^[a-zA-Z ]*$/",$valarr[$key]))){
                            $valid = false;
                        }
                        elseif(($value['validate'] == "bool") && (!preg_match("/^(true|false|0|1|on|off)$/",$valarr[$key]))){
                            $valid = false;
                        }
                        elseif(($value['validate'] == "url") && (!preg_match("/^(true|false|0|1)$/",$valarr[$key]))){
                            $valid = false;
                        }
                        elseif(($value['validate'] == "regex") && (!preg_match(re_add_slashes($value['regex']),trim($valarr[$key])))){
                            $valid = false;
                        }
                    }
                    elseif(isset($value['min']) && (strlen($valarr[$key]) < $value['min'])){
                        $valid = false;
                    }
                    elseif(isset($value['max']) && (strlen($valarr[$key]) > $value['max'])){
                        $valid = false;
                    }
                    elseif(isset($value['min_range']) && ($valarr[$key] < $value['min_range'])){
                        $valid = false;
                    }
                    elseif(isset($value['max_range']) && ($valarr[$key] > $value['max_range'])){
                        $valid = false;
                    }
                }  
                elseif(!isset($valarr[$value])){
                    $valid = false;
                }

                if(!$valid){
                    if(isset($value['error'])){
                        $error = $value['error'];
                    }
                    else{
                        if(is_array($value)){
                            $p = $key;
                        }
                        else{
                            $p = $value;
                        }
                        $error = "The ".strtoupper($type)." parameter '" . $p . "' is missing or invalid";
                    }

                    break;
                }
            } 

            return [
                "valid"=>$valid,
                "error"=>$error
            ];
        }

        if(isset($options['get'])){
            $validation = validate_type($route,'get');
            $valid = $validation['valid'];
        }
        if(isset($options['post']) && $valid){
            $validation = validate_type($route,'post');
            $valid = $validation['valid'];
        }
        if(isset($options['url']) && $valid){
            $validation = validate_type($route,'url',$this->url_params($route['url'],$url));
            $valid = $validation['valid'];
        }
        if(isset($options['json']) && $valid){
            $validation = validate_type($route,'json');
            $valid = $validation['valid'];
        }
        if(isset($options['validators']) && $valid){
            foreach($options['validators'] as $v){
                if(isset($this->validators[$v])){
                    $resp = $this->validators[$v]($this->request_object($route,$this->url_params($route['url'],$url)));
                    if((is_bool($resp) && ($resp == true)) || (!is_bool($resp) && ($resp['valid']==true))){
                        //Validator successful
                        $valid = true;
                    }
                    elseif(!isset($resp['error'])){
                        //Validator unsuccessful
                        $valid = false;
                        $validation = ["error"=>"Validator '".$v."' not fulfilled"];
                    }
                    else{
                        //Validator unsuccessful
                        $valid = false;
                        $validation = ["error"=>$resp['error']];     
                    }
                }
                else{
                    $valid = false;
                    $validation = ["error"=>"Validator '".$v."' not fulfilled"];
                }
            }
        }

        return [
            "valid"=>$valid,
            "error"=>$validation['error']
        ];
    }

    //Generates a template and outputs final HTML
    public function generate_template($template_url,$values=[]){
        $template = file_get_contents($template_url);
        foreach($values as $k=>$v){
            $template = str_replace('${'.$k.'}',$v,$template);    
        }
        return $template;
    }

    //Custom validator functions
    public $validators = [];

    public function validator($name,$func){
        $this->validators[$name] = $func;    
    }
}


?>
