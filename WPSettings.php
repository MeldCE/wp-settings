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
			$this->settings =& $settings;
			$this->id = $settings['id'];

			if (isset($this->settings['prefix']) && $this->settings['prefix']) {
				$this->prefix = $this->settings['prefix'];
				$p = $this->settings['prefix'];
			} else {
				$p = '';
			}
			
			foreach ($this->settings['settings'] as $s => &$section) {
				foreach ($section['fields'] as $f => &$field) {
					$this->fields[$f] =& $field;
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
					/** @todo Add error alert
					if (!isset($field['type'])) {
						echo 'Type not set for ' . $f . ' ' . print_r($field, 1);
					}
					*/
					if ($field['type'] == 'internal' ) {
						register_setting($this->id . '_internal', $p . $f);
						continue;
					} else {
						if (isset($field['check'])) {
							$sanitize = $field['check'];
						} else {
							switch($field['type']) {
								case 'boolean':
									$sanitize = array(&$this, 'sanitizeBoolean');
									break;
								case 'dimensions':
									$sanitize = array(&$this, 'sanitizeDimension');
									break;
								/*case 'select':
									$sanitize = array(&$this, 'sanitizeSelect');
									break;
								case 'selectMultiple':
									$sanitize = array(&$this, 'sanitize');
									break;
								case 'multiple':
									$sanitize = array(&$this, 'sanitize');
									break;
								case 'formatted':
									$sanitize = array(&$this, 'sanitize');
									break;
								case 'folder':
									$sanitize = array(&$this, 'sanitize');
									break;
								case '':
									$sanitize = array(&$this, 'sanitize');
									break;*/
								case 'number':
									$sanitize = 'intval';
									break;
								default:
									$sanitize = '';
							}
						}
						register_setting($this->id, $p . $f, $sanitize);
					}
					add_settings_field($p . $f, $field['title'],
							array(&$this, 'fieldText'), $this->id, $s, array($p . $f, $field));
				}
			}

			add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
		}

		function enqueueScripts() {
			$dir = plugin_basename(__DIR__);
			wp_enqueue_script('wpsettings', 
					plugins_url('/' . $dir . '/js/wpsettings.min.js')); // , __DIR__));
			wp_enqueue_style('wpsettings',
					plugins_url('/' . $dir . '/css/wpsettings.min.css')); // , __DIR__));
		}

		protected function formatDimension($dimension) {
			if (is_array($dimension)) {
				if (isset($dimension['width']) && isset($dimension)) {
					return $dimension;
				}

				if (isset($dimension[0]) && isset($dimension[1])) {
					return array(
						'width' => $dimension[0],
						'height' => $dimension[1]
					);
				}
			}

			return null;
		}

		/**
		 * Uses the get_option function to retrieve a value of a setting, returning
		 * the default value if not set.
		 * @param $option string Setting to return
		 * @return Value of setting
		 * @retval false If option is not set
		 */
		function get_option($option) {
			$pOption = $this->prefix . $option;
		
			$default = null;
			if (!isset($this->fields[$option])) {
				return null;
			}

			if (isset($this->fields[$option]['default'])) {
				$default = $this->fields[$option]['default'];
			}
		
			// Run getter function
			if (isset($field['get'])) {
				if (is_null(($value = call_user_func($field['get'], $field)))) {
					return $default;
				}
				return value;
			}
			
			$type = (isset($this->fields[$option]['type']) ? $this->fields[$option]['type'] : '');

			switch($type) {
				case 'dimensions':
					return $this->formatDimension(get_option($pOption, $default));
				default:
					return get_option($pOption, $default);
			}
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
			if (isset($this->settings['useTabs']) && $this->settings['useTabs']) {
				foreach ($this->settings['settings'] as $s => &$section) {
					echo '<a class="wp-settings-tab" id="' . $this->id . '-' . $s
							. '-tab" onclick="wps.tabs.open(\'' . $this->id
							. '\', \'' . $s . '\')">' . $section['title']
							. '</a>';
				}
			}
			settings_fields($this->id);
			$this->do_settings_sections($this->id);
			submit_button();
			echo '<script>wps.tabs.init(\'' . $this->id . '\''
					. (isset($_REQUEST['section']) ? ', \'' 
					. $_REQUEST['section'] . '\'' : '') . ');</script>';
			echo '</form>';
			echo '</div>';
		}

		protected function do_settings_sections($page) {
			global $wp_settings_sections, $wp_settings_fields;

			if (!isset($wp_settings_sections[$page]) || !is_array($wp_settings_sections[$page])) {
				return;
			}

			foreach ($wp_settings_sections[$page] as $s => &$section) {
				if (isset($this->settings['useTabs']) && $this->settings['useTabs']) {
					echo '<div class="' . $this->id . '-section wp-settings-section" id="' . $this->id . '-' . $s . '">';
				}

				if ( $section['title'] )
						echo "<h3>{$section['title']}</h3>\n";

				if ( $section['callback'] )
						call_user_func( $section['callback'], $section );

				if ( ! isset( $wp_settings_fields )
						|| !isset( $wp_settings_fields[$page] )
						|| !isset( $wp_settings_fields[$page][$section['id']] ) )
					continue;
				
				echo '<table class="form-table">';
				do_settings_fields( $page, $section['id'] );
				echo '</table>';
				
				if (isset($this->settings['useTabs']) && $this->settings['useTabs']) {
					echo '</div>';
				}
			}
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
		 *
		 * @todo Implement value verification.
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
		
			if (isset($field['get'])) {
				$value = call_user_func($field['get'], $field);
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
								$value = array(
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
					if (isset($field['multiple']) && $field['multiple']) {
						$multiple = true;
					} else {
						$multiple = false;
					}

					if ($multiple && !is_array($value)) {
						if ($value) {
							$value = array($value);
						} else {
							$value = array();
						}
					}

					$html .= '<select id="' . $this->id($f) . '" name="'
							. $this->name($f) . ($multiple ? '[]' : '') . '"'
							. ((isset($field['multiple']) 
							&& $field['multiple']) ? ' multiple' : '') . '>';
					if (isset($field['values'])) {
						foreach ($field['values'] as $v => $val) {
							$html .= '<option value="' . $v . '"'
									. ((($multiple && in_array($v, $value))
									|| (!$multiple && $v == $value)) ? ' selected' : '') . '>'
									. $val . '</option>';
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
					$html .= $this->multipleField($f, $field, $value);

					break;
				case 'formatted':
					$html .= wp_editor($value, $this->id($f),
							array( 'textarea_name' => $this->name($f)));

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

		protected function addPart($currentPart, $newPart) {
			if (!is_array($currentPart)) {
				$currentPart = array($currentPart);
			}

			if (is_array($newPart)) {
				return array_merge($currentPart, $newPart);
			} else {
				$currentPart[] = $newPart;
				return $currentPart;
			}
		}

		protected function multipleField($f, &$field, $value) {
			$html = '';

			if (isset($field['description'])) {
				$html .= '<p class="description">' . $field['description'] . '</p>';
			}

			if (!(isset($field['multiple']) && $field['multiple'])) {
				$html .= '<table class="form-table">';
				$html .= $this->multipleFieldText($f, $field['fields'], 
						(($value && is_array($value)) ? $value : array()));
				$html .= '</table>';
			} else {
				if (!($value && is_array($value))) { // If multiple & have values
					$value = array();
				}

				$id = uniqid();
				$selected = false;
				if (isset($field['label'])) {
					$html .= '<select id="' . $id . 'select">';
					foreach ($value as $v => &$val) {
						$html .= '<option value="' . $v . '"'
								. (!$selected ? ' checked' : '') . '>' . $val[$field['label']]
								. '</option>';
						if (!$selected) {
							$selected = $v;
						}
					}
					$html .= '</select>';
				}

				$html .= '<div id="' . $id . '">';
				// @todo Generate labels and divs
				foreach ($value as $v => &$val) {
					$html .= '<table id="' . $id . '-' . $v . '"'
							. (isset($field['label']) ? ($v != $selected
							? 'style="display: none"' : '') : '')
							. ' class="form-table">';
					$html .= $this->multipleFieldText($this->addPart($f, $v), $field['fields'], 
							(($val && is_array($val)) ? $val : null));
					$html .= '<tr><td colspan="2"><a class="button" '
							. 'onclick="wps.multiple.del(\'' . $id . '\', \'' . $v . '\');">'
							. __('Delete', 'wpsettings') . '</a>';
					$html .= '</td></tr>';
					$html .= '</table>';
				}
				$html .= '</div>';

				// Generate blank HTML
				$blank = '<table id="' . $id . '-%id%"'
						. ' class="form-table">';
				$blank .= $this->multipleFieldText($this->addPart($f, '%id%'), $field['fields']);
				$blank .= '<tr><td colspan="2"><a class="button" '
						. 'onclick="wps.multiple.del(\'' . $id . '\', \'%id%\');">'
						. __('Delete', 'wpsettings') . '</a>';
				$blank .= '</td></tr>';
				$blank .= '</table>';
				$blank = str_replace('\\', '\\\\', $blank);
				$blank = str_replace('\'', '\\\'', $blank);
				$blank = str_replace('</script>', '</scr\' + \'ipt>', $blank);
				$blank = str_replace('<script type="text/javascript">', '<scr\' + \'ipt type="text/javascript">', $blank);
				
				$html .= '</div>';
				$html .= '<a class="button" onclick="wps.multiple.add(\'' . $id . '\');">'
						. __('Add another', 'wpsettings') . '</a>';
				$html .= '<script type="text/javascript">wps.multiple.init(\'' . $id
						. '\', \'' . $blank . '\');</script>';
			}
			
			return $html;
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

		function sanitizeBoolean($value) {
			if ($value) {
				return 1;
			} else {
				return 0;
			}
		}

		function sanitizeDimension($value) {
			if (isset($value['width']) && isset($value['height'])) {
				$value['width'] = intval($value['width']) || 0;
				$value['height'] = intval($value['height']) || 0;
			}

			return null;
		}
	}
}
