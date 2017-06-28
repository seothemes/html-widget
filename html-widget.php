<?php
/**
 * Plugin name: HTML Widget
 * Plugin URI:  http://github.com/seothemes/html-widget
 * Description: Adds a simple HTML Code Widget with syntax highlighting for HTML, CSS and JavaScript code.
 * Version:     0.1.0
 * Author:      SEO Themes
 * Author URI:  https://seothemes.net
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: html-widget
 * Domain Path: /languages
 *
 * @link    http://github.com/seothemes/html-widget
 * @since   0.1.0
 * @package HTML_Widget
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Register text domain for translation.
add_action( 'plugins_loaded', 'html_widget_load_plugin_textdomain' );

// Register plugin scripts and styles.
add_action( 'admin_enqueue_scripts', 'html_widget_scripts_styles' );

// Register new HTML Widget instance.
add_action( 'widgets_init', function() {
	register_widget( 'HTML_Widget' );
} );

// Enable shortcodes in HTML Widget.
add_filter( 'html_widget_content', 'do_shortcode' );

/**
 * Plugin text domain.
 *
 * @since 0.1.0
 */
function html_widget_load_plugin_textdomain() {
	load_plugin_textdomain( 'html-widget', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}

/**
 * Enqueue scripts and styles.
 *
 * @since 0.1.0
 */
function html_widget_scripts_styles() {

	// Return early if not Widgets screen or Customizer.
	if ( get_current_screen()->id !== 'widgets' && ! is_customize_preview() ) {
		return;
	}

	// Codemirror scripts and styles.
	wp_enqueue_style( 'html-widget', plugin_dir_url( __FILE__ ) . 'codemirror/codemirror.css' );
	wp_enqueue_script( 'html-widget', plugin_dir_url( __FILE__ ) . 'codemirror/codemirror.js' );

	// Codemirror modes.
	wp_enqueue_script( 'html-widget-mode-xml', plugin_dir_url( __FILE__ ) . 'codemirror/mode/xml/xml.js' );
	wp_enqueue_script( 'html-widget-mode-css', plugin_dir_url( __FILE__ ) . 'codemirror/mode/css/css.js' );
	wp_enqueue_script( 'html-widget-mode-js', plugin_dir_url( __FILE__ ) . 'codemirror/mode/javascript/javascript.js' );
	wp_enqueue_script( 'html-widget-mode-html', plugin_dir_url( __FILE__ ) . 'codemirror/mode/htmlmixed/htmlmixed.js' );

	// Codemirror addons.
	wp_enqueue_script( 'html-widget-autorefresh', plugin_dir_url( __FILE__ ) . 'codemirror/addon/display/autorefresh.js' );
	wp_enqueue_script( 'html-widget-closebrackets', plugin_dir_url( __FILE__ ) . 'codemirror/addon/edit/closebrackets.js' );
	wp_enqueue_script( 'html-widget-closetag', plugin_dir_url( __FILE__ ) . 'codemirror/addon/edit/closetag.js' );

}

/**
 * Core class used to implement a HTML Code widget.
 *
 * @since 0.1.0
 * @see   WP_Widget
 */
class HTML_Widget extends WP_Widget {

	/**
	 * Default instance.
	 *
	 * @since 0.1.0
	 * @var   array
	 */
	protected $default_instance = array(
		'title'   => '',
		'content' => '',
	);

	/**
	 * Sets up a new HTML Code widget instance.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'widget_html',
			'description'                 => __( 'Displays HTML code with syntax highlighting.', 'html-widget' ),
			'customize_selective_refresh' => true,
		);
		$control_ops = array();
		parent::__construct( 'html_widget', __( 'HTML', 'html-widget' ), $widget_ops, $control_ops );
	}

	/**
	 * Outputs the content for the current HTML Code widget instance.
	 *
	 * @since 0.1.0
	 * @param array $args     Default widget arguments.
	 * @param array $instance Settings for the current instance.
	 */
	public function widget( $args, $instance ) {

		$instance = array_merge( $this->default_instance, $instance );
		$content = $instance['content'];

		/**
		 * Filters the content of the HTML Code widget.
		 *
		 * @since 0.1.0
		 * @param string $content  The widget content.
		 * @param array  $instance Settings for the current widget.
		 */
		$content = apply_filters( 'html_widget_content', $content, $instance, $this );
		echo $args['before_widget'];
		echo $content;
		echo $args['after_widget'];
	}

	/**
	 * Handles updating settings for the current widget instance.
	 *
	 * @since  0.1.0
	 * @param  array $new_instance New settings for this instance.
	 * @param  array $old_instance Old settings for this instance.
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array_merge( $this->default_instance, $old_instance );
		if ( current_user_can( 'unfiltered_html' ) ) {
			$instance['content'] = $new_instance['content'];
		} else {
			$instance['content'] = wp_kses_post( $new_instance['content'] );
		}
		return $instance;
	}

	/**
	 * Outputs the HTML Code widget settings form.
	 *
	 * @since  0.1.0
	 * @param  array $instance Current widget instance.
	 * @return void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->default_instance );

		// Print inline script and styles so we can use `$this` object. ?>
		<style>
		.CodeMirror {
			border: 1px solid #e5e5e5;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			var editor = CodeMirror.fromTextArea( document.getElementById('<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>'), {
				height: "350px",
				parserfile: ["parsexml.js", "parsecss.js", "tokenizejavascript.js", "parsejavascript.js", "parsehtmlmixed.js"],
				stylesheet: ["css/xmlcolors.css", "css/jscolors.css", "css/csscolors.css"],
				path: "js/",
				mode: "htmlmixed",
				tabSize: 2,
				autoRefresh: true,
				autoCloseBrackets: true,
				autoCloseTags: true,
				value: document.documentElement.innerHTML,
				lineWrapping: true,
			} );
			editor.on("change", function(editor, change) {
				document.getElementById('<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>').value = editor.getValue();
			});
			editor.refresh();
		} );
		</script>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>" class="screen-reader-text"><?php esc_html_e( 'Content:', 'html-widget' ); ?></label>
			<textarea class="widefat code" rows="16" cols="20" id="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'content' ) ); ?>"><?php echo esc_textarea( $instance['content'] ); ?></textarea>
		</p>

		<?php if ( ! current_user_can( 'unfiltered_html' ) ) : ?>
			<?php
			$probably_unsafe_html = array( 'script', 'iframe', 'form', 'input', 'style' );
			$allowed_html = wp_kses_allowed_html( 'post' );
			$disallowed_html = array_diff( $probably_unsafe_html, array_keys( $allowed_html ) );
			?>
			<?php if ( ! empty( $disallowed_html ) ) : ?>
				<p>
					<?php esc_html_e( 'Some HTML tags are not permitted, including:', 'html-widget' ); ?>
					<code><?php echo join( '</code>, <code>', esc_html( $disallowed_html ) ); ?></code>
				</p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}
}
