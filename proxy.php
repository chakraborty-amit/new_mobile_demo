<?PHP
$enable_jsonp    = false;
$enable_native   = true;
$valid_forcecom_url_regex = '/https:\/\/.*\.(salesforce|database|cloudforce)\.com/';


$url_query_param = null; // 'url'
$url_header = 'SalesforceProxy-Endpoint';


$authz_header = 'X-Authorization';
// added for getting headers from the apache server - Amit
$headers_data = null;

$return_all_headers = true;

$cors_allow_origin  = 'http:\\localhost:81\demo';
$cors_allow_methods = 'GET, POST, PUT, PATCH, DELETE, HEAD';
$cors_allow_headers = 'Authorization, Content-Type';

// ############################################################################

$status = array();

if ( $url_query_param != null ) {
	$url = isset($_GET[$url_query_param]) ? $_GET[$url_query_param] : null;
//} else if ( $url_header != null ) {
} else if ( isset($_GET['instance'])) {
     
	//$url = isset($_SERVER[$url_header]) ? $_SERVER[$url_header] : null;
	//$url = 'https://ap1.salesforce.com/services/data/v27.0/query?q=SELECT%20Name%20FROM%20Book__c'; 
	  $url =$_GET['instance'].$_GET['version']."?q=".$_GET['query'];
	 
// Added by Amit	 
} else if(!isset($_GET['instance']))
      {
           
           //$url = isset($_SERVER[$url_header]) ? $_SERVER[$url_header] : null;		   
		   
		   /*foreach (getallheaders() as $name => $value) {
                  echo "$name: $value\n";
               }
			   */
			   
			     $headers_data = apache_request_headers();
				 //$url = isset($headers_data[$url_header]) ? $headers_data[$url_header] : null;
				  $url = urldecode($headers_data[$url_header]);
				 //echo ($headers_data[$url_header]);
				 //echo ($url);
				 
		   
	  }	  
else {
	$url = null;
}

if ( !$url ) {
  
  // Passed url not specified.
  $contents = 'ERROR: url not specified';
  $status['http_code'] = 400;
  $status['status_text'] = 'Bad Request';
  
} 
/*else if ( !preg_match( $valid_forcecom_url_regex, $url )) {
  
  // Passed url doesn't match $valid_url_regex.
  $contents = 'ERROR: invalid url';
  $status['http_code'] = 400;
  $status['status_text'] = 'Bad Request' ;

}*/ 
else {

  if ( isset( $cors_allow_origin ) ) {
    header( 'Access-Control-Allow-Origin: '.$cors_allow_origin );
    if ( isset( $cors_allow_methods ) ) {
      header( 'Access-Control-Allow-Methods: '.$cors_allow_methods );
    }
    if ( isset( $cors_allow_headers ) ) {
      header( 'Access-Control-Allow-Headers: '.strtolower($cors_allow_headers) );
    }
    //if ( isset($_SERVER['REQUEST_METHOD']) && 
	  if ( isset($headers_data['REQUEST_METHOD']) && 
        //($_SERVER['REQUEST_METHOD'] == 'OPTIONS') ) {
		($headers_data['REQUEST_METHOD'] == 'OPTIONS') ) {
      // We're done - don't proxy CORS OPTIONS request
      exit();
	}
  }

  $ch = curl_init( $url );

  
  // Pass on request method, regardless of what it is
  curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 
      //isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET' );
	    isset($headers_data['REQUEST_METHOD']) ? $headers_data['REQUEST_METHOD'] : 'GET' );
     
	  
  // Pass on content, regardless of request method
  if ( (isset($headers_data['CONTENT_LENGTH'] ) && $headers_data['CONTENT_LENGTH'] > 0) ||
       (isset($headers_data['HTTP_CONTENT_LENGTH'] ) && $headers_data['HTTP_CONTENT_LENGTH'] > 0) ) {
    curl_setopt( $ch, CURLOPT_POSTFIELDS, file_get_contents("php://input") );
  }
// passing content explicitly
//echo(file_get_contents("php://input"));
curl_setopt( $ch, CURLOPT_POSTFIELDS, file_get_contents("php://input") );
   
  if ( isset($_GET['send_cookies']) ) {
    $cookie = array();
    foreach ( $_COOKIE as $key => $value ) {
      $cookie[] = $key . '=' . $value;
    }
    if ( isset($_GET['send_session']) ) {
      $cookie[] = SID;
    }
    $cookie = implode( '; ', $cookie );
    
    curl_setopt( $ch, CURLOPT_COOKIE, $cookie );
  }

  $headers = array();
  //if ( isset($authz_header) && isset($_SERVER[$authz_header]) ) {
  if ( isset($authz_header) ) {
    // Set the Authorization header
	 
	
 // array_push($headers, "Authorization: ".$_SERVER[$authz_header] );
 if (isset($_GET['instance']))
 {
       array_push($headers, "Authorization: ".$_GET['auth'] );
	   }
else if (!isset($_GET['instance']))
{ 
		//echo ($headers_data[$authz_header]);	
		array_push($headers, "Authorization: ".urldecode($headers_data[$authz_header]) );
		
		
}	   
	   
  }
  // adding the content type explicitly
     array_push($headers, "Content-Type: ".'application/json');
  
  
  
  if ( isset($headers_data['CONTENT_TYPE']) ) {
	// Pass through the Content-Type header
	array_push($headers, "Content-Type: ".$headers_data['CONTENT_TYPE'] );
  } 
  elseif ( isset($headers_data['HTTP_CONTENT_TYPE']) ) {
    // Pass through the Content-Type header
    array_push($headers, "Content-Type: ".$headers_data['HTTP_CONTENT_TYPE'] );
  }
  if ( isset($headers_data['HTTP_SOAPACTION']) ) {
    // Pass through the SOAPAction header
    array_push($headers, "SOAPAction: ".$headers_data['HTTP_SOAPACTION'] );
  }
  if ( isset($headers_data['HTTP_X_USER_AGENT']) ) {
	// Pass through the X-User-Agent header
	array_push($headers, "X-User-Agent: ".$headers_data['HTTP_X_USER_AGENT'] );
  }
  if ( isset($headers_data['HTTP_X_FORWARDED_FOR']) ) {
	array_push($headers, $headers_data['HTTP_X_FORWARDED_FOR'].", ".$headers_data['HTTP_X_USER_AGENT'] );
  } else if (isset($headers_data['REMOTE_ADDR'])) {
	array_push($headers, "X-Forwarded-For: ".$headers_data['REMOTE_ADDR'] );
  }

  // showing the values of the $headers
		/*foreach ($headers as $value) {
                    echo "Value: $value<br />\n";
					
                            }*/
							
   
  if ( count($headers) > 0 ) {
     
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
  }
  
  curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
  curl_setopt( $ch, CURLOPT_HEADER, true );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  
  curl_setopt( $ch, CURLOPT_USERAGENT, 
	//isset($_GET['user_agent']) ? $_GET['user_agent'] : $headers_data['HTTP_X_USER_AGENT']);
	isset($_GET['user_agent']) ? $_GET['user_agent']:'salesforce-toolkit-rest-javascript/v27.0');
  

  list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', curl_exec( $ch ), 2 );
  
  $status = curl_getinfo( $ch );
  
  if ( curl_errno( $ch ) ) {
    $status['http_code'] = 500;
    $contents = "cURL error ".curl_errno( $ch ).": ".curl_error( $ch )."\n";
  }
  
  curl_close( $ch );
}

// Split header text into an array.
$header_text = isset($header) ? preg_split( '/[\r\n]+/', $header ) : array();

if ( isset($_GET['mode']) && $_GET['mode'] == 'native' ) {
  if ( !$enable_native ) {
    $contents = 'ERROR: invalid mode';
    $status['http_code'] = 400;
    $status['status_text'] = 'Bad Request';
  }

  if ( isset( $status['http_code'] ) ) {
      $header = "HTTP/1.1 ".$status['http_code'];
      if (isset($status['status_text'])) {
          $header .= " ".$status['status_text'];
      }
      header( $header );

      $header_match = '/^(?:Content-Type|Content-Language|Set-Cookie)/i';
  } else {
      $header_match = '/^(?:HTTP|Content-Type|Content-Language|Set-Cookie)/i';
  }
  
  foreach ( $header_text as $header ) {
    if ( preg_match( $header_match, $header ) ) {
      header( $header );
    }
  }

  print $contents;
  
} else {
  
  // $data will be serialized into JSON data.
  $data = array();
  
  // Propagate all HTTP headers into the JSON data object.
  if ( isset($_GET['full_headers']) ) {
    $data['headers'] = array();
    
    foreach ( $header_text as $header ) {
      preg_match( '/^(.+?):\s+(.*)$/', $header, $matches );
      if ( $matches ) {
        $data['headers'][ $matches[1] ] = $matches[2];
      }
    }
  }
  
  // Propagate all cURL request / response info to the JSON data object.
  if ( isset($_GET['full_status']) ) {
    $data['status'] = $status;
  } else {
    $data['status'] = array();
    $data['status']['http_code'] = $status['http_code'];
  }
  
  // Set the JSON data object contents, decoding it from JSON if possible.
  $decoded_json = json_decode( $contents );
  $data['contents'] = $decoded_json ? $decoded_json : $contents;

  // Generate appropriate content-type header.
  $is_xhr = isset($headers_data['HTTP_X_REQUESTED_WITH']) && 
      (strtolower($headers_data['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
  header( 'Content-type: application/' . ( $is_xhr ? 'json' : 'x-javascript' ) );
  
  // Get JSONP callback.
  $jsonp_callback = ($enable_jsonp && isset($_GET['callback'])) ? $_GET['callback'] : null;
  
  // Generate JSON/JSONP string
  $json = json_encode( $data );
  
  print $jsonp_callback ? "$jsonp_callback($json)" : $json;

}

?>