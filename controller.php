<?php
include_once('model.php');
 
  $model= new Model();
  switch($request->getMethod().'_'.$request->getPath()[2]){
    case 'GET_notes':
	    $res=$model->getNotes(
		    $request->getOrderby(), 
		    $request->getOrder(),
		    $request->getPage()
	    );
	    include('get_notes.php');
	    template($res);
	    break;
    case 'POST_notes':
      	    $res = $model->createNote(
              $request->getPost()    
            );
	    include('add_notes.php');
	    template($res);
	    break;
    case 'PUT_notes':
	    $token=$model->parseJwtToken($request->getToken());
	    if($model->checkRoleAndTime($token)){
	     $res=$model->editNote(
	       (int)$request->getPath()[3], 
	       $request->getPut(),
               $token
              );
	     include('edit_notes.php');
	     template($res);

	    }else{
	      http_response_code(403);
	    }
	    break;
    case 'POST_login':
            $token=$model->login(
	        $request->getPost()
	);
	    if(empty($token)){
	      http_response_code('403');
	    }else{
	      setcookie('token',$token,time()+300,'/index.php/api/');
	    }
	break;   
   default:
	    http_response_code(404);
    	    
  }
