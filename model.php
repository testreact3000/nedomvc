<?php
class Model{
   const USERNAME="root";
   const PASSWORD="";
   const HOST="localhost";
   const DB="pdb";
   const SECRET="jahsdkjahdkajshk";
   protected $data = [];	
   function __construct(){
   
   }	

   protected function getConnection(){

            $username = self::USERNAME;
            $password = self::PASSWORD;
            $host = self::HOST;
	    $db = self::DB;
            $connection = new PDO("mysql:dbname=$db;host=$host", $username, $password);
            return $connection;
   }
/**
 * @todo write success check
 */
  function getNotes($orderby, $order,$page){
	   $connection = $this->getConnection();
	   if(empty($orderby))$orderby='id';
	   $offset= ($page-1) *3;
           $query = $connection->prepare('SELECT id, name, email, status, body as text from notes order by '.$orderby.' '.$order.' limit 4 offset :offset');
	   $count = $connection->query("SELECT count(*) as cnt from notes");
	   $query->bindValue(':offset', $offset, PDO::PARAM_INT);
	   $query->execute();
	   //var_dump($query->debugDumpParams());
           $count->execute();
	   $res=$query->fetchAll(PDO::FETCH_ASSOC);
	   $rescnt=$count->fetchAll()[0]['cnt'];
	   if($rescnt<$page)$page=$rescnt;
           $result=['data'=>$res,'page' => $page, 'pages' => ceil($rescnt/3),'orderby'=>$orderby,'order'=>$order];
           return $result;
   }
   /**
    * dumb function for note validation
    */
   function checkValidNote($note,&$messages){
     $messages=[];
   }
   function createNote($note){
	   $email=""; if(!empty($note['email']))$email=strip_tags($note['email']);
	   $text=""; if(!empty($note['text']))$text=strip_tags($note['text']);
	   $name=""; if(!empty($note['name']))$name=strip_tags($note['name']);

	   $messages=[];
           $this->checkValidNote([
	     'email'=>$email,
	     'text'=>$text,
	     'name'=>$name,
           ],$messages);

           if(empty($messages)){

	     $connection=$this->getConnection();
	     $stmt=$connection->prepare('INSERT INTO notes (email, body, name,status) values (:email,:text,:name,"new")');
	     $stmt->execute([
	       'email'=>$email,
	       'text'=>$text,
	       'name'=>$name,
             ]);
	     $id=$connection->lastInsertId();
	     return ['id'=>$id];
	   }else{
	     return ['errors'=>$messages];
	   }
   }
   function checkValidEdit($note, &$messages){
        $messages=[];
   }
   function checkRoleAndTime($token){
      if(!empty($token)){
	      if((int)$token['exp']>time() && $token['role']==='admin'){
	         return true;
	      }
      }
      return false;
   }
   function editNote($id,$note,$token){
	 if(!$this->checkRoleAndTime($token)){
	   http_response_code(403);
	 }else{

           $text="";if(!empty($note['text']))$text=$note['text'];//allow scrips for admin
	   $status=""; if(!empty($note['status']))$status=$note['status'];
	   $messages=[];
	   $this->checkValidEdit(['text'=>$text,'status'=>$status],$messages);
           if(empty($messages)){
             $connection=$this->getConnection();
	     $stmt=$connection->prepare('UPDATE notes set body=:text, status=:status where id=:id ');
	     $stmt->bindValue(':text',$text);
	     $stmt->bindValue(':status',$status);
	     $stmt->bindvalue(':id',$id, PDO::PARAM_INT);
	     $stmt->execute();
	     var_dump($stmt->debugDumpParams());
	     $min=60;
             $token = $this->createJwtToken([
               'role'=>'admin',
               'exp'=> time() + 5*$min
             ]);
             return ['token'=>$token];
	   }else{
	     return ['errors'=>$messages];
	   }
	 }
   }
   function login($post){
      $min=60;	   
      $login=""; if(!empty($post['login']))$login=$post['login'];
      $password=""; if(!empty($post['password']))$password=$post['password'];
      if($login==="admin" && $password==="123"){
	       $token = $this->createJwtToken([
		      'role'=>'admin',
		      'exp'=> time() + 5*$min
	      ]);
	      return $token; 
      }
      return '';
   }
   function createJwtToken($data){
      // Create token header as a JSON string
      $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
      // Create token payload as a JSON string
      $payload = json_encode($data);
      // Encode Header to Base64Url String
      $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
      // Encode Payload to Base64Url String
      $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
      // Create Signature Hash
      $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::SECRET, true);
      // Encode Signature to Base64Url String
      $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
      // Create JWT
      $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
      return $jwt;
   }
   function parseJwtToken($token){
      $tokenParts = explode(".", $token); 
      $tokenHeader = base64_decode(str_replace(['-','_'],['+','/'],$tokenParts[0]));
      $tokenPayload = base64_decode(str_replace(['-','_'],['+','/'],$tokenParts[1]));
      $tokenSignature = base64_decode(str_replace(['-','_'],['+','/'],$tokenParts[2]));
      $signature = str_replace('=','',hash_hmac('sha256', $tokenParts[0] . "." . $tokenParts[1], self::SECRET, true));
      $res=false;
      if($signature===$tokenSignature){
	if(!(empty($tokenSignature)||empty($tokenPayload)||empty($tokenHeader))){
          $jwtHeader = json_decode($tokenHeader,true);
	  $jwtPayload = json_decode($tokenPayload,true);
	  if(
	    !empty($jwtHeader)&& 
	    !empty($jwtPayload) && 
	    count($jwtHeader)==2 && 
	    $jwtHeader['typ']=='JWT' && 
	    $jwtHeader['alg']=='HS256'
	  ){
	    return $jwtPayload;
	  }
        }
      }
      return [];

print $jwtPayload->username;
   }
   function createDatabase(){
         $username = self::USERNAME;
         $password = self::PASSWORD;
         $host = self::HOST;
         $db = self::DB;
         $connection = new PDO("mysql:host=$host",$username,$password);
	 $connection->exec("
                CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                CREATE USER '$username'@'localhost' IDENTIFIED BY '$pass';
                GRANT ALL ON `$db`.* TO '$username'@'localhost';
                FLUSH PRIVILEGES;
        ");
   }
   function createTable(){
       $connection = $this->getConnection();
       $connection->exec("
          CREATE TABLE IF NOT EXISTS 
	    notes (
     	      id INT AUTO_INCREMENT NOT NULL DEFAULT NULL,
	      email VARCHAR(320),
	      status VARCHAR(50),
              body  TEXT,
              name VARCHAR(255),

              PRIMARY KEY id_idx(id),
	      INDEX email_idx (email),
              INDEX status_idx (status)
	    )

       ");
   }

}
