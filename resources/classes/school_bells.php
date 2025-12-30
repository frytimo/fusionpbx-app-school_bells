<?php


class school_bells {
	/**
	 * declare constant variables
	 */
	const app_name = 'school_bells';
	const app_uuid = 'fd29e39c-c936-f5fc-8e2b-611681b266b5';

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Settings object set in the constructor. Must be a settings object and cannot be null.
	 * @var settings Settings Object
	 */
	private $settings;

	/**
	 * User UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
	 * @var string
	 */
	private $user_uuid;

	/**
	 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
	 * @var string
	 */
	private $domain_uuid;

	/**
	 * declare private variables
	 */
	private $permission_prefix;
	private $list_page;
	private $table;
	private $uuid_prefix;
	private $toggle_field;
	private $toggle_values;

	public function __construct($settings_array = []) {
		//set objects
		$config = $setting_array['config'] ?? config::load();
		$this->database = $setting_array['database'] ?? database::new(['config' => $config]);
		$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);

		//set private variables
		$this->permission_prefix = 'school_bell_';
		$this->list_page = 'school_bells.php';
		$this->table = 'school_bells';
		$this->uuid_prefix = 'school_bell_';
		$this->toggle_field = 'school_bell_enabled';
		$this->toggle_values = ['true','false'];
	}

	/**
	 * Deletes one or multiple records from the access controls table.
	 *
	 * @param array $records An array of record IDs to delete, where each ID is an associative array
	 *                       containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                       whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function delete($records) {
		if (permission_exists($this->permission_prefix.'delete')) {

			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//validate the token
				$token = new token;
				if (!$token->validate($_SERVER['PHP_SELF'])) {
					message::add($text['message-invalid_token'],'negative');
					header('Location: '.$this->list_page);
					exit;
				}

			//delete multiple records
				if (is_array($records) && @sizeof($records) != 0) {

					//build the delete array
						foreach ($records as $x => $record) {
							if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
								$array[$this->table][$x]['domain_uuid'] = $this->domain_uuid;
							}
						}

					//delete the checked rows
						if (is_array($array) && @sizeof($array) != 0) {

							//execute delete
								$this->database->delete($array);
								unset($array);

							//clear the destinations session array
								if (isset($_SESSION['destinations']['array'])) {
									unset($_SESSION['destinations']['array']);
								}

							//set message
								message::add($text['message-delete']);
						}
						unset($records);
				}
		}
	}

	/**
	 * Toggles the state of the specified records.
	 *
	 * @param array $records An array of record IDs to delete, where each ID is an associative array
	 *                       containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                       whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function toggle($records) {
		if (permission_exists($this->permission_prefix.'edit')) {

			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//validate the token
				$token = new token;
				if (!$token->validate($_SERVER['PHP_SELF'])) {
					message::add($text['message-invalid_token'],'negative');
					header('Location: '.$this->list_page);
					exit;
				}

			//toggle the checked records
				if (is_array($records) && @sizeof($records) != 0) {

					//get current toggle state
						foreach ($records as $x => $record) {
							if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
								$uuids[] = "'".$record['uuid']."'";
							}
						}
						if (is_array($uuids) && @sizeof($uuids) != 0) {
							$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
							$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
							$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
							$parameters['domain_uuid'] = $this->domain_uuid;
							$rows = $this->database->select($sql, $parameters, 'all');
							if (is_array($rows) && @sizeof($rows) != 0) {
								foreach ($rows as $row) {
									$states[$row['uuid']] = $row['toggle'];
								}
							}
							unset($sql, $parameters, $rows, $row);
						}

					//build update array
						$x = 0;
						foreach ($states as $uuid => $state) {
							$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
							$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
							$x++;
						}

					//save the changes
						if (is_array($array) && @sizeof($array) != 0) {

							//save the array

								$this->database->save($array);
								unset($array);

							//clear the destinations session array
								if (isset($_SESSION['destinations']['array'])) {
									unset($_SESSION['destinations']['array']);
								}

							//set message
								message::add($text['message-toggle']);
						}
						unset($records, $states);
				}

		}
	}

	/**
	 * Copies one or more records
	 * @param array $records An array of record IDs to delete, where each ID is an associative array
	 *                       containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                       whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function copy($records) {
		if (permission_exists($this->permission_prefix.'add')) {

			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//validate the token
				$token = new token;
				if (!$token->validate($_SERVER['PHP_SELF'])) {
					message::add($text['message-invalid_token'],'negative');
					header('Location: '.$this->list_page);
					exit;
				}

			//copy the checked records
				if (is_array($records) && @sizeof($records) != 0) {

					//get checked records
						foreach ($records as $x => $record) {
							if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
								$uuids[] = "'".$record['uuid']."'";
							}
						}

					//create insert array from existing data
						if (is_array($uuids) && @sizeof($uuids) != 0) {
							$sql = "select * from v_".$this->table." ";
							$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
							$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
							$parameters['domain_uuid'] = $this->domain_uuid;
							$rows = $this->database->select($sql, $parameters, 'all');
							if (is_array($rows) && @sizeof($rows) != 0) {
								foreach ($rows as $x => $row) {

									//convert boolean values to a string
										foreach($row as $key => $value) {
											if (gettype($value) == 'boolean') {
												$value = $value ? 'true' : 'false';
												$row[$key] = $value;
											}
										}

									//copy data
										$array[$this->table][$x] = $row;

									//overwrite
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = uuid();
										$array[$this->table][$x]['school_bell_description'] = trim($row['school_bell_description'].' ('.$text['label-copy'].')');

								}
							}
							unset($sql, $parameters, $rows, $row);
						}

					//save the changes and set the message
						if (is_array($array) && @sizeof($array) != 0) {

							//save the array

								$this->database->save($array);
								unset($array);

							//set message
								message::add($text['message-copy']);

						}
						unset($records);
				}

		}
	}

}
