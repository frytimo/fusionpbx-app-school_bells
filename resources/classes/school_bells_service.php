<?php

class school_bells_service extends service {
	/**
	 * Database object
	 *
	 * @var database
	 */
	private $database;

	/**
	 * Settings object
	 *
	 * @var settings
	 */
	private $settings;

	/**
	 * Event Socket
	 *
	 * @var event_socket
	 */
	private $esl;

	/**
	 * Set the command line options for this service
	 *
	 * @return void
	 */
	public static function set_command_options(): void {
		parent::append_command_option(command_option::new()
			->long_option('version')
			->short_option('v')
			->description('Display the service version')
			->callback('display_version')
		);
	}

	/**
	 * Display this version
	 *
	 * @return void
	 */
	public static function display_version(): void {
		echo "School Bells Service Version 1.0.0\n";
	}

	/**
	 * Get the service name
	 *
	 * @return string Service name
	 */
	public static function get_service_name(): string {
		return self::class;
	}

	/**
	 * Reload settings when the SIGHUP or SIGUSR1 signal is received
	 *
	 * @return void
	 */
	protected function reload_settings(): void {
		parent::$config->read();

		// Create a new database connection
		$this->database = new database(['config' => parent::$config]);

		// Create a new settings object
		$this->settings = new settings(['database' => $this->database]);

		$this->connect_event_socket();
	}

	/**
	 * The main service loop
	 *
	 * @return int Exit code
	 */
	public function run(): int {
		// Connect to the database and load settings
		$this->reload_settings();

		$sql = 'SELECT v_domains.domain_name AS context'
			. ', v_school_bells.domain_uuid AS domain_uuid'
			. ', school_bell_leg_a_data AS extension'
			. ', school_bell_leg_b_type AS full_path'
			. ', school_bell_ring_timeout AS ring_timeout'
			. ', school_bell_min AS min'
			. ', school_bell_hour AS hour'
			. ', school_bell_dom AS dom'
			. ', school_bell_mon AS mon'
			. ', school_bell_dow AS dow'
			. ', school_bell_timezone AS timezone'
			. ' FROM'
			. '     v_school_bells'
			. ' JOIN'
			. '     v_domains ON v_domains.domain_uuid = v_school_bells.domain_uuid'
			. ' WHERE'
			. '     school_bell_min <= :current_minute'
			. ' OR'
			. '     school_bell_min = -1';
		$parameters = [];
		while ($this->is_running()) {

			if (! $this->esl->is_connected()) {
				$this->connect_event_socket();
			}

			if (!$this->database->is_connected()) {
				$this->database->connect();
			}

			$parameters['current_minute'] = (int) date('i');
			// Fetch school bell data from the database
			$bells = $this->database->select($sql, $parameters, 'all');

			$current_time = new DateTime('now', new DateTimeZone('UTC'));

			foreach ($bells as $bell) {
				// Check if the bell should ring at the current time
				$bell_time = new DateTime($bell['school_bell_time'], new DateTimeZone($bell['school_bell_timezone']));
				if ($current_time->format('H:i') === $bell_time->format('H:i')) {
					// Trigger the bell
					$this->trigger_bell($bell);
				}
			}

			// Sleep for a minute before checking again
			sleep(60);
		}
		return 0;  // Success
	}

	private function trigger_bell($bell) {
		$dial_string = $bell['bell_dial_string'];
		if ($dial_string) {
			$cmd = "originate {$dial_string} &playback({$bell['bell_sound']})";
			$this->esl->request($cmd);
		}
	}

	private function connect_event_socket() {
		$host     = parent::$config->get('switch.event_socket.host', 'host', '127.0.0.1');
		$port     = parent::$config->get('switch.event_socket.port', 'port', '8021');
		$password = parent::$config->get('switch.event_socket.password', 'password', 'ClueCon');

		$this->esl = event_socket::create($host, $port, $password);
		while (!$this->esl->is_connected()) {
			// Connect to the event socket
			$this->esl->connect($host, $port, $password);
			sleep(1);
 		}
	}
}
