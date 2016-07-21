<?php
/*
Plugin Name: F13 Weather Widget
Plugin URI: http://f13dev.com/wordpress-plugin-weather-widget/
Description: Add a widget to your blog to display the weather in your location.
Version: 1.0
Author: Jim Valentine - f13dev
Author URI: http://f13dev.com
Text Domain: f13-weather-widget
License: GPLv3
*/

/*
Copyright 2016 James Valentine - f13dev (jv@f13dev.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Register the CSS
add_action( 'wp_enqueue_scripts', 'f13_weather_stylesheet');
// Register the widget
add_action('widgets_init', create_function('', 'return register_widget("f13_weather_widget");'));

function f13_weather_stylesheet()
{
	wp_register_style( 'f13weather-style', plugins_url('wp-weather-widget.css', __FILE__));
	wp_enqueue_style( 'f13weather-style' );
	wp_register_style( 'fontawesome-style', plugins_url('font-awesome.css', __FILE__));
	wp_enqueue_style( 'fontawesome-style' );
}

class f13_weather_widget extends WP_Widget
{
	/** Basic Widget Settings */
	const WIDGET_NAME = "F13Dev Weather Widget";
	const WIDGET_DESCRIPTION = "Add a widget to your blog to display the weather in your location.";

	var $textdomain;
	var $fields;

    function __construct()
	{
		$this->textdomain = strtolower(get_class($this));

		//Add fields
		$this->add_field('title', 'Enter title', '', 'text');
		$this->add_field('forecast_key', 'Forecast.io API Key', '', 'text');
    $this->add_field('city', 'City', '', 'text');
		$this->add_field('lat', 'Latitude', '', 'text');
		$this->add_field('long', 'Longtitude', '', 'text');
		$this->add_field('cache', 'Cache timeout', '', 'number');

		//Init the widget
		parent::__construct($this->textdomain, __(self::WIDGET_NAME, $this->textdomain), array( 'description' => __(self::WIDGET_DESCRIPTION, $this->textdomain), 'classname' => $this->textdomain));
	}

	public function widget($args, $instance)
	{
		$title = apply_filters('widget_title', $instance['title']);

		echo $args['before_widget'];

		if (!empty($title))
			echo $args['before_title'] . $title . $args['after_title'];


		$this->widget_output($args, $instance);

		echo $args['after_widget'];
	}

	private function widget_output($args, $instance)
	{
		extract($instance);

		// Get the API results
		$data = $this->get_weather(esc_attr($forecast_key), esc_attr($lat), esc_attr($long));

		// Create the widget
		?>

			<div class="f13-weather-container">
				<span class="f13-weather-title"><?php echo esc_attr($city); ?></span>
				<canvas id="f13-weather-icon"></canvas>
				<div class="f13-weather-stats">
					<span><i class="fa fa-clock-o"></i> <?php echo $data['currently']['summary']; ?></span>
					<span><i class="fa fa-bar-chart"></i>	<?php echo $data['currently']['temperature']; ?>&deg;F/<?php echo round(5/9*($data['currently']['temperature']-32), 1); ?>&deg;C <br /></span>
					<span><i class="fa fa-cloud"></i> <?php echo $data['currently']['cloudCover'] * 10; ?>%</span>
					<span>Precipitation: <?php echo $data['currently']['precipIntensity']; ?></span>
					<span>Wind speed: <?php echo round($data['currently']['windSpeed'] * 2.23694, 1); ?>mph <?php echo round($data['currently']['windSpeed']*3.6, 1); ?>kmh</span>
					<span>Visibility: <?php echo $data['currently']['visibility'] * 10; ?>%</span>
			</div>

			<!-- load the skycons -->
			<script src="<?php echo plugins_url('skycons.js', __FILE__); ?>">
			</script>

			<script>
				var skycons = new Skycons();
	      skycons.add("f13-weather-icon", Skycons.<?php echo strtoupper(str_replace('-', '_', $data['currently']['icon']));?>);
				skycons.play();
			</script>
		<?php

		echo $output;
	}

	public function form( $instance )
	{
		/**
		 * Create a header with basic instructions.
		 */
		?>
			<br/>
			Use this widget to add the weather for the location below to your website<br/>
			<br/>
			Get your API key from forecast.io.<br/>
			<br/>
		<?php
		/* Generate admin form fields */
		foreach($this->fields as $field_name => $field_data)
		{
			if($field_data['type'] === 'text')
			{
				?>
				<p>
					<label for="<?php echo $this->get_field_id($field_name); ?>"><?php _e($field_data['description'], $this->textdomain ); ?></label>
					<input class="widefat" id="<?php echo $this->get_field_id($field_name); ?>" name="<?php echo $this->get_field_name($field_name); ?>" type="text" value="<?php echo esc_attr(isset($instance[$field_name]) ? $instance[$field_name] : $field_data['default_value']); ?>" />
				</p>
			<?php
			}
			elseif($field_data['type'] === 'number')
			{
				?>
				<p>
					<label for="<?php echo $this->get_field_id($field_name); ?>"><?php _e($field_data['description'], $this->textdomain ); ?></label>
					<input class="widefat" id="<?php echo $this->get_field_id($field_name); ?>" name="<?php echo $this->get_field_name($field_name); ?>" type="number" value="<?php echo esc_attr(isset($instance[$field_name]) ? $instance[$field_name] : $field_data['default_value']); ?>" />
				</p>
			<?php
			}
			/* Otherwise show an error */
			else
			{
				echo __('Error - Field type not supported', $this->textdomain) . ': ' . $field_data['type'];
			}
		}
	}

	private function add_field($field_name, $field_description = '', $field_default_value = '', $field_type = 'text')
	{
		if(!is_array($this->fields))
			$this->fields = array();

		$this->fields[$field_name] = array('name' => $field_name, 'description' => $field_description, 'default_value' => $field_default_value, 'type' => $field_type);
	}

	public function update($new_instance, $old_instance)
	{
		return $new_instance;
	}

	private function get_weather($key, $lat, $long)
	{
	    // start curl
	    $curl = curl_init();

	    // set the curl URL
	    $url = 'https://api.forecast.io/forecast/' . $key . '/' . $lat . ',' . $long;

	    // Set curl options
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPGET, true);

	    // Set the user agent
	    curl_setopt($curl, CURLOPT_USERAGENT, 'F13 WP Book Shortcode/1.0');
	    // Set curl to return the response, rather than print it
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	    // Get the results and store the XML to results
	    $results = json_decode(curl_exec($curl), true);

	    // Close the curl session
	    curl_close($curl);

	    return $results;
	}

}
