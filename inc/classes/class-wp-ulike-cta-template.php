<?php
/**
 * WP ULike Process Class
 * // @echo HEADER
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_cta_template' ) ) {

	class wp_ulike_cta_template extends wp_ulike_entities_process{

		protected $item_settings, $method_id, $args;

		private $isDistinct;

		/**
		 * Constructor
		 */
		function __construct( $args ){
			$this->args = $args;
			$this->item_settings = new wp_ulike_setting_type_repo( $this->args['slug'] );
			parent::__construct();

			$this->setIsDistinct();
		}

		private function setIsDistinct(){
			$this->isDistinct = parent::isDistinct( $this->item_settings->getMethod() );
		}

		public function display(){
			parent::setPrevStatus( $this->args['slug'], $this->args['id'], $this->args['table'], $this->args['column'] );
			return $this->get_template( $this->get_method_id() );
		}

		public function get_method_id(){
			switch( $this->item_settings->getMethod() ){
				case 'do_not_log':
					return 1;
				case 'by_cookie':
					return parent::hasPermission( array(
						'method' => $this->item_settings->getMethod(),
						'type'   => $this->item_settings->getCookieName(),
						'id'     => $this->args['id']
					) ) ? 1 : 4;
				default:
					if( ! parent::getPrevStatus() ){
						return 1;
					} else {
						return substr( parent::getPrevStatus(), 0, 2 ) !== "un" ? 2 : 3;
					}
			}
		}

		public function get_template( $method_id ){

			$user_status = parent::getPrevStatus() && $this->isDistinct ? parent::getPrevStatus() : 'like';

			//Primary button class name
			$button_class_name	= str_replace( ".", "", apply_filters( 'wp_ulike_button_selector', 'wp_ulike_btn' ) );
			//Button text value
			$button_text		= '';

			// Get all template callback list
			$temp_list = call_user_func( 'wp_ulike_generate_templates_list' );
			$func_name = isset( $temp_list[ $this->args['style'] ]['callback'] ) ? $temp_list[ $this->args['style'] ]['callback'] : 'wp_ulike_set_default_template';

			if( $this->args['button_type'] == 'image' || ( isset( $temp_list[$this->args['style']]['is_text_support'] ) && ! $temp_list[$this->args['style']]['is_text_support'] ) ){
				$button_class_name .= ' wp_ulike_put_image';
 				if( in_array( $method_id, array( 2, 4 ) ) ){
					$button_class_name .= ' image-unlike wp_ulike_btn_is_active';
				}
			} else {
				$button_class_name .= ' wp_ulike_put_text';
 				if( in_array( $method_id, array( 2, 4 ) ) && strpos( $user_status, 'dis') !== 0){
					$button_text = wp_ulike_get_button_text( 'unlike', $this->args['setting'] );
				} else {
					$button_text = wp_ulike_get_button_text( 'like', $this->args['setting'] );
				}
			}

			// Add unique class name for each button
			$button_class_name .= strtolower( ' wp_' . $this->args['method'] . '_' . $this->args['id'] );

			$total_likes   = wp_ulike_get_counter_value( $this->args['id'], $this->args['slug'], 'like', $this->isDistinct );
			$formatted_val = apply_filters( 'wp_ulike_count_box_template', '<span class="count-box">'. wp_ulike_format_number( $total_likes ) .'</span>' , $total_likes );
			$this->args['is_distinct'] = $this->isDistinct;

			$wp_ulike_template 	= apply_filters( 'wp_ulike_add_templates_args', array(
					"ID"               => esc_attr( $this->args['id'] ),
					"wrapper_class"    => esc_attr( $this->args['wrapper_class'] ),
					"slug"             => esc_attr( $this->args['slug'] ),
					"counter"          => $formatted_val,
					"total_likes"      => $total_likes,
					"type"             => esc_attr( $this->args['method'] ),
					"status"           => esc_attr( $method_id ),
					"user_status"      => esc_attr( $user_status ),
					"setting"      	   => esc_attr( $this->args['setting'] ),
					"attributes"       => $this->args['attributes'],
					"style"            => esc_html( $this->args['style'] ),
					"button_type"      => esc_html( $this->args['button_type'] ),
					"display_likers"   => esc_attr( $this->args['display_likers'] ),
					"disable_pophover" => esc_attr( $this->args['disable_pophover'] ),
					"button_text"      => $button_text,
					"general_class"    => $this->get_general_selectors( $method_id ),
					"button_class"     => esc_attr( $button_class_name )
				), $this->args, $temp_list
			);

			$final_template = call_user_func( $func_name, $wp_ulike_template );

			return apply_filters( 'wp_ulike_return_final_templates', preg_replace( '~>\s*\n\s*<~', '><', $final_template ), $wp_ulike_template );

		}

		private function get_general_selectors( $method_id ){
			$selectors	= str_replace( ".", "", apply_filters( 'wp_ulike_general_selector', 'wp_ulike_general_class' ) );

			switch ( $method_id ){
				case 0:
					$selectors .= ' wp_ulike_is_not_logged';
					break;
				case 1:
					$selectors .= ' wp_ulike_is_not_liked';
					break;
				case 2:
					$selectors .= ' wp_ulike_is_liked';
					break;
				case 3:
					$selectors .= ' wp_ulike_is_unliked';
					break;
				case 4:
					$selectors .= ' wp_ulike_is_already_liked';
			}

			return esc_attr( $selectors );
		}


	}

}