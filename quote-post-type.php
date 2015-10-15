<?php
if( ! function_exists( 'quote_create_post_type' ) ) :
	function quote_create_post_type() {
		$labels = array(
			'name' => __( 'Quote' ),
			'singular_name' => __( 'Quote' ),
			'add_new' => __( 'Add quote' ),
			'all_items' => __( 'All quotes' ),
			'add_new_item' => __( 'Add quote' ),
			'edit_item' => __( 'Edit quote' ),
			'new_item' => __( 'New quote' ),
			'view_item' => __( 'View quote' ),
			'search_items' => __( 'Search quotes' ),
			'not_found' => __( 'No quotes found' ),
			'not_found_in_trash' => __( 'No quotes found in trash' ),
			'parent_item_colon' => __( 'Parent quote' )
			//'menu_name' => default to 'name'
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'has_archive' => true,
			'publicly_queryable' => true,
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				//'author',
				//'trackbacks',
				//'custom-fields',
				//'comments',
				'revisions',
				//'page-attributes', // (menu order, hierarchical must be true to show Parent option)
				//'post-formats',
			),
			'taxonomies' => array( 'category', 'post_tag' ), // add default post categories and tags
			'menu_position' => 5,
			'register_meta_box_cb' => 'quote_add_post_type_metabox'
		);
		register_post_type( 'quote', $args );
		//flush_rewrite_rules();
 
		register_taxonomy( 'quote_category', // register custom taxonomy - quote category
			'quote',
			array( 'hierarchical' => true,
				'label' => __( 'Quote categories' )
			)
		);
		register_taxonomy( 'quote_tag', // register custom taxonomy - quote tag
			'quote',
			array( 'hierarchical' => false,
				'label' => __( 'Quote tags' )
			)
		);
	}
	add_action( 'init', 'quote_create_post_type' );
 
 
	function quote_add_post_type_metabox() { // add the meta box
		add_meta_box( 'quote_metabox', 'Meta', 'quote_metabox', 'quote', 'normal' );
	}
 
 
	function quote_metabox() {
		global $post;
		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="quote_post_noncename" id="quote_post_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
 
		// Get the data if its already been entered
		$quote_post_name = get_post_meta($post->ID, '_quote_post_name', true);
		$quote_post_desc = get_post_meta($post->ID, '_quote_post_desc', true);
 
		// Echo out the field
		?>
 
		<div class="width_full p_box">
			<p>
				<label>Name<br>
					<input type="text" name="quote_post_name" class="widefat" value="<?php echo $quote_post_name; ?>">
				</label>
			</p>
			<p><label>Description<br>
					<textarea name="quote_post_desc" class="widefat"><?php echo $quote_post_desc; ?></textarea>
				</label>
			</p>
		</div>
	<?php
	}
 
 
	function quote_post_save_meta( $post_id, $post ) { // save the data
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if( !wp_verify_nonce( $_POST['quote_post_noncename'], plugin_basename(__FILE__) ) ) {
			return $post->ID;
		}
 
		// is the user allowed to edit the post or page?
		if( ! current_user_can( 'edit_post', $post->ID )){
			return $post->ID;
		}
		// ok, we're authenticated: we need to find and save the data
		// we'll put it into an array to make it easier to loop though
 
		$quote_post_meta['_quote_post_name'] = $_POST['quote_post_name'];
		$quote_post_meta['_quote_post_desc'] = $_POST['quote_post_desc'];
 
		// add values as custom fields
		foreach( $quote_post_meta as $key => $value ) { // cycle through the $quote_post_meta array
			// if( $post->post_type == 'revision' ) return; // don't store custom data twice
			$value = implode(',', (array)$value); // if $value is an array, make it a CSV (unlikely)
			if( get_post_meta( $post->ID, $key, FALSE ) ) { // if the custom field already has a value
				update_post_meta($post->ID, $key, $value);
			} else { // if the custom field doesn't have a value
				add_post_meta( $post->ID, $key, $value );
			}
			if( !$value ) { // delete if blank
				delete_post_meta( $post->ID, $key );
			}
		}
	}
	add_action( 'save_post', 'quote_post_save_meta', 1, 2 ); // save the custom fields
endif; // end of function_exists()
 
 
if( ! function_exists( 'view_quotes_posts' ) ) : // output
	function view_quotes_posts( $num = 4, $do_shortcode = 1, $strip_shortcodes = 0 ) {
 
		$args = array(
			'numberposts'     => $num,
			'offset'          => 0,
			//'category'        => ,
			'orderby'         => 'menu_order, post_title', // post_date, rand
			'order'           => 'DESC',
			//'include'         => ,
			//'exclude'         => ,
			//'meta_key'        => ,
			//'meta_value'      => ,
			'post_type'       => 'quote',
			//'post_mime_type'  => ,
			//'post_parent'     => ,
			'post_status'     => 'publish',
			'suppress_filters' => true
		);
 
		$posts = get_posts( $args );
 
		$html = '';
		foreach ( $posts as $post ) {
			$meta_name = get_post_meta( $post->ID, '_quote_post_name', true );
			$meta_desc = get_post_meta( $post->ID, '_quote_post_desc', true );
			$img = get_the_post_thumbnail( $post->ID, 'medium' );
			if( empty( $img ) ) {
				$img = '<img src="'.plugins_url( '/img/default.png', __FILE__ ).'">';
			}
 
 
			if( has_post_thumbnail( $post->ID ) ) {
				//$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
				//$img_url = $image[0];
				$img = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'thumbnail' );
				$img_url = $img[0];
 
				//the_post_thumbnail( 'thumbnail' ); /* thumbnail, medium, large, full, thumb-100, thumb-200, thumb-400, array(100,100) */
			}
 
			$content = $post->post_content;
			if( $do_shortcode == 1 ) {
				$content = do_shortcode( $content );
			}
			if( $strip_shortcodes == 1 ) {
				$content = strip_shortcodes( $content );
			}
 
			$html .= '
			<div>
				<h3>'.$post->post_title.'</h3>
				<div>
					<p>Name: '.$meta_name.'</p>
					<p>Description: '.$meta_desc.'</p>
				</div>
				<div>'.$img.'</div>
				<div>'.$content.'</div>
			</div>
    		';
		}
		$html = '<div class="wrapper">'.$html.'</div>';
		return $html;
	}
endif; // end of function_exists()
?>