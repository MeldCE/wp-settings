wp-settings
===========

An easy way to have your settings managed for you in Wordpress.

This class can draw a Options page for setting your settings and provides get
and set functions (built on top of the Wordpress get and set functions) for
setting and getting your settings. You simply provide an array containing your
settings and away you go.


You can get or set the value of any option as if it was a variable of the
class. For example:

```
$settings = WPSettings($options);
// Get option value
echo $settings->my_option;
// Set option value
$settings->my_option = 'new value';
```

If the option `prefix` is set, the prefix will be prefixed to the start of the
variable name used when getting or setting this way, e.g. if `prefix` is set to
`gh_`, the above example would get and set the option `gh_my_option`.

## Use
To use, simply include the WPSettings.php in your PHP script and call as in the
example above.

You will also need to ensure that the javascript file `js/wpsetting.inc.js`
is enqueued in the admin section.

## Specifying Settings

The settings are specified in an array, `$options` that is passed to WPSettings
on construction. The `$options` array contains the WPSettings options and an
associative array containing sections, each one of those containing an 
associative array containing the settings in that section.

### WPSettings Options (`$options`)

The following options are available (*bold* options are required):
- `'title'` - *The title of your options page*.
- `'id'` - *An id used as the sections id and page id*.
- `'prefix'` - If set, it will be added to the start of each setting
  name.
- `'settings'` - *The associative array containing the settings broken into
  sections*.

### Sections (`$options['settings']`)

`$options['settings']` must contain an associative array of the different
sections of settings. Each section should have a unique array key and be an
associative array containing the following options (*bold* options are
required):
- `'title'` - *The title of the settings section*.
- `'fields'` - *The associative array containing the settings in this
  section*.

### Settings (`$options['settings'][$section]['fields']`)
`$options['settings'][$section]['fields']` must contain an associative array of
the settings in the section. Each setting should have a unique array key (that
unique across *all* settings) and be an associative array containing the
following options (*bold* options are required):
- `'title'` - *The title of the setting*.
- `'description'` - A description of the setting. Will be placed below the
  field.
- `'type'` - The type of setting. Can be either:
	- `'internal'` - Only for internal use - won't be added to the edit options
	  screen.
  - `'folder'` - A folder.
	- `'text'` - A text field.
	- `'number'` - A number.
	- `'boolean'` - True or false (a checkbox).
	- `'select'` - One (**or more**) of the given options (see below).
	- `'dimensions'` - A width and a height.
	- `'multiple'` - a group of fields.
	- `'selectMultiple'` - a group of fields that will depend on a value
	  selected
- `'default'` - The default value of the settings
  - If `'type'` is `'boolean'`, `'default'` must be either `true` or `false`
  - If `'type'` is `'select'`, `'default'` must be a key of the `'values'`
	  array.
  - If `'type'` is `'dimensions'`, `'default'` must be either an associative
	  array (`array('width' => w, 'height' => h)`) or an array
		(`array(w, h)`) containing the width and height.
  - If `'type'` is `'multiple'`, `'default'` will be ignored.
  - If `'type'` is `'number'`, `'default'` must be a number
  - Otherwise, `'default'` should be a string
- '`'values'` - If `'type'` is `'select'`, *this must be an array containing
  possible values for the settings. If the array is associative, the keys will
	be used as the option values and the values used as the option labels*.
- `'base'` - If `'type'` is `'folder'`, should contain a relative folder path
  to a folder inside the sites root folder.
- `'multiple'` - `(true|false)` If `'type'` is `'multiple'` and this is set to
  true, multiple groups of value will be able to be stored.
- `'fields'` - If `'type'` is `'multiple'`, *must contain an array like
  `$options['settings'][$section]['fields']`*. If `'type'` is
	`'selectMultiple'`, *must contain an array like `$options['settings']`. The
	fields in `$options['settings'][$value]['fields']` will be displayed if
	`$value` is selected.
- '`label`' - If `'type'` is `'multiple'` and `'multiple'` is set to true,
  if this is set, one group will be visible at one time and the user will
  be able to switch between them using a select box. The value *must a key of
	one of the fields in `'fields'` to be used as the label to groups of fields*.

### `$options` Array Example

Below is an example (from
[wp-gallery-hierarchy](https://github.com/weldstudio/wp-gallery-hierarchy)) of
what an settings array should look like:

```
$options = array(
    'title' => __('Gallery Hierarchy Options', 'gallery_hierarchy'), // The title of your options page
    'id' => 'gHOptions', // The section and other ids for the settings
		'prefix' => 'gh_', // If set, it will prefixed to any option accessed or set as a variable
    'settings' => array( // The array containing the settings sections
		    'gHFolders' => array( // A settings section
				    'title' => __('Folder Options', 'gallery_hierarchy'), // The title of the settings section
    				'fields' => array( // The fields in the settings section
		    				'folder' => array( // The id of the setting
				    				'title' => __('Image Folder', 'gallery_hierarchy'), // The title of the setting
						    		'description' => __('This should be a relative path ' // The description of the setting
    										. 'inside of wp-content to a folder containing your '
		    								. 'images.', 'gallery_hierarchy'),
				    				'type' => 'folder', // The type of setting, can be folder, text, number, boolean, select or dimensions
						    		'default' => 'gHImages' // The default value for the setting
						    		'values' => array('value1' => 'Value 1', 'value2' => 'Value 2') // If this setting was of type select, this would contain the options to select from
	    					),
			    			'cache_folder' => array(
					    			'title' => __('Cache Image Folder', 'gallery_hierarchy'),
							    	'description' => __('This should be a relative path '
									    	. 'inside of wp-content to a folder that will be '
    										. 'used to store images created by Gallery '
		    								. 'Hierarchy, including thumbnails.',
				    						'gallery_hierarchy'),
						    		'type' => 'folder',
		    						'default' => 'gHCache'
    						),
		    		)
    		),
		    'gHThumbnails' => array(
				    'title' => __('Thumbnail Options', 'gallery_hierarchy'),
    				'fields' => array(
		    				'thumbnail_size' => array(
				    				'title' => __('Thumbnail Dimensions',
						    				'gallery_hierarchy'),
								    'description' => __('Size to make the thumbnails.',
    										'gallery_hierarchy'),
		    						'type' => 'dimensions',
				    				'default' => array(200, 150) // The default width, height for a setting of type dimension
						    ),
    						'crop_thumbnails' => array(
		    						'title' => __('Crop Thumbnails', 'gallery_hierarchy'),
				    				'description' => __('If this option is selected, the '
						    				. 'image will be cropped so that if fills the entire '
								    		. 'thumbnail.', 'gallery_hierarchy'),
    								'type' => 'boolean',
		    						'default' => false
				    		)
		    		)
      	),
    )
);
```
