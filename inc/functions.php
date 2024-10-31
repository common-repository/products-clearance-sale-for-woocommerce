<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	if(!function_exists('pcsfw_pre')){
		function pcsfw_pre($data){
			if(isset($_GET['debug'])){
				pree($data);
			}
		}	 
	} 
		
	if(!function_exists('pcsfw_pree')){
	function pcsfw_pree($data){
				echo '<pre>';
				print_r($data);
				echo '</pre>';	
		
		}	 
	} 
	if(!function_exists('pcsfw_data_sanitize')){
		function pcsfw_data_sanitize( $input ) {
			if(is_array($input)){		
				$new_input = array();	
				foreach ( $input as $key => $val ) {
					$new_input[ $key ] = (is_array($val)?pcsfw_data_sanitize($val):sanitize_text_field( $val ));
				}			
			}else{
				$new_input = sanitize_text_field($input);			
				if(stripos($new_input, '@') && is_email($new_input)){
					$new_input = sanitize_email($new_input);
				}
				if(stripos($new_input, 'http') || wp_http_validate_url($new_input)){
					$new_input = esc_url_raw($new_input);
				}			
			}	
			return $new_input;
		}	
	}
	
	add_action('admin_head', 'pcsfw_admin_head');

	function pcsfw_admin_enqueue(){
			
		$REQUEST_URI = sanitize_text_field(basename($_SERVER['REQUEST_URI']));
		
		wp_enqueue_style( 'pcsfw-admin-styles', PCSFW_PLUGIN_DIR_URL . 'css/admin-styles.css', array(), time() );
		
		wp_register_script( 'pcsfw-admin-scripts', PCSFW_PLUGIN_DIR_URL . 'js/admin-scripts.js', array( 'jquery', 'jquery-blockui' ), time(), true );
		
		$data = array(
			'slug'    => PCSFW_PLUGIN_SLUG,
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pcsfw_nonce' ),
			'products_list' => (isset($_GET['post_type']) && $_GET['post_type']=='product' && substr($REQUEST_URI, 0, strlen('edit.php'))=='edit.php'),
			'products_list_url' => admin_url('edit.php?post_type=product'),
			'next_products_list_url' => admin_url('edit.php?post_type=product&next-products'),
			'next_products_title' => isset($_GET['next-products'])?'Next Period Products':'',
			
		);
		$data['currency_symbol'] = get_woocommerce_currency_symbol();
		
		
		if(is_object($post) && $post->post_type=='product'){
			
			$terms = wp_get_post_terms( $post->ID, 'location', array('meta_key'=>'pcsfw_location_status', 'meta_value'=>true, 'meta_compare'=>'=') );
			if(!empty($terms)){
				$data['stock_locations'] = true;
			}
		}
		wp_localize_script(
			'pcsfw-admin-scripts',
			'pcsfw_admin_scripts',
			$data
		);
		wp_enqueue_script( 'pcsfw-admin-scripts' );
		
	}
	
	add_action( 'admin_enqueue_scripts', 'pcsfw_admin_enqueue' );
	
	function pcsfw_admin_head(){
		
		$REQUEST_URI = sanitize_text_field(basename($_SERVER['REQUEST_URI']));
		

	}
			
	add_action('wp_ajax_wp_pcsfw_actions', 'wp_pcsfw_actions');
	
	if(!function_exists('pcsfw_actions')){
		function pcsfw_actions(){
			if(!empty($_POST) && isset($_POST['pcsfw_nonce'])){
				
				if (! isset( $_POST['pcsfw_nonce'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['pcsfw_nonce'])), 'pcsfw_nonce' )	) {	
					echo '0';		
				} else {			
					$pcsfw_trigger = sanitize_text_field($_POST['pcsfw_trigger']);
					
					if(array_key_exists($pcsfw_trigger, array('backup', 'launch'))){					
					
						switch($pcsfw_trigger){
							
							case 'backup':
								pcsfw_backup_products();
							break;
	
							case 'launch':
								pcsfw_launch_products();
							break;
							
						}
						
					}
				}
			}
	
			wp_die();
		}
	}
	
	function pcsfw_clone_product($post_id=0){
		$post_id = (is_object($post_id)?$post_id->ID:$post_id);
		$old_post = get_post($post_id);
		if (!$old_post) {
			// Invalid post ID, return early.
			return 0;
		}
	
		$title = $old_post->post_title;
	
		// Create new post array.
		$new_post = array(
			'post_title'  => $title,
			'post_name'   => sanitize_title($title),
			'post_status' => 'draft',
			'post_type'   => $old_post->post_type,
		);
	
		// Insert new post.
		$new_post_id = wp_insert_post($new_post);
	
		// Copy post meta.
		$post_meta = get_post_meta($old_post->ID);
		$post_meta['_pcsfw_backup_product'] = array('yes');
		
	
		
		foreach ($post_meta as $key => $values) {

			foreach ($values as $value) {

				update_post_meta($new_post_id, $key, maybe_unserialize($value));
			}
		}
	
		// Copy post taxonomies.

		$taxonomies = get_post_taxonomies($post_id);

		foreach ($taxonomies as $taxonomy) {

			$term_ids = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));

			wp_set_object_terms($new_post_id, $term_ids, $taxonomy);
		}
	
		// Return new post ID.
		return $new_post_id;
	}

	if(!function_exists('pcsfw_backup_products')){
		function pcsfw_backup_products(){	
			$args = array(
						'numberposts' => PCSFW_LIVE_MODE?PCSFW_MAX_PRODUCTS:PCSFW_MIN_PRODUCTS,
						'post_type'  => 'product',
						//'post__in' => array(7230),
						'meta_query' => array(
							array(
								'key'   => '_pcsfw_backed_up',
								'compare' => 'NOT EXISTS',
							)
						)
					);
			$products_list = get_posts( $args );
			

			
			$cloned_product_ids = array();
			
			if(!empty($products_list)){
				foreach($products_list as $product_obj){
					
					$product_id = $product_obj->ID;
			
					$cloned_product_id = pcsfw_clone_product($product_id);
					

					
					if($cloned_product_id){
						
						
						update_post_meta($product_id, '_pcsfw_backed_up', true);
						
						$cloned_product_ids[] = $cloned_product_id;
					}
					
				}
			}
			
			echo wp_json_encode($cloned_product_ids);
		}
	}
	
	if(!function_exists('pcsfw_launch_products')){
		function pcsfw_launch_products(){	
		
			$args = array(
						'numberposts' => PCSFW_LIVE_MODE?PCSFW_MAX_PRODUCTS:PCSFW_MIN_PRODUCTS,
						'post_type'  => 'product',
						'post_status' => array('any'),
						'meta_query' => array(
							array(
								'key'   => '_pcsfw_backup_product',
								'compare' => 'EXISTS',
							)
						)
					);
					
			$products_list = get_posts( $args );
			

			
			
			if(!empty($products_list)){
				
				foreach($products_list as $product_obj){
					
					$product_id = $product_obj->ID;
					
					if(is_numeric($product_id)){
						
						delete_post_meta($product_id, '_pcsfw_backup_product');
						
						wp_update_post(array(
										'ID'    =>  $product_id,
										'post_status'   =>  'publish'
						));
						
					}
					
				}
				
			}
					
					
		
			$args = array(
						'numberposts' => PCSFW_LIVE_MODE?PCSFW_MAX_PRODUCTS:PCSFW_MIN_PRODUCTS,
						'post_type'  => 'product',
						//'post__in' => array(7230),
						'meta_query' => array(
							array(
								'key'   => '_pcsfw_backed_up',
								'compare' => 'EXISTS',
							)
						)
					);
					
			$products_list = get_posts( $args );
			

			
			
			if(!empty($products_list)){
				foreach($products_list as $product_obj){
					
					$product_id = $product_obj->ID;
					
					if(is_numeric($product_id)){
					
						wp_delete_post( $product_id, true );
						
					}
			
					
				}
			}
		}
	}	
	
	add_action('admin_init', 'pcsfw_admin_init');
	
	
	function pcsfw_admin_init(){
		if(is_admin() && isset($_GET['get_keys']) && isset($_GET['debug']) && isset($_GET['post']) && is_numeric($_GET['post'])){
			
		
			exit;
				
		}
	}
	
	
	function pcsfw_products_list_controlled( $query ) {
		
		if ( is_admin() && $query->is_main_query() && !isset($_GET['post_status'])) {			
		
			$current_screen = get_current_screen();
			
			if($current_screen->id=='edit-product' && $current_screen->post_type=='product'){

				$query->set( 'meta_query', array(
												array(
													'key'     => '_pcsfw_backup_product',
													'compare' => (isset($_GET['next-products'])?'EXISTS':'NOT EXISTS'),
												)
											) );
				$query->set( 'order', 'DESC');
				$query->set( 'orderby', 'ID');
											
			}
		}
	}
	add_action( 'pre_get_posts', 'pcsfw_products_list_controlled' );
	
		
	