

<?php
/**
 * @package RelatedPosts MON
 * @version 0.1
 */
/*
Plugin Name: Related Posts MON
Plugin URI: http://med.over.net
Description: Povezava med posti za Med.Over.Net
Author: Tine Dolžan
Version: 0.1 
Author URI: http://med.over.net
*/

//V file od teme single.php je potrebno dadati hook: do_action('related_posts')

// Preveri in opozori, če ni naložen advanced-custum-fields plugin
add_action( 'admin_notices', 'rp_check_plugin' );

function rp_check_plugin()
{
	if ( is_plugin_inactive( 'advanced-custom-fields/acf.php' ) )
	{
		echo"<div class=\"error\"> <p>Related Posts MON: Plugin Advanced Custom Fields ni naložen ali aktiviran!</p></div>"; 
	}
}

add_action( 'related_posts', 'related_posts' );

function related_posts()
{
  	echo "<h3 class=\"dividing-title\">Oglejte si še</h3>";

	$rp_prikazani[0] = get_the_ID();	//ID glavnega posta, da se ne ponovi v predlogih
	global $post;
	$rp_x = 0;	//Štetje, koliko postov je bilo izpisanih

	$rp_relacije = get_field('relacije');	//Funkcija od Advanced custom fields plugina, no plugin no FUN

	//Izpiši ročno izbrane poste
	if ($rp_relacije)
	{
		$rp_args = array( 'post__in' => $rp_relacije );
		$rp_post_array = get_posts( $rp_args );

		foreach ( $rp_post_array as $post )
		{
	  		setup_postdata( $post );
	  		rp_izpis();
	  		$rp_prikazani[$rp_x+1] = get_the_ID();
	  		$rp_x++;
		}

		wp_reset_postdata();
	}

	//Če niso izbrani 4 posti, poišči dodatne, glede na kategorijo ali tag
	if ( $rp_x < 4 )
	{
		$rp_categories = get_the_category();
		foreach ( $rp_categories as $rp_category ) 
		{ 
			$cat_IDs .= $rp_category->term_id . ",";
		}

		$cat_IDs = substr($cat_IDs, 0, -1);

		$rp_oznake = get_the_tags();

		if ($rp_oznake) 
		{
			foreach($rp_oznake as $oznaka) 
			{
				$seznam_oznak .= $oznaka->name . ",";
			}
			$seznam_oznak = substr($seznam_oznak, 0, -1);
		}

		$rp_args = array(
			'posts_per_page'   	=> 6-$rp_x,
			'tag'				=> "$seznam_oznak",
			'offset'           	=> 0,
			'category'         	=> "$cat_IDs",
			'orderby'          	=> 'date',
			'order'            	=> 'DESC',
			'post_type'        	=> 'post',
			'post_status'      	=> 'publish',
			'suppress_filters' 	=> true 
		);

		$rp_post_array = get_posts( $rp_args );

		foreach ( $rp_post_array as $post )
		{
	  		setup_postdata( $post );
	  		$rp_cid = get_the_ID();

	  		//Preveri, če je post že prikazan in v tem primeru postavi $rp_y = 0
	  		$rp_y = 1;
			foreach ( $rp_prikazani as $rp_i )
			{
				if ($rp_i == $rp_cid)
				{
					$rp_y = 0;
				}
			}

			//Preveri, če bo post izpisan
	  		if ( $rp_x < 4 && $rp_y )
	  		{
	  			rp_izpis();
	  			$rp_x++;
	  		}
	  		
		}

		wp_reset_postdata();
	}
}

function rp_izpis()
{
	?>
		<div class="box span span-2_1 fak">
			<a href="<?php the_permalink() ?>" class="link-holder">
				<div class="box-top">
					<div class="meta">
						<span class="date"><?php the_date() ?></span>
						<h2 class="title"><?php the_title() ?></h2>
					</div>
							<?php
							if( has_post_thumbnail() ):
								the_post_thumbnail('category-grid',array("alt" => overnet_get_featured_img_alt(get_the_ID()))); 
							else: 
							?>                           
							<img src="<?php echo get_theme_mod('overnet_default_featured_image'); ?>" alt="generic image">
							<?php endif; ?>
				</div>
				<div class="box-bottom">
						<?php the_excerpt() ?>
				</div>
			</a>
		</div>
	<?php
}

?>
