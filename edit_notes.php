<?php
function template($res){
	if(!empty($res['token'])){
	  $token=$res['token'];
          unset($res['token']);
	  setcookie('token',$token,time()+300,'/index.php/api/');
	}
	
	echo json_encode($res);
};		

