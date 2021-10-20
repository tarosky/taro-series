<?php

namespace Tarosky\Series\Pattern;


/**
 * Register block.
 */
abstract class BlockPattern extends Singleton {

	/**
	 * Register hooks.
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_block' ], 30 );
	}

	/**
	 * Block name.
	 *
	 * @return string
	 */
	abstract protected function block_name();

	/**
	 * Arguments to register.
	 *
	 * @return array
	 */
	protected function arguments() {
		$args = [
			'editor_script' => $this->editor_script(),
		];
		foreach ( [ 'script', 'style', 'editor_style' ] as $key ) {
			$handle = $this->{$key}();
			if ( $handle ) {
				$args[ $key ] = $handle;
			}
		}
		return $args;
	}

	/**
	 * Register block
	 */
	public function register_block() {
		register_block_type( $this->block_name(), $this->arguments() );
	}

	/**
	 * Public script
	 *
	 * @return string
	 */
	protected function script() {
		return '';
	}

	/**
	 * Public script
	 *
	 * @return string
	 */
	abstract protected function editor_script();

	/**
	 * Editor style
	 *
	 * @return string
	 */
	protected function editor_style() {
		return '';
	}

	/**
	 * Block style.
	 *
	 * @return string
	 */
	protected function style() {
		return '';
	}
}
