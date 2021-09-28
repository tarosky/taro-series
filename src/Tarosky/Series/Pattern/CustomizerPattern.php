<?php

namespace Tarosky\Series\Pattern;

/**
 * Customizer pattern.
 */
abstract class CustomizerPattern extends Singleton {

	/**
	 * @var string ID of section.
	 */
	protected $section_id = 'series-setting';

	/**
	 * @var bool Section is registered?
	 */
	private static $is_section_registered = false;

	/**
	 * Return panel id.
	 *
	 * @return string
	 */
	abstract protected function id();

	/**
	 * Get field for this setting.
	 *
	 * @return array{ type:string, capability?:string, default?:string, transport?:string }
	 */
	protected function get_setting() {
		return [
			'type'      => 'option',
			'transport' => 'refresh'
		];
	}

	/**
	 * Controller settings.
	 *
	 * @return array{type:string, priority:int, active_callback?:callable, input_attrs:array, type:string, label:string, description:string}
	 */
	protected function controller_args() {
		return [
			'priority' => 10,
			'section'  => $this->section_id,
		];
	}

	/**
	 * Initialize.
	 */
	final protected function init() {
		if ( ! self::$is_section_registered ) {
			self::$is_section_registered = true;
			add_action( 'customize_register', [ $this, 'register_section' ], 10 );
		}
		add_action( 'customize_register', [ $this, 'register_field' ], 11 );
		$this->register_hooks();
	}

	/**
	 * To register hooks, here.
	 */
	protected function register_hooks() {
		// Do something.
	}

	/**
	 * Register section.
	 *
	 * @param \WP_Customize_Manager $wp_customizer Customizer.
	 */
	final public function register_section( $wp_customizer ) {
		$wp_customizer->add_section( $this->section_id, [
			'title'       => __( 'Series Setting', 'taro-series' ),
			'priority'    => 160,
			'description' => __( 'Please specify how series looks like in your theme.', 'taro-series' ),
		] );
	}

	/**
	 * Register customizer field.
	 *
	 * @param \WP_Customize_Manager $wp_customizer Customizer.
	 */
	public function register_field( $wp_customizer ) {
		$wp_customizer->add_setting( $this->id(), $this->get_setting() );
		$wp_customizer->add_control( $this->id(), array_merge( $this->controller_args(), [
			'section' => $this->section_id,
		] ) );
	}

	/**
	 * Get option value.
	 *
	 * @return mixed
	 */
	public static function get() {
		return get_option( static::get_instance()->id() );
	}
}
