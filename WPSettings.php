<?php

/**
 * Class to handle settings
 */
class WPSettings{
	protected $settings;
	protected $id;
	protected $fields;
	protected $prefix = '';

	function __construct(&$settings) {
		$this->settings = $settings;
		$this->id = $settings['id'];

		if (isset($this->settings['prefix']) && $this->settings['prefix']) {
			$this->prefix = $this->settings['prefix'];
		}
		
		foreach ($this->settings['settings'] as $s => &$section) {
			foreach ($section['fields'] as $f => &$field) {
				$this->fields[$f] =& $field;
			}
		}

		add_action('admin_init', array($this, 'init'));
	}

	/**
	 * Registers the settings. Is called during the admin_init action.
	 * @see __construct()
	 */
	function init() {
		foreach ($this->settings['settings'] as $s => &$section) {
			add_settings_section($s, $section['title'],
					array(&$this, 'sectionText'), $this->id);
			foreach ($section['fields'] as $f => &$field) {
				if ($field['type'] == 'internal' ) {
					register_setting($this->id, $f);
					continue;
				} else {
					register_setting($this->id, $f); /// @todo , array(&$this, 'checkValue'));
				}
				add_settings_field($f, $field['title'],
						array(&$this, 'fieldText'), $this->id, $s, array($f, $field));
			}
		}
	}
	
	/**
	 * Uses the get_option function to retrieve a value of a setting, returning
	 * the default value if not set.
	 * @param $option string Setting to return
	 * @return Value of setting
	 * @retval false If option is not set
	 */
	function get_option($option) {
		$default = null;
		if (isset($this->fields[$option])) {
			if (isset($this->fields[$option]['default'])) {
				$default = $this->fields[$option]['default'];
			}
		} else {
			return false;
		}
		
		return get_option($option, $default);
		/** @todo Need to test that the type index is set
		switch($this->fields[$option]['type']) {
			case 'dimension':
				return array(
						get_option($option . '_w', $default[0]),
						get_option($option . '_h', $default[1])
				);
			default:
				return get_option($option, $default);
		}*/
	}
	function __get($option) {
		return $this->get_option($this->prefix . $option);
	}

	function update_option($option, $new_value) {
		if (isset($this->fields[$option])) {
			update_option($option, $new_value);
		} else {
			return false;
		}

		return true;
	}
	function __set($option, $new_value) {
		$this->update_option($this->prefix . $option, $new_value);
	}

	/**
	 * Echos the options form based from the given settings
	 */
	function printOptions() {
		echo '<div class="wrap">';
		if (isset($this->settings['title'])) {
			screen_icon();
			echo '<h1>' . $this->settings['title'] . '</h1>';
		}
		if (isset($this->settings['description'])) {
			if (strstr($this->settings['description'], '<p>') === -1) {
				echo '<p>' . $this->settings['description'] . '</p>';
			} else {
				echo $this->settings['description'];
			}
		}
		echo '<form action="options.php" method="post" id="gHierarchy">';
		submit_button();
		settings_fields($this->id);
		do_settings_sections($this->id);
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	function sectionText($args) {
		if (isset($this->settings['settings'][$args['id']]['description'])) {
			echo '<p>' . $this->settings['settings'][$args['id']]['description'] . '</p>';
		}
	}

	function fieldText($args) {
		$f =& $args[0];
		$field =& $args[1];

		// Print the input
		switch ($field['type']) {
			case 'boolean':
				echo '<input type="checkbox" id="' . $f . '" name="' . $f . '"'
						. (get_option($f, $field['default']) ? 'checked' : '') . ' \>';
				if (isset($field['label'])) {
					echo '<label for="' . $f . '">' . $field['label'] . '</label>';
				}
				break;
			case 'dimensions':
				if (!($dim = get_option($f))) {
					if (isset($field['default'])) {
						if (isset($field['default']['width'])) {
							$dim = $field['default'];
						} else {
							$dim = array(
									'width' => $field['default'][0],
									'height' => $field['default'][1]
							);
						}
					} else {
						$dim = array(
								'width' => '',
								'height' => ''
						);
					}
				}

				echo __('Width', 'ghsettings') . ': <input type="number" '
						. 'id="' . $f . 'width" name="' . $f . '[width]" '
						. 'value="' . $dim['width'] . '" />px  '
						. __('Height', 'ghsettings') . ': <input type="number" '
						. 'id="' . $f . 'height" name="' . $f . '[height]" '
						. 'value="' . $dim['height'] . '" />px';
				break;
			case 'select':
				echo '<select id="' . $f . '" name="' . $f . '">';
				if (isset($field['values'])) {
					$cv = get_option($f, $field['default']);
					foreach ($field['values'] as $v => $value) {
						echo '<option value="' . $v . '"'
								. ($v == $cv ? ' selected' : '') . '>' . $value
								. '</option>';
					}
				}
				echo '</select>';
				break;
			case 'folder':
				echo WP_CONTENT_DIR . DIRECTORY_SEPARATOR . ' ';
			case 'text':
			case 'number':
			default:
				echo '<input type="' . $field['type'] . '" id="' . $f . '" name="' . $f
						. '" value="' . get_option($f, $field['default']) . '" />';
				break;
		}
		
		if (isset($field['description'])) {
			echo '<p class="description">' . $field['description'] . '</p>';
		}
	}

	function checkValue() {

	}
}
