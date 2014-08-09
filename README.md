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

## Settings Array

Below is an example (from wp-gallery-hierarchy) of what an settings array should look like:
```
$options = array(
    'title' => __('Gallery Hierarchy Options', 'gallery_hierarchy'), // The title of your options page
    'id' => 'gHOptions', // The section and other ids for the settings
		'prefix' => 'gh_', // If set, it will prefixed to any option accessed or set as a variable
    'settings' => array( // The array containing the settings sections
		    'gHFolders' => array( // A settings section
				    'title' => __('Folder Options', 'gallery_hierarchy'), // The title of the settings section
    				'fields' => array( // The fields in the settings section
		    				'gh_folder' => array( // The id of the setting
				    				'title' => __('Image Folder', 'gallery_hierarchy'), // The title of the setting
						    		'description' => __('This should be a relative path ' // The description of the setting
    										. 'inside of wp-content to a folder containing your '
		    								. 'images.', 'gallery_hierarchy'),
				    				'type' => 'folder', // The type of setting, can be folder, text, number, boolean, select or dimensions
						    		'default' => 'gHImages' // The default value for the setting
						    		'values' => array('value1' => 'Value 1', 'value2' => 'Value 2') // If this setting was of type select, this would contain the options to select from
	    					),
			    			'gh_cache_folder' => array(
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
		    				'gh_thumbnail_size' => array(
				    				'title' => __('Thumbnail Dimensions',
						    				'gallery_hierarchy'),
								    'description' => __('Size to make the thumbnails.',
    										'gallery_hierarchy'),
		    						'type' => 'dimensions',
				    				'default' => array(200, 150) // The default width, height for a setting of type dimension
						    ),
    						'gh_crop_thumbnails' => array(
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
'''
