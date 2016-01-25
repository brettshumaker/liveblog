<?php

/**
 * The class responsible for adding WebSocket support
 * if the constant LIVEBLOG_USE_SOCKETIO is true and
 * requirements are met.
 *
 * PHP sends messages to a Socket.io server via a Redis
 * server using socket.io-php-emitter.
 */
class WPCOM_Liveblog_Socketio {

	/**
	 * @var SocketIO\Emitter
	 */
	private static $emitter;

	/**
	 * @var string Socket.io server URL
	 */
	private static $url;

	/**
	 * Load everything that is necessary to use WebSocket
	 *
	 * @return void
	 */
	public static function load() {
		// load socket.io-php-emitter
		require( dirname( __FILE__ ) . '/../vendor/autoload.php' );

		self::load_settings();

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		self::$emitter = new SocketIO\Emitter();
	}

	/**
	 * Load Socket.io settings from PHP constants or use
	 * default values if constants are not defined.
	 *
	 * @return void
	 */
	public static function load_settings() {
		if ( defined( 'LIVEBLOG_SOCKETIO_URL' ) ) {
			self::$url = LIVEBLOG_SOCKETIO_URL;
		} else {
			$parsed_url = parse_url( site_url() );
			self::$url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . ':3000';
		}
	}

	/**
	 * Enqueue the necessary CSS and JS that the WebSocket support needs to function.
	 * Nothing is enqueued if not viewing a Liveblog post.
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		if ( ! WPCOM_Liveblog_Socketio_Loader::should_use_socketio() ) {
			return;
		}

		$handle = 'liveblog-socket.io';

		wp_enqueue_script( 'socket.io', plugins_url( '../js/socket.io.min.js', __FILE__ ), array(), '1.4.4', true );
		wp_enqueue_script(
			$handle,
			plugins_url( '../js/liveblog-socket.io.js', __FILE__ ),
			array( 'jquery', 'socket.io', WPCOM_Liveblog::key ),
			WPCOM_Liveblog::version,
			true
		);

		wp_localize_script( $handle, 'liveblog_socketio_settings',
			apply_filters( 'liveblog_socketio_settings',
				array(
					'url' => self::$url,
				)
			)
		);
	}

	/**
	 * Emits a message to all connected socket.io clients
	 * via Redis.
	 *
	 * @param string $name the name of the message
	 * @param string|array $data the content of the message
	 * @return void
	 */
	public static function emit( $name, $data ) {
		self::$emitter->json->emit( $name, $data );
		exit;
	}
}
