<?php
/**
 * Exam plugin bootstrap.
 *
 * @package RKSiksha\Exam
 */

declare( strict_types=1 );

namespace RKSiksha\Exam;

use RKSiksha\Core\Admin\QuizPage;
use RKSiksha\Core\Modules\Quiz;
use RKSiksha\Exam\Modules\CertificateIssuer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	private bool $booted = false;

	private function __construct() {}

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

		if ( ! defined( 'RKS_VERSION' ) || ! class_exists( '\RKSiksha\Core\Support\DB' ) ) {
			add_action( 'admin_notices', [ $this, 'render_missing_core_notice' ] );
			return;
		}

		Installer::maybe_install();
		Roles::install();

		( new Quiz() )->register();
		( new CertificateIssuer() )->register();

		if ( is_admin() ) {
			( new QuizPage() )->register();
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		}

		do_action( 'rks_exam_plugin_booted', $this );
	}

	public function render_missing_core_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>Rishikul Shiksha Exam</strong> requires the Rishikul Shiksha Core plugin to be active.</p></div>';
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( strpos( $hook, 'rks-exams' ) === false ) {
			return;
		}

		wp_enqueue_style( 'rks-exam-admin', RKS_EXAM_PLUGIN_URL . 'assets/css/admin-exam.css', [ 'rks-admin' ], RKS_EXAM_VERSION );
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \RuntimeException( 'Cannot unserialize singleton' );
	}
}
