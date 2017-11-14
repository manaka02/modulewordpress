<?php
/*
Plugin Name: WooCommerce Ariary.net gateway
Description: Extends WooCommerce with ariarynet gateway.
Version: 1.2.4
Author: Nivo SA
Author URI: http://www.nivo.mg/

Copyright: © 2017 Ariary.net.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!defined('ABSPATH')) {
	exit;
}


add_action('plugins_loaded', 'woocommerce_ariary_init', 0);

function woocommerce_ariary_init() {

	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}


	/**
	 * Gateway class
	 */
	class WC_Ariary_Gateway extends WC_Payment_Gateway {

		public $params = null;

		public function __construct() {
			// Go wild in here
			$this->id = 'ccavenue';
			$this->method_title = __('Ariary.net', 'ariary');
			$this->title = __( 'Ariary.net', 'ariarynet' );
			$this->icon = plugins_url('images/logo.png', __FILE__);
			$this->has_fields = false;

			$this->init_form_fields();
			$this->init_settings();

			$this->client_id = $this->settings['client_id'];
			$this->client_secret = $this->settings['client_secret'];
			$this->public_key = $this->settings['public_key'];
			$this->private_key = $this->settings['private_key'];			

			$this->notify_url = str_replace('https:', 'http:', home_url('/wc-api/WC_Ariary_Gateway'));
			
			
			$this->msg['message'] = "";
			$this->msg['class'] = "";

			// add_action('init', array(&$this, 'check_ccavenue_response'));
			//update for woocommerce >2.0
			add_action( 'woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
			add_action('woocommerce_api_wc_ariary_gateway', array(
				$this,
				'check_ariary_response',
			));

			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
					$this,
					'process_admin_options',
				));
			} else {
				add_action('woocommerce_update_options_payment_gateways', array(&$this,
					'process_admin_options',
				));
			}
			add_action( 'init', 'woocommerce_clear_cart_url' );
			add_action('woocommerce_thankyou_ccavenue', array(
				$this,
				'thankyou_page',
			));
		}

	
		function init_form_fields() {

			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'ariary'),
					'type' => 'checkbox',
					'label' => __('Enable CCAvenue Payment Module.', 'ariary'),
					'default' => 'no',
				),
				'client_id' => array(
					'title' => __( 'Client ID', 'client' ),
					'type' => 'text',
					'default' => ''
				),
				'client_secret' => array(
					'title' => __( 'Client Secret', 'ariarynet' ),
					'type' => 'password',
					'default' => ''
				),
				'public_key' => array(
					'title' => __( 'Public key', 'ariarynet' ),
					'type' => 'text',
					'default' => ''
				),
				'private_key' => array(
					'title' => __( 'Private Key', 'ariarynet' ),
					'type' => 'password',
					'default' => ''
				),		
			);
		}
		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 */
		public function admin_options() {
			echo '<h3>' . __('CCAvenue Payment Gateway', 'ariary') . '</h3>';
			echo '<p>' . __('CCAvenue is most popular payment gateway for online shopping in India') . '</p>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}
		/**
		 *  There are no payment fields for CCAvenue, but we want to show the description if set.
		 *
		 */
		function payment_fields() {
			if ($this->description) {
				echo wpautop(wptexturize($this->description));
			}
		}

			/**
     * Checkout receipt page
     *
     * @return void
     */
    public function receipt_page( $order ) {
		$order = wc_get_order( $order );
		$this->includes();
		$params = array(
			'client_id' => $this->client_id, 
			'client_secret' => $this->client_secret,
			'public_key' => $this->public_key,  
			'private_key' => $this->private_key, 
		);
		$id = $order->id;
		$total = $order->data['total'];
		$name = $order->data['billing']['first_name'].' '.$order->data['billing']['last_name'];
		$reference = $order->data['order_key'];
		$adressIp = $_SERVER['REMOTE_ADDR'];

		$util = new Util();
		$data = array(
			'idpaiement' => $util->crypter($this->private_key,432),
			'resultat' => $util->crypter($this->private_key,'success'),
			'idpanier' => $util->crypter($this->private_key,32),
			'montant' => $util->crypter($this->private_key,4320),
			'ref_int' => $util->crypter($this->private_key,'vente IPod')
		);

	
		$result = [];
		foreach ($data as $key => $value) {
			$temp = $util->decrypter($this->private_key,$value);
			var_dump($temp);
			$result[$key] = json_decode($temp);
		}
		
		echo json_encode($result);
		
		$idPaie = $paiement->initPaie(
			$id,
			$total,
			$name,
			$reference,
			$adressIp
		);
	

		$link = 'https://moncompte.ariarynet.com/paiement/'.$idPaie;
		// echo(str_replace('https:', 'http:', home_url('/wc-api/WC_Mrova_Ccave')));

		echo '<a class="button alt" href="' . $link . '">';
		echo __( 'Go To Ariary.net', 'flw-payments' ) . '</a>';

		echo '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">';
		echo __( 'Cancel order &amp; restore cart', 'flw-payments' ) . '</a>';

		
  
	  }


	  public function includes(){
		require_once('Util.php' );
		require_once('Paiement.php' );
	  }


		function process_payment($order_id) {
			$order = new WC_Order($order_id);

			$this->includes();
			$params = array(
				'client_id' => $this->client_id, 
				'client_secret' => $this->client_secret,
				'public_key' => $this->public_key,  
				'private_key' => $this->private_key, 
			);
			$id = $order->id;

			$total = $order->data['total'];
			$name = $order->data['billing']['first_name'].' '.$order->data['billing']['last_name'];
			$reference = $order->data['order_key'];
			$adressIp = '10.0.0.146';
	
			$paiement = new Paiement($params);
			$idPaie = $paiement->initPaie(
				$id,
				$total,
				$name,
				$reference,
				$adressIp
			);

			$link = 'https://moncompte.ariarynet.com/paiement/'.$idPaie;

			return array(
				'result' => 'success',
				// 'redirect' => $link,
				'redirect' => $order->get_checkout_payment_url( true ),
			);
		}


		/**
		 * Check for valid CCAvenue server callback
		 *
		 */
		function check_ariary_response() {
			global $woocommerce;

			$this->includes();
			$msg['class'] = 'success';
			$msg['message'] = "Nous vous remercions d'avoir fair votre achat avec nous.Vidé le izy";

			// if($_REQUEST != null){
				$util = new Util();

				$encResult = $_REQUEST["resultat"];
				// echo strlen($_REQUEST["resultat"]).' ty n halavanle request[resultat]';

				echo ($util->decrypter($private_key, $_REQUEST["resultat"]).' farany ty rangah ah');
				// echo $encResult;
			// 	$keySub = substr($this->private_key, 0, 24);
			// 	$result = $util->decrypter($keySub, $encResult);
			// 	echo $result;
			// 		if($result != null){
			// 			echo 'result';
			// 			$order_id = $_REQUEST["idpanier"];
			// 			$order_id = $util->decrypter($keySub, $encIdPanier);
			// 			$order = new WC_Order($order_id);
			// 			$order->payment_complete();
			// 			$order->add_order_note('Ariary payment successful<br/>Bank Ref Number: 43');
			// 			$order->payment_complete();
			// 			$woocommerce->cart->empty_cart();
			// 		}else{
			// 			echo 'tsy tafa v vida le result cryptage ah';
			// 		}

				
			// }else{
			// 	echo "tsy tafiditr";
			// }
			exit;


			// WC()->cart->empty_cart();

			// var_dump($woocommerce);

			// if (isset($_REQUEST['resultat'])) {
			// 	$util = new Util();

			// 	$encResult = $_REQUEST["resultat"];
			// 	$encIdpaiement = $_REQUEST["idpaiement"];

			// 	$result= decrypt($this->private_key, $encResponse);
			// 	$idPaiement = decrypt($this->private_key, $encIdpaiement);


			// 	$msg['message'] .= $result.$idPaiement;
				
			// 	var_dump($msg);
			// 	if($result == 'success'){

			// 	}else if ($result =='error'){

			// 	}else{

			// 	}
				// if (function_exists('wc_add_notice')) {
				// 	wc_add_notice($msg['message'], $msg['class']);
				// } else {
				// 	if ($msg['class'] == 'success') {
				// 		$woocommerce->add_message($msg['message']);
				// 	} else {
				// 		$woocommerce->add_error($msg['message']);
				// 	}
				// 	$woocommerce->set_messages();
				// }
				// $redirect_url = get_permalink(woocommerce_get_page_id('myaccount'));
				// wp_redirect($redirect_url);
				// exit;

			// 	// $decryptValues = array();
			// 	// parse_str($rcvdString, $decryptValues);
			// 	// $order_id_time = $decryptValues['order_id'];
			// 	// $order_id = explode('_', $decryptValues['order_id']);
			// 	// $order_id = (int) $order_id[0];
			// 	$order_id = '';
				
				
				
			// 	if ($order_id != '') {
			// 		try {
			// 			$order = new WC_Order($order_id);
			// 			$order_status = $decryptValues['order_status'];
			// 			$transauthorised = false;
			// 			if ($order->status !== 'completed') {
			// 				if ($order_status == "Success") {
			// 					$transauthorised = true;
			// 					$msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
			// 					$msg['class'] = 'success';
			// 					if ($order->status != 'processing') {
			// 						$order->payment_complete();
			// 						$order->add_order_note('CCAvenue payment successful<br/>Bank Ref Number: ' . $decryptValues['bank_ref_no']);
			// 						$woocommerce->cart->empty_cart();
			// 					}
			// 				} else if ($order_status === "Aborted") {
			// 					$msg['message'] = "Thank you for shopping with us. We will keep you posted regarding the status of your order through e-mail";
			// 					$msg['class'] = 'success';
			// 				} else if ($order_status === "Failure") {
			// 					$msg['class'] = 'error';
			// 					$msg['message'] = "Thank you for shopping with us. However, the transaction has been declined tsy tafa voloany ah.";
			// 				} else {
			// 					$msg['class'] = 'error';
			// 					$msg['message'] = "Thank you for shopping with us. However, the transaction has been declined. tsy tafa faharoa";
			// 				}

			// 				if ($transauthorised == false) {
			// 					$order->update_status('failed');
			// 					$order->add_order_note('Failed');
			// 					$order->add_order_note($this->msg['message']);
			// 				}
			// 			}
			// 		} catch (Exception $e) {

			// 			$msg['class'] = 'error';
			// 			$msg['message'] = $e->getMessage();
			// 		}
			// 	}
			// }
		}
	}
	/**
	 * Add the Gateway to WooCommerce
	 *
	 */
	function woocommerce_add_mrova_ccave_gateway($methods) {
		$methods[] = 'WC_Ariary_Gateway';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_mrova_ccave_gateway');
}




?>
