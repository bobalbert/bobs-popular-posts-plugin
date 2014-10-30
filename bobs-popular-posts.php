<?php
/**
 * Plugin Name: Bob's Popular Posts
 * Description: Simple Plugin to track popularity of posts based on views and displays in a widget.
 *              ;-) help from Morten Rand-Hendriksen: @mor10 - http://mor10.com
 * Version: 0.1
 * Author: Bob Albert
 * Author URI: http://bobalbert.info
 * License: GPL2
 */

/**
 * Post Popularity Counter
 *
 * @param int $post_id
 */
function bobs_popular_post_views ( $postID) {
	$total_key = 'views';

	// get current total_key field
	$total_views = get_post_meta( $postID, $total_key, true );

	// if not set, set data with inital value
	if ( $total_views == '' ){
		$total_views = 0;
		delete_post_meta( $postID, $total_key );
		add_post_meta( $postID, $total_key, '0' );
	} else {
		// increment value for new view
		$total_views++;
		update_post_meta( $postID, $total_key, $total_views );

	}

}

// Remove prefetching to avoid confusion
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

/**
 * Dynamically inject counter into single posts
 *
 * @param int $post_id
 */
function bobs_count_popular_posts ( $post_id ) {
	// check single post and user is a visitor/not loggedin
	if ( !is_single() ){
		return;
	}
	if ( !is_user_logged_in() ){
		// get the post ID
		if ( empty( $post_id ) ){
			global $post;
			$post_id = $post->ID;
		}

		// call post counter on post
		bobs_popular_post_views( $post_id );
	}
}
add_action( 'wp_head', 'bobs_count_popular_posts' );

/**
 * add view count data to all posts table
 *
 * @param $defaults
 * @return mixed
 */
function bobs_add_views_column ( $defaults ){
	$defaults['post_views'] = __('View Count');
	return $defaults;
}
add_filter ( 'manage_posts_columns', 'bobs_add_views_column' );

function bobs_display_views ( $column_name ){
	if ( $column_name === 'post_views' ) {
		echo (int) get_post_meta( get_the_ID(), 'views', true );
	}
}
add_action( 'manage_posts_custom_column', 'bobs_display_views', 5, 2 );


/**
 * Bob's Popular Posts Widget
 */
class bobs_popular_posts extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'bobs_popular_posts', // Base ID
			__("Bob's Popular Posts", 'text_domain'), // Name
			array( 'description' => __( 'Displays the 5 most popular posts', 'text_domain' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}

		echo '<ul>';
		// Query for top 5 posts
		$query_args = array(
			'post_type'     => 'post',
			'posts_per_page' => 5,
			'meta_key'      => 'views',
			'orderby'       => 'meta_value_num',
			'order'         => 'DESC',
			'ignore_sticky_posts' => true
		);

		// The Query
		$the_query = new WP_Query( $query_args );

		// The Loop
		if ( $the_query->have_posts() ) {
			echo '<ul>';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				echo '<li>';
				echo '<a href="' . get_the_permalink() .'" rel="bookmark">';
				echo get_the_title();
				echo ' (' . (int) get_post_meta( get_the_ID(), 'views', true ) . ')';
				echo '</a>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			// no posts found'
			// echo 'no posts';
		}
		/* Restore original Post Data */
		wp_reset_postdata();

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Popular Posts', 'text_domain' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
	<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

} // class bobs_popular_posts


/**
 * register bobs_popular_posts widget
 */
function register_bobs_popular_posts_widget() {
	register_widget( 'bobs_popular_posts' );
}
add_action( 'widgets_init', 'register_bobs_popular_posts_widget' );


