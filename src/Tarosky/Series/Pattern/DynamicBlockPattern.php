<?php

namespace Tarosky\Series\Pattern;


/**
 * Dynamic block pattern.
 */
abstract class DynamicBlockPattern extends BlockPattern {

	/**
	 * @inheritDoc
	 */
	public function init() {
		parent::init();
		add_action( 'enqueue_block_editor_assets', [ $this, 'add_block_variables' ], 1 );
	}

	/**
	 * @inheritDoc
	 */
	protected function arguments() {
		$arguments  = parent::arguments();
		$attributes = $this->get_block_attributes();
		if ( ! empty( $attributes ) ) {
			$arguments['attributes'] = $attributes;
		}
		$arguments['render_callback'] = [ $this, 'render_callback' ];
		return $arguments;
	}

	/**
	 * Block attributes.
	 *
	 * @return array
	 */
	protected function get_block_attributes() {
		return [];
	}

	/**
	 * Render block content;
	 *
	 * @param array  $arguments Block arguments.
	 * @param string $content   Contents.
	 * @return string
	 */
	abstract public function render_callback( $arguments, $content = '' );

	/**
	 * Add block variables.
	 */
	public function add_block_variables() {
		$name   = array_map( 'ucfirst', preg_split( '#[/\-]#u', $this->block_name() ) );
		$name[] = 'Vars';

		wp_localize_script( $this->editor_script(), implode( '', $name ), $this->block_variable_filter( [
			'name' => $this->block_name(),
			'attributes' => $this->get_block_attributes(),
		] ) );
	}

	/**
	 * Filter block variables.
	 *
	 * @param array $vars Variables.
	 * @return array
	 */
	protected function block_variable_filter( $vars ) {
		return $vars;
	}
}
