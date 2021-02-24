<?php  

use Latitude_Gateway_Interface as GatewayInterface;

//reference: https://www.sitepoint.com/creating-custom-endpoints-for-the-wordpress-rest-api/
class Latitude_API_Controller extends WP_REST_Controller { 
	//protected $_payment_request_callback;  
	//protected $_gateway;

    public function __construct( GatewayInterface $gateway ) {
		 $this->_gateway = $gateway;
	}
	//protected $version;
	

	/**
	 * Register the REST API routes.
	 */
	public function register_routes() { 

		$namespace = __('latitude/v1');  

		register_rest_route( $namespace, 'callback', array( 
				'methods' => 'POST',
				'callback' => array( $this, 'confirm_payment_request' ),
				'args' => array(),
				'permission_callback' => array( $this, 'confirm_payment_request_permission' )  
		) );

		register_rest_route( $namespace,   'whatsup' , array(
				'methods' => 'GET',
				'callback' => array( $this, 'whatsup' ),
				'args' => array(),
				'permission_callback' => array( $this, 'confirm_payment_request_permission' )  
			)
		);		
	}    

	// public function set_payment_request_callback($callback) {
	// 	$this->_payment_request_callback = $callback;
	// }
	 
	public function confirm_payment_request( $request ) { 
 
		if (!($this->_gateway instanceof GatewayInterface)) {
			return new WP_Error( 'nullinstance', 'null instance', array( 'status' => 404 ) );
		}
		 
		$parameters = $request->get_json_params() ; 
		$result = $this->_gateway->payment_request_callback($parameters);
		return rest_ensure_response( true ); 

		// if (empty($posts)) { 
        //     return new WP_Error( 'empty_category', 'there is no post in this category', array( 'status' => 404 ) );
		// }
		// return new WP_REST_Response($posts, 200); 
		//return new WP_Error( 'cant-delete', __( 'message', 'text-domain' ), array( 'status' => 500 ) );
		//return rest_ensure_response( new WP_Error( 'hardcoded_key', __( 'This site\'s API key is hardcoded and cannot be changed via the API.', 'akismet' ), array( 'status'=> 409 ) ) );
		// return rest_ensure_response( true );
	}


	public function confirm_payment_request_permission() {
		//TODO:  ? return current_user_can( 'confirm_request' );
		return true;
	}

	public function whatsup( $request ) {

		if (!($this->_gateway instanceof GatewayInterface)) {
			return new WP_Error( 'nullinstance', 'null instance', array( 'status' => 404 ) );
		}
		$this->_gateway->sayhello();
		//$custom_data = array( 'custom' => 'Data' );
		$parameters = $request->get_json_params();
		return new WP_REST_Response($parameters, 200);
	}	

	

} 

// function get_awesome_params(WP_REST_Request $request) {
//     // question attributes from angular code
//     $parameters = $request->get_params();
//     return new WP_REST_Response($parameters, 200);
//   }