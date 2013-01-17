<?php

class jsonRPCServer {
    /*
     * @param $object Object
     * @return Boolean
     */
     public static function handle($object) {

         /* checks whether we have an AJAX request JSON-RPC client */
         if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
         
             if($_SERVER['CONTENT_TYPE'] != 'application/x-www-form-urlencoded; charset=UTF-8' &&
                                 !preg_match('/application\/json.*/', $_SERVER['HTTP_ACCEPT']) &&
                                 $_SERVER['HTTP_X_REQUEST'] != 'JSON') {
                                     //sort($_SERVER);
                                     //print_r($_SERVER);
                                     return false;
             }

         /* otherwise, the request is made through PHP client */
         } else { 

           if($_SERVER['REQUEST_METHOD'] != 'POST' || 
              !preg_match('/^application\/json.*/',$_SERVER['CONTENT_TYPE'])  || 
                                        empty($_SERVER['CONTENT_TYPE'])) {
                return false;
           }
         }
         
         /* 
          * Reads the input data 
          * decodes a JSON string - takes a JSON encoded string and converts in into a PHP variable.
          * Syntax: mixed decode_json(string $json [, bool $assoc = false [, int $depth = 512 [, int $options = 0]]] );
          * 
          * @param $json - the json string being decoded.
          * @param $assoc - when TRUE, returned objects will be converted into associative arrays.
          * @param $depth - user specified recursion depth.
          * @param $options - bitmask of JSON decode options. currently only JSON_BIGINT_AS_STRING is supported.     
          */
         $request = json_decode(file_get_contents("php://input"), true); 
         $rpcversion = $request['jsonrpc']=='2.0'?2:1;
         /* executes the task in local object */
         try {
             /* 
              * Call a user function given with an array of parameters.
              *
              * Syntax: mixed call_user_func_array(callback function, array $param_arr).
              *
              * @param function - function to be called.
              * @param param_arr - the parameters to be passed to the function, as an indexed array. 
              * @return returns the function result or FALSE on error.
              */
              $result = @call_user_func_array(array($object, $request['method']), (array) $request['params']);
             if(!is_null($result)) {

               $response = array(
                                'id' => $request['id'],
                                'result' => $result,
                                'error' => NULL
                                );           
             } else {
               $error = 'Unknown method or parameters';
               if($rpcversion == 2) {
                   $error = array('code'=> 0 , 'message' => $error );
               }
               $response = array(
                              'id' => $request['id'],
                              'result' => NULL,
                              'error' => $error
                             ); 
             } 
                      
         }catch(Exception $e) {
                $error= $e->getMessage();
               if($rpcversion == 2) {
                   $error = array('code'=> $e->getCode() , 'message' => $error );
               }         
               $response = array(
                              'id' => $request['id'],
                              'result' => NULL,
                              'error' => $error
                             );
         }  
          
         //output the response
         if(!empty($request['id'])) {
            if($rpcversion == 2) {
                $response['jsonrpc'] = "2.0";
            }
             header('content-type: text/javascript');
             /*
              * Returns the JSON represenation of a value.
              * Syntax: string json_encode(mixed $value [,int $options = 0 ]);
              *
              * @param $value - the value being encoded. can be any type except a resource. 
              *                 the function only works with UTF-8 encoded data.
              * @param $options - Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS       
              * @return - returns a JSON encoded string on success.
              */
              echo json_encode($response);
         }
         if($_SERVER['HTTP_USER_AGENT'] == 'PHP') {
             @session_destroy();
         }

       /* finish */
       return true;
     }
}
?>
