<?php

if (!class_exists('WPSettings')) {
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

			$p = (isset($this->settings['prefix']) ? $this->settings['prefix'] : '');

			if (isset($this->settings['prefix']) && $this->settings['prefix']) {
				$this->prefix = $this->settings['prefix'];
			}
			
			foreach ($this->settings['settings'] as $s => &$section) {
				foreach ($section['fields'] as $f => &$field) {
					$this->fields[$p . $f] =& $field;
				}
			}

			add_action('admin_init', array($this, 'init'));
			/** @todo Check if this is working properly
			add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));*/
		}

		/**
		 * Registers the settings. Is called during the admin_init action.
		 * @see __construct()
		 */
		function init() {
			$p = (isset($this->settings['prefix']) ? $this->settings['prefix'] : '');
			foreach ($this->settings['settings'] as $s => &$section) {
				add_settings_section($s, $section['title'],
						array(&$this, 'sectionText'), $this->id);
				foreach ($section['fields'] as $f => &$field) {
					if ($field['type'] == 'internal' ) {
						register_setting($this->id, $p . $f);
						continue;
					} else {
						register_setting($this->id, $p . $f); /// @todo , array(&$this, 'checkValue'));
					}
					add_settings_field($p . $f, $field['title'],
							array(&$this, 'fieldText'), $this->id, $s, array($p . $f, $field));
				}
			}
		}

		function enqueueScripts() {
			$dir = plugin_basename(__DIR__);
			wp_enqueue_script('wpsettings', 
					plugins_url('/' . $dir . '/js/wpsettings.min.js')); // , __DIR__));
			wp_enqueue_style('wpsettings',
					plugins_url('/' . $dir . '/css/wpsettings.min.css')); // , __DIR__));
		}

		/**
		 * Uses the get_option function to retrieve a value of a setting, returning
		 * the default value if not set.
		 * @param $option string Setting to return
		 * @return Value of setting
		 * @retval false If option is not set
		 */
		function get_option($option) {
			$option = $this->prefix . $option;
			
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
			return $this->get_option($option);
		}

		function update_option($option, $new_value) {
			$option = $this->prefix . $option;

			if (isset($this->fields[$option])) {
				update_option($option, $new_value);
			} else {
				return false;
			}

			return true;
		}
		function __set($option, $new_value) {
			$this->update_option($option, $new_value);
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

		/**
		 * Prints the HTML for a given field.
		 * @param $args array An array containing the field id and the field options.
		 * @param $value mixed The current value of the field.
		 * @param $default mixed The default value of the field.
		 */
		function fieldText($args, $value = null, $default = null) {
			echo $this->generateFieldText($args, $value, $default);
		}

		/**
		 * Generates the HTML for a given field.
		 * @param $args array An array containing the field id and the field options.
		 * @param $value mixed The current value of the field.
		 * @param $default mixed The default value of the field.
		 * @return string The generated HTML.
		 */
		protected function generateFieldText($args, $value = null, $default = null) {
			$html = '';

			$f =& $args[0];
			$field =& $args[1];

			// Get value and default
			if (is_null($default)) {
				if (isset($field['default'])) {
					$default = $field['default'];
				} else {
					$default = false;
				}
			}

			if (is_null($value)) {
				$value = get_option($f, $default);
			}

			// Print the input
			switch ($field['type']) {
				case 'boolean':
					$html .= '<input type="checkbox" id="' . $this->id($f) . '" name="' . $this->name($f) . '"'
							. ($value ? ' checked' : '') . '>';
					if (isset($field['label'])) {
						$html .= '<label for="' . $this->id($f) . '">' . $field['label'] . '</label>';
					}
					break;
				case 'dimensions':
					if (!(is_array($value) && isset($value['width'])
							&& isset($value['height']))) {
						if (isset($default) && is_array($default)) {
							if (isset($default['width'])) {
								$value = $default;
							} else {
								$valie = array(
										'width' => $default[0],
										'height' => $default[1]
								);
							}
						} else {
							$value = array(
									'width' => '',
									'height' => ''
							);
						}
					}

					$html .= __('Width', 'wpsettings') . ': <input type="number" '
							. 'id="' . $this->id($f). 'width" name="' . $this->name($f, 'width') . '" '
							. 'value="' . $value['width'] . '" />px  '
							. __('Height', 'wpsettings') . ': <input type="number" '
							. 'id="' . $this->id($f, 'height') . '" name="' . $this->name($f, 'height') . '" '
							. 'value="' . $value['height'] . '" />px';
					break;
				case 'select':
					$html .= '<select id="' . $this->id($f) . '" name="' . $this->name($f) . '">';
					if (isset($field['values'])) {
						foreach ($field['values'] as $v => $val) {
							$html .= '<option value="' . $v . '"'
									. ($v == $value ? ' selected' : '') . '>' . $val
									. '</option>';
						}
					}
					$html .= '</select>';
					break;
				case 'selectMultiple':
					if (isset($field['description'])) {
						$html .= '<p class="description">' . $field['description'] . '</p>';
					}

					if (!(is_array($value) && isset($value['value'])
							&& isset($value['fields']))) {
						$value = array('value' => '', 'fields' => array());
					}

					$id = uniqid();
					$groups = array();

					// Create select
					$html .= '<select id="' . $id . 'select">';
					foreach ($field as $g => &$group) {
						$html .= '<option value="' . $g . '"' 
								. ($value['value'] == $g ? 'checked' : '') . '>'
								. $group['title'] . '</option>';
					}
					$html .= '</select>';

					// Create field groups
					foreach ($field as $g => &$group) {
						$html .= '<div id="' . $id . '-' . $g . '">';
						$this->multipleField(array($f, $g), $group, $value['fields']);
						$html .= '</div>';
					}

					// Javascript initialisation
					$html .= '<script type="text/javascript">wps.select.add(\'' . $id
							. '\');</script>';
					break;
				case 'multiple':
					$this->multipleField($f, $field, $value);

					break;
				case 'folder':
					$html .= WP_CONTENT_DIR . DIRECTORY_SEPARATOR . ' ';
				case 'text':
				case 'number':
				default:
					$html .= '<input type="' . $field['type'] . '" id="' . $this->id($f) . '" name="' . $this->name($f)
							. '" value="' . $value . '" />';
					break;
			}
		
			if ($field['type'] != 'multiple') {
				if (isset($field['description'])) {
					$html .= '<p class="description">' . $field['description'] . '</p>';
				}
			}

			return $html;
		}

		protected function name() {
			$parts = array();

			foreach (func_get_args() as $a) {
				if (is_array($a)) {
					$parts = array_merge($parts, $a);
				} else {
					$parts[] = $a;
				}
			}

			$name = array_shift($parts);

			if ($parts) {
				$name .= '[' . join('][', $parts) . ']';
			}

			return $name;
		}

		protected function id() {
			$parts = array();

			foreach (func_get_args() as $a) {
				if (is_array($a)) {
					$parts = array_merge($parts, $a);
				} else {
					$parts[] = $a;
				}
			}

			return join('-', $parts);
		}

		protected function multipleField($f, &$field, $value) {
			if (isset($field['description'])) {
				echo '<p class="description">' . $field['description'] . '</p>';
			}

			if (!(isset($field['multiple']) && $field['multiple'])) {
				echo '<table class="form-table">';
				echo $this->multipleFieldText($f, $field['fields'], 
						(($value && is_array($value)) ? $value : array()));
				echo '</table>';
			} else {
				if (!($value && is_array($value))) { // If multiple & have values
					$value = array(uniqid() => array());
				}

				$id = uniqid();
				$selected = false;
				if (isset($field['label'])) {
					echo '<select id="' . $id . 'select">';
					foreach ($value as $v => &$val) {
						echo '<option value="' . $v . '"'
								. (!$selected ? ' checked' : '') . '>' . $val[$field['label']]
								. '</option>';
						if (!$selected) {
							$selected = $v;
						}
					}
					echo '</select>';
				}

				echo '<div id="' . $id . '">';
				// @todo Generate labels and divs
				foreach ($value as $v => &$val) {
					echo '<table id="' . $id . '-' . $v . '"'
							. (isset($field['label']) ? ($v != $selected
							? 'style="display: none"' : '') : '')
							. ' class="form-table">';
					echo $this->multipleFieldText(array($f, $id), $field['fields'], 
							(($val && is_array($val)) ? $val : null));
					echo '<tr><td colspan="2"><a class="button" '
							. 'onclick="wps.multiple.del(\'' . $id . '\', \'' . $v . '\');">'
							. __('Delete', 'wpsettings') . '</a>';
					echo '</td></tr>';
					echo '</table>';
				}

				// Generate blank HTML
				$blank = '<table id="' . $id . '-%id%"'
						. ' class="form-table">';
				$blank .= $this->multipleFieldText(array($f, '%id%'), $field['fields']);
				$blank .= '<tr><td colspan="2"><a class="button" '
						. 'onclick="wps.multiple.del(\'' . $id . '\', \'%id%\');">'
						. __('Delete', 'wpsettings') . '</a>';
				$blank .= '</td></tr>';
				$blank .= '</table>';
				$blank = str_replace('\'', '\\\'', $blank);
				//$blank = preg_replace('/([\'"])/', '\\\\$1', $blank);

				
				echo '</div>';
				echo '<a class="button" onclick="wps.multiple.add(\'' . $id . '\');">'
						. __('Add another', 'wpsettings') . '</a>';
				echo '<script type="text/javascript">wps.multiple.init(\'' . $id
						. '\', \'' . $blank . '\');</script>';
			}
		}

		/**
		 * Handles the printing of the fields for a multiple type field.
		 * @param $id string The id to be used as the base id of the fields.
		 * @param $fields array An array containing the fields to be printed.
		 * @param $values array An array containing the current values of the
		 *                fields.
		 */
		protected function multipleFieldText($id, &$fields, &$values = array()) {
			$html = '';
			foreach ($fields as $f => &$field) {
				$fid = $id;
				if (is_array($fid)) {
					$fid[] = $f;
				} else {
					$fid = array($fid, $f);
				}
				$html .= '<tr><th scope="row">' . $field['title'] . '</th><td>';
				$html .= $this->generateFieldText(array($fid, &$field), 
						(isset($values[$f]) ? $values[$f] : false),
						(isset($field['default']) ? $field['default'] : false));
				$html .= '</td></tr>';
			}

			return $html;
		}

		function checkValue() {

		}
	}
}
