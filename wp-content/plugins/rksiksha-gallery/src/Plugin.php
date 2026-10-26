<?php
declare( strict_types=1 );

namespace RKSiksha\Gallery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;
	private bool $booted = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		if ( is_admin() ) {
			( new AdminPage() )->register();
		}
	}

	private function __construct() {}
	private function __clone() {}
}
