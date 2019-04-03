<?php

class Request {
  protected $method;
  protected $path;
  protected $query;
  protected $order;
  protected $orderby;
  protected $post;
  protected $put;
  protected $page;
  protected $type;
  protected $decoded = false;
  protected $cookie;
  function __construct($method, $path="/", $query="", $post,$type,$cookie){
    $this->method = $method;
    $this->path = empty($path)?"/":$path;
    $this->path = explode('/', $this->path);
    parse_str($query,$this->query);
    $this->order = (empty($this->query['sort'])||$this->query['sort']!='asc')?'desc':'asc';
    if(empty($this->query['orderby'])){
      $this->orderby="id";
    }
    $this->post = $post;
    $this->type= $type;
    $this->cookie= $cookie;
  }

  public function isApi(){
     return $this->path[1]=='api' && !empty($this->path[2]);
  }
  
  public function getPath(){
    return $this->path;
  }

  public function getQuery(){
    return $this->query;
  }
  public function getOrder(){
    return $this->order;
  }

  public function getOrderBy(){
    if(!isset($this->orderby)){
      if(preg_match('/^[a-zA-Z]+$/',$this->query['orderby'])){
        $this->orderby=$this->query['orderby'];
      }
    }
    return $this->orderby;
  
  }

  public function getPage(){
    $options = array(
      'options' => array(
        'default' => 1, 
        'min_range' => 0
      ),
   );
   $var = filter_var($this->query['page'], FILTER_VALIDATE_INT, $options);
   return $var;
  }

  public function getMethod(){
     return $this->method;
  }

  public function getPost(){
    if($this->type=='application/json'&& $this->decoded==false){
      $this->post=json_decode(file_get_contents('php://input'), true);
      if(empty($this->post))$this->post=[];
      $this->decoded=true;
    }else{
      parse_str(file_get_contents('php://input'),$this->post);
    }
    return $this->post; 
  }

  public function getPut(){
    if ('PUT' === $this->method) {
     if($this->decoded==false){
       if($this->type=='application/json'){
          $this->put=json_decode(file_get_contents('php://input'), true);
          if(empty($this->put))$this->put=[];
       }else{
          parse_str(file_get_contents('php://input'), $this->put);
       }
       $this->decoded = true;
     }
     return $this->put;
    } else return [];
  }

  public function getToken(){
    return orElse($this->cookie['token'],'');
  }
}

function orNull($v){
  if(!empty($v)) return $v;
  return null;
}

function orElse($v,$else){
  if(!empty($v)) return $v;
  return $else;
}

$request =  new Request(
	@orNull($_SERVER['REQUEST_METHOD']),
	@orNull($_SERVER['PATH_INFO']),
	@orNull($_SERVER['QUERY_STRING']),
	$_POST,
	orElse($_SERVER["CONTENT_TYPE"],@orNull($_SERVER["HTTP_CONTENT_TYPE"])),
	$_COOKIE
);

if($request->isApi()){
  try{	
   include('controller.php');
  } catch(Exception $e) {
    include('create.php');
 }
}else{
  http_response_code(404);
}
