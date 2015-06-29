<?php namespace PrimeTime\WordPress\Support;


class Hook
{
	const DEPRECATED_HANDLER = '';
	protected static $deprecated_tags = [ ];

	public static function deprecate( $tag )
	{
		if ( empty( self::$deprecated_tags[ $tag ] ) ) {
			$handler = __NAMESPACE__ . '\\' . static::DEPRECATED_HANDLER;
			self::$deprecated_tags[ $tag ] = new $handler( $tag );
		}

		return self::$deprecated_tags[ $tag ];
	}
}

class Filter extends Hook
{
	const DEPRECATED_HANDLER = 'Deprecated_Filter';
}

class Action extends Hook
{
	const DEPRECATED_HANDLER = 'Deprecated_Action';
}


class Deprecated_Filter
{
	const TYPE = 'filter';
	const FUNC = 'add_filter';

	/**
	 * The deprecated tag
	 * @var string
	 */
	protected $tag;

	/**
	 * The tag to use instead
	 * @var string
	 */
	protected $alternative;

	/**
	 * The version the tag was deprecated
	 * @var string
	 */
	protected $since;

	public function __construct( $tag )
	{
		$this->tag = $tag;

		$this->set_listener();
	}

	/**
	 * Sets the alternative tag which should be used instead
	 *
	 * @param $tag
	 *
	 * @return $this
	 */
	public function instead( $tag )
	{
		$this->alternative = $tag;

		return $this;
	}

	/**
	 * Sets the version the deprecation started
	 *
	 * @param $version
	 *
	 * @return $this
	 */
	public function since( $version )
	{
		$this->since = $version;

		return $this;
	}

	/**
	 * Get the message which is used to inform the user if the tag is used
	 *
	 * @return string
	 */
	public function get_message()
	{
		$message = "The ". static::TYPE ." tag '$this->tag' is deprecated.";

		if ( $this->alternative ) {
			$message .= " Use '$this->alternative' instead.";
		}

		return $message;
	}

	/**
	 * Setup a callback to listen for usage
	 */
	protected function set_listener()
	{
		// add_action is a wrapper for add_filter
		add_filter($this->tag, [$this, 'listener_callback'], 0);
	}

	/**
	 * The deprecated tag has been used
	 * @return mixed
	 */
	public function listener_callback()
	{
		// remove this callback to not interfere with our check
		remove_filter($this->tag, [$this, 'listener_callback'], 0);

		// check for any registered callbacks on this
		if ( has_filter($this->tag) ) {
			$this->report_usage();
		}

		// reset the listener on a later priority to avoid an infinite loop
		add_filter($this->tag, [$this, 'reset_listener']);

		// return the first argument to not break a filter
		return $this->get_return( func_get_args() );
	}

	/**
	 * Reset the listener callback to listen on subsequent uses
	 * @return mixed
	 */
	public function reset_listener()
	{
		remove_filter($this->tag, [$this, 'reset_listener']);

		$this->set_listener();

		return $this->get_return( func_get_args() );
	}

	/**
	 * The deprecated tag was used, issue a warning
	 */
	protected function report_usage()
	{
		_deprecated_argument(static::FUNC, $this->since, $this->get_message());
	}

	/**
	 * @param $args
	 *
	 * @return mixed
	 */
	protected function get_return( $args )
	{
		return reset( $args );
	}
}

class Deprecated_Action extends Deprecated_Filter
{
	const TYPE = 'action';
	const FUNC = 'add_action';
}

