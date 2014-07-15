<?php
class jsonRPCClient {
    /* 
     * Debug state
     * @var boolean
     */
    public $debug;
    /* 
     * Server URL
     * @var String
     */
    private $uri;
    /* 
     * The request ID
     * @var String
     */
    private $id;
    /* 
     * If true, notifications are performed instead of requests
     * @var Boolean
     */
    private $notification = false;
    /*
     * If true, the USERAGENT is not set and jsonRPCServer doesn't destroy the session
     * only of use, when the backend is jsonRPCServer
     * @var Boolean
     */
    private $keepsession = false;
    
    /*
     * Curl-instance
     */ 
    private $ch = null;
    
    private $cookie_file_generated = true;
    private $cookie_file = null;
    private $stream_mode = false;
    private $stream_host = false;
    private $stream_port = false;
    private $stream_socket = 0;
    /*
     *  Constructor of class
     *  Takes the connection parameters
     *
     *  @param String $url
     *  @param Array $options  array('debug'=>false|true, 'keepsession'=> false|true, 'cookie_file' => ...)
     */
    public function __construct($uri, $options = array()) {
        $this->uri = $uri;
        $this->keepsession = $options['keepsession'];
        empty($proxy) ? $this->proxy = '' : $this->proxy = $proxy;
        empty($options['debug']) ? $this->debug = false : $this->debug = true;
        $this->debugclone = $debug;
        
        $userAgent = 'PHP';
        if($this->keepsession) {
            $userAgent = 'PHPWithSession';
        }
        if(empty($options['cookie_file'])) {
            $this->cookie_file = tmpfile();
        }
        else {
            $this->cookie_file = $options['cookie_file'];
            $this->cookie_file_generated = false;
        }
        if(!empty($options['stream_host'])) {
            $this->stream_mode = true;
            $this->stream_host = $options['stream_host'];
            $this->stream_port = $options['stream_port'];
        }
        if($this->stream_mode) {
            $this->stream_socket = socket_create(AF_INET, SOCK_STREAM, 0);
            socket_connect($this->stream_socket, $this->stream_host, $this->stream_port);
        }
        else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_COOKIESESSION, false);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
            curl_setopt($ch, CURLOPT_URL, $this->uri); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json-rpc'));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            $this->ch = $ch;
        }
    }

    public function __destruct() {
    }

    /*
     * Performs a jsonRPCRequest and gets the results as an array;
     * 
     * @param $method (String) 
     * @param $params (Array)
     * @return Array
     */
    public function __call($method, $params) {

         /* finds whether $method is a scalar or not */
         if(!is_scalar($method)) {
            throw new Exception("Method name has no scalar value.");   
         }

         /* checks if the $params is vector or not */
         if(is_array($params)) {
            $params = array_values($params);
         } else {
            throw new Exception("Params must be given as array.");
         }

         $this->id = rand(0,99999); 

         if($this->notification) {
           $currentId = NULL; 
         } else {
           $currentId = $this->id;
         }

         /* prepares the request */
         $request = array(
                          'method' => $method,
                          'params' => $params,
                          'id' => $currentId,
                          'jsonrpc'=> '2.0'
                         );

         $request = json_encode($request);

         $this->debug && $this->debug .= "\n".'**** Client Request ******'."\n".$request."\n".'**** End of Client Request *****'."\n";

         if($this->stream_mode) {
             $contentTypeServer = 'text/javascript';
             $len = socket_write($this->stream_socket,$request);
             if($len === false) {
                 throw new Exception('Socket-Error:' . socket_strerror(socket_last_error()));
             }
             $response = socket_read($this->stream_socket,10 * 1024 * 1024);
         }
         else {
             /* Performs the HTTP POST */
             curl_setopt($this->ch, CURLOPT_POSTFIELDS, $request);
             $response = curl_exec($this->ch); 
             $contentTypeServer = curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
         }
         if($contentTypeServer=='text/javascript' || $contentTypeServer=='application/json-rpc' ) {
            $this->debug && $this->debug .= '**** Server response ****'."\n".$response."\n".'**** End of server response *****'."\n\n";

            $response = json_decode($response, true);    
             
         } else {
             $uri = preg_split("/\?/", $this->uri);
             throw new Exception('Unable to connect to '. $uri[0] . "\nResponse: $response");
         } 
         
         /*
          * inserts HTML line breaks before all newlines in a string
          * @param $debug (String) 
          * @return String returns string with '<br/>' or '<br>' inserted before al newlines.
          */
         if($this->debug) {
            echo ($this->debug);
         }

         /* Final checks and return */
         if(!$this->notification) {
            $rpcversion = $response['jsonrpc']=='2.0'?2:1;

           if($response['id'] != $currentId) {
               throw new Exception('Incorrect response ID (request ID: '. $currentId . ', response ID: '. $response['id'].')');
           }

           if(!is_null($response['error'])) {
               if($rpcversion==1) {
                   throw new Exception('Request error: '. $response['error']);     
               }
               else {
                   $error = (array) $response['error'];
                   throw new Exception($error['message'], $error['code']);     
               }
           }   

           $this->debug = $this->debugclone;
          
           return $response['result'];

         } else {

           return true;
         }
    }

    /* 
     * Sets the notifications state of the object.
     * In this state, notifications are performed, instead of requests.
     *
     * Syntax: String nl2br(String $string, [bool $is_html = true]) 
     *
     * @param $notification (Boolean)
     * @return true;
     */
      public function setRPCNotification($notification) {
             empty($notification) ? $this->notification = false : $this->notification = true;
         return true;  
      }

      public function close() {
          if($this->stream_mode) {
              socket_close($this->stream_socket);
          }
      }

}
?>
