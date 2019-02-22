<?php if ( ! defined( 'ABSPATH' ) ) exit;

class CS_Ajax {

	public static function init() {
		$class = __CLASS__;
		new $class;
	}



	public function __construct() {

		global $nf, $current_user;

		// Process any form
		add_action('wp_ajax_do_process_form', [$this, 'process_form']);
		add_action('wp_ajax_nopriv_do_process_form', [$this, 'process_form']);
	}



	public function process_form() {

		if ( ! check_ajax_referer( 'nonce', 'nonce' ) )
			wp_die('Verification Failed');

		$data = get_key('data', $_REQUEST);
		$type = get_key('form', $_REQUEST);

		$r = [
			'code'   => 501,
			'errors' => 'Something went wrong, please try again later'
		];

		// Validation failed
		if ( get_key('errors', $data) )
			wp_die(json_encode($data));


		switch ($type) :
			case 'signin':

				$r = $this->user_signin($data);
			break;
		endswitch;


		wp_die(json_encode($r));
	}





	private function user_signin( $data ) {

		return $data;
	}
}
add_action('init', ['CS_Ajax', 'init']);