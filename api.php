<?php
/**
 * Plugin Name: Room Malta Api Integration
 * Description: Update posts and ACF fields with API data .
 * Version: 1.0
 * Author: NextNova.tech
 */

 function enqueue_custom_scripts() {
    wp_enqueue_script('custom-api-sync', plugin_dir_url(__FILE__) . 'ajax.js', array('jquery'), '1.0', true);
    // Localize the script with the AJAX URL
    wp_localize_script('custom-api-sync', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

add_action('wp_ajax_custom_api_sync_ajax', 'custom_api_sync_ajax_callback');
add_action('wp_ajax_nopriv_custom_api_sync_ajax', 'custom_api_sync_ajax_callback');

function custom_api_sync_ajax_callback() {
   // Define your API endpoint
   $api_endpoint = 'http://api.portal.islandproperties.com.mt/1/53';

   // Define your headers with key-value pairs
   $headers = array(
       'Content-Type'  => 'application/json',
       'apiKey' => '769af2d1-df69-48c1-acf1-e69b14011987',
   );

   // Fetch data from the API with headers
   $response = wp_remote_get($api_endpoint, array('headers' => $headers));

   // Check for errors or empty response
   if (is_wp_error($response)) {
       echo json_encode(array('error' => 'Error fetching data from the API.'));
       wp_die();
   }

   // Decode JSON response
   $body = wp_remote_retrieve_body($response);
   $data = json_decode($body);
   print_r("abc");

   // Process and prepare your data here as needed
   foreach ($data as $item) {
  
      
    $post_titl = $item->PropertyId;
     $single_post = "http://api.portal.islandproperties.com.mt/{$post_titl}";
    
     $response_single = wp_remote_get($single_post, array('headers' => $headers));
     $body_single = wp_remote_retrieve_body($response_single);
     $single_post_data = json_decode($body_single);
    //  var_dump($single_post_data->PropertyId);
    $propertyId = $single_post_data->PropertyId;
     $post_title = "Room available in {$single_post_data->Locality} {$post_titl}";
	   var_dump($post_title);
       $repeater_data = $single_post_data->Features;
     if(!empty($single_post_data->Locality)) {
        
        $existing_post_id = get_posts(array(
            'post_type'  => 'room',
            'post_title' => $post_title,
            'meta_key'   => 'property_id',
            'meta_value' => $propertyId,
            'fields'     => 'ids',
            'posts_per_page' => -1,
        ));
   
       //  foreach ($existing_post_id as $post_id) {
       //     $post_permalink = get_permalink($post_id);
       //     if ($post_permalink) {
       //         $custom_post_links[] = $post_permalink;
       //     }
       // }
       
        $post_data = array(
            'post_title'   => $post_title,
            'post_content' => $single_post_data->Description,
            'post_type'    => 'room', // Replace with your custom post type
         'post_status'  => 'publish',
            // Add additional fields as needed
        );
    
      if (!empty($existing_post_id))   {  
       
        
        foreach ($existing_post_id as $existing_post_ids) {
    //          var_dump($existing_post_id);
    
           
        
            $update_data = array(
                'ID' => $existing_post_ids,
                'post_title'   => $post_title,
                'post_content' => $single_post_data->Description,
                'post_type'    => 'room', // Replace with your custom post type
                // Add additional fields as needed
            );
        $update_post_id = $existing_post_ids;
        if (isset($update_post_id)){
       wp_update_post($update_data);
        }
        // Update ACF fields
       if (function_exists('update_field')) {
       update_acf_fields($single_post_data, $update_post_id);
               if(is_array($repeater_data)) {
                 foreach($repeater_data as $key =>$repeat) {
                     $row = array(
                         'featured' => $repeat
                     );
                     update_row('feature', $key + 1,$row,$update_post_id);
                 }
             }
       }
    }
      } else {
               
                 $post_id=wp_insert_post($post_data);
                    if (!is_wp_error($post_id)) {
                        // Update ACF fields
                       update_acf_fields($single_post_data, $post_id);
                         if (is_array($repeater_data)) {
                           foreach ($repeater_data as $repeat) {
                               $row = array(
                                   'featured' => $repeat
                               );
                               add_row('feature', $row, $post_id);
                           }
       }
                    } else {
                        // Log an error if post insertion fails
                        error_log('Failed to insert post for title ' . $post_title . '. Error: ' . $post_id->get_error_message());
                    }
      }
    wp_reset_postdata();
    // ob_end_flush();
    }
    }
}

function update_acf_fields($single_post_data, $post_id) {
    // Update ACF fields with API data
    update_field('bedrooms', $single_post_data->NumberOfBedrooms, $post_id);
    update_field('bathrooms', $single_post_data->NumberOfBathrooms, $post_id);
    update_field('locality', $single_post_data->Locality, $post_id);
    update_field('size', $single_post_data->Size, $post_id);
    update_field('price', $single_post_data->Price, $post_id);
    update_field('property_id', $single_post_data->PropertyId, $post_id);
    update_field('property_type', $single_post_data->PropertyType, $post_id);
	$features = $single_post_data->Features;
	if (in_array("En Suite", $features)) {
			$bathroom = "{$single_post_data->BlockName} (Private Bathroom)";
		} else {
			$bathroom = $single_post_data->BlockName;
		}
	update_field('block_name', $bathroom, $post_id);
    $dob_str = $single_post_data->DateAvailable;
    $date_now = date('Y-m-d');
    $davail = substr($dob_str, 0, 10);
    if ($davail <= $date_now) {
        update_field('date', 'Available Now', $post_id);
    } else {
        update_field('upcoming_date', $davail, $post_id);
    }
}
