<?php
/**
 * Widget areas + custom widgets.
 *
 * @package Manchit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register sidebars.
 */
function manchit_register_sidebars() {
	$before_title = '<h3 class="widget-title">';
	$after_title  = '</h3>';
	$before       = '<section id="%1$s" class="widget %2$s">';
	$after        = '</section>';

	register_sidebar(
		array(
			'name'          => __( 'الشريط الجانبي', 'manchit' ),
			'id'            => 'sidebar-main',
			'description'   => __( 'يظهر بجانب المقالات والأرشيف.', 'manchit' ),
			'before_widget' => $before,
			'after_widget'  => $after,
			'before_title'  => $before_title,
			'after_title'   => $after_title,
		)
	);

	$cols = (int) manchit_get_option( 'footer_columns', 4 );
	for ( $i = 1; $i <= $cols; $i++ ) {
		register_sidebar(
			array(
				/* translators: %d: column number */
				'name'          => sprintf( __( 'التذييل %d', 'manchit' ), $i ),
				'id'            => 'footer-' . $i,
				'before_widget' => $before,
				'after_widget'  => $after,
				'before_title'  => $before_title,
				'after_title'   => $after_title,
			)
		);
	}
}
add_action( 'widgets_init', 'manchit_register_sidebars' );

/**
 * Register custom widgets.
 */
function manchit_register_widgets() {
	register_widget( 'Manchit_Popular_Posts_Widget' );
	register_widget( 'Manchit_Recent_Posts_Widget' );
	register_widget( 'Manchit_Social_Widget' );
}
add_action( 'widgets_init', 'manchit_register_widgets' );

/**
 * Popular posts (by views) widget.
 */
class Manchit_Popular_Posts_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'manchit_popular',
			__( 'Manchit: الأكثر قراءة', 'manchit' ),
			array( 'description' => __( 'أكثر المقالات مشاهدة.', 'manchit' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] ?? __( 'الأكثر قراءة', 'manchit' ) );
		$count = max( 1, (int) ( $instance['count'] ?? 5 ) );
		$days  = (int) ( $instance['days'] ?? 0 );

		$q = function_exists( 'manchit_most_viewed_query' ) ? manchit_most_viewed_query( $count, $days ) : new WP_Query( array( 'posts_per_page' => $count, 'no_found_rows' => true ) );
		if ( ! $q->have_posts() ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore
		}
		echo '<div class="mn-widget-list">';
		$rank = 0;
		while ( $q->have_posts() ) {
			$q->the_post();
			$rank++;
			echo '<div class="mn-list-card">';
			printf( '<span class="mn-rank">%d</span>', $rank );
			if ( has_post_thumbnail() ) {
				echo '<a class="mn-list-card__media" href="' . esc_url( get_permalink() ) . '">' . get_the_post_thumbnail( get_the_ID(), 'manchit-list', array( 'loading' => 'lazy' ) ) . '</a>';
			}
			echo '<div><h4 class="mn-list-card__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h4>';
			echo '<div class="mn-list-card__meta">' . esc_html( manchit_relative_date() ) . '</div></div>';
			echo '</div>';
		}
		echo '</div>';
		echo $args['after_widget']; // phpcs:ignore
		wp_reset_postdata();
	}

	public function form( $instance ) {
		$title = $instance['title'] ?? __( 'الأكثر قراءة', 'manchit' );
		$count = $instance['count'] ?? 5;
		$days  = $instance['days'] ?? 0;
		?>
		<p><label><?php esc_html_e( 'العنوان:', 'manchit' ); ?>
			<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>"></label></p>
		<p><label><?php esc_html_e( 'عدد المقالات:', 'manchit' ); ?>
			<input type="number" min="1" max="20" class="tiny-text" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" value="<?php echo esc_attr( $count ); ?>"></label></p>
		<p><label><?php esc_html_e( 'خلال آخر (يوم، 0 = الكل):', 'manchit' ); ?>
			<input type="number" min="0" class="tiny-text" name="<?php echo esc_attr( $this->get_field_name( 'days' ) ); ?>" value="<?php echo esc_attr( $days ); ?>"></label></p>
		<?php
	}

	public function update( $new, $old ) {
		return array(
			'title' => sanitize_text_field( $new['title'] ?? '' ),
			'count' => max( 1, (int) ( $new['count'] ?? 5 ) ),
			'days'  => max( 0, (int) ( $new['days'] ?? 0 ) ),
		);
	}
}

/**
 * Recent posts with thumbnails widget.
 */
class Manchit_Recent_Posts_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'manchit_recent',
			__( 'Manchit: أحدث المقالات', 'manchit' ),
			array( 'description' => __( 'أحدث المقالات مع الصور المصغرة.', 'manchit' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] ?? __( 'أحدث المقالات', 'manchit' ) );
		$count = max( 1, (int) ( $instance['count'] ?? 5 ) );
		$cat   = (int) ( $instance['cat'] ?? 0 );

		$query_args = array(
			'posts_per_page'      => $count,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( $cat ) {
			$query_args['cat'] = $cat;
		}
		$q = new WP_Query( $query_args );
		if ( ! $q->have_posts() ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore
		}
		echo '<div class="mn-widget-list">';
		while ( $q->have_posts() ) {
			$q->the_post();
			echo '<div class="mn-list-card">';
			if ( has_post_thumbnail() ) {
				echo '<a class="mn-list-card__media" href="' . esc_url( get_permalink() ) . '">' . get_the_post_thumbnail( get_the_ID(), 'manchit-list', array( 'loading' => 'lazy' ) ) . '</a>';
			}
			echo '<div><h4 class="mn-list-card__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h4>';
			echo '<div class="mn-list-card__meta">' . esc_html( manchit_relative_date() ) . '</div></div>';
			echo '</div>';
		}
		echo '</div>';
		echo $args['after_widget']; // phpcs:ignore
		wp_reset_postdata();
	}

	public function form( $instance ) {
		$title = $instance['title'] ?? __( 'أحدث المقالات', 'manchit' );
		$count = $instance['count'] ?? 5;
		$cat   = $instance['cat'] ?? 0;
		?>
		<p><label><?php esc_html_e( 'العنوان:', 'manchit' ); ?>
			<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>"></label></p>
		<p><label><?php esc_html_e( 'عدد المقالات:', 'manchit' ); ?>
			<input type="number" min="1" max="20" class="tiny-text" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" value="<?php echo esc_attr( $count ); ?>"></label></p>
		<p><label><?php esc_html_e( 'التصنيف:', 'manchit' ); ?><br>
			<?php
			wp_dropdown_categories(
				array(
					'show_option_all' => __( 'كل التصنيفات', 'manchit' ),
					'name'            => $this->get_field_name( 'cat' ),
					'selected'        => $cat,
					'hide_empty'      => false,
				)
			);
			?>
		</label></p>
		<?php
	}

	public function update( $new, $old ) {
		return array(
			'title' => sanitize_text_field( $new['title'] ?? '' ),
			'count' => max( 1, (int) ( $new['count'] ?? 5 ) ),
			'cat'   => (int) ( $new['cat'] ?? 0 ),
		);
	}
}

/**
 * Social profiles widget.
 */
class Manchit_Social_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'manchit_social',
			__( 'Manchit: تابعنا', 'manchit' ),
			array( 'description' => __( 'روابط شبكات التواصل من إعدادات القالب.', 'manchit' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title  = apply_filters( 'widget_title', $instance['title'] ?? __( 'تابعنا', 'manchit' ) );
		$social = array_filter( (array) manchit_get_option( 'social', array() ) );
		if ( ! $social ) {
			return;
		}
		echo $args['before_widget']; // phpcs:ignore
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore
		}
		echo '<div class="mn-footer__social">';
		foreach ( $social as $network => $url ) {
			if ( ! $url ) {
				continue;
			}
			printf(
				'<a href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>',
				esc_url( $url ),
				esc_attr( $network ),
				manchit_icon( $network )
			);
		}
		echo '</div>';
		echo $args['after_widget']; // phpcs:ignore
	}

	public function form( $instance ) {
		$title = $instance['title'] ?? __( 'تابعنا', 'manchit' );
		?>
		<p><label><?php esc_html_e( 'العنوان:', 'manchit' ); ?>
			<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>"></label></p>
		<p class="description"><?php esc_html_e( 'تُدار الروابط من: المظهر ← إعدادات Manchit ← التواصل الاجتماعي.', 'manchit' ); ?></p>
		<?php
	}

	public function update( $new, $old ) {
		return array( 'title' => sanitize_text_field( $new['title'] ?? '' ) );
	}
}
