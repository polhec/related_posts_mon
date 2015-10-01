

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

//V file od teme single.php je potrebno za tag </article> dodati hook: do_action('related_posts')

/**
// Generiraj custom field za izbiro povezanih postov
*/

$rp_x = 0;	//Štetje, koliko postov je bilo izpisanih
$rp_prikazani = array();	//ID glavnega posta, da se ne ponovi v predlogih

add_action( 'init', 'rp_add_field' );

function rp_add_field()
{
	if(function_exists("register_field_group"))
	{
		register_field_group(array (
			'id' => 'acf_relacije',
			'title' => 'Relacije',
			'fields' => array (
				array (
					'key' => 'field_55fbadff07c46',
					'label' => 'Relacije',
					'name' => 'relacije',
					'type' => 'relationship',
					'instructions' => 'Izberi podobne članke',
					'return_format' => 'id',
					'post_type' => array (
						0 => 'post',
					),
					'taxonomy' => array (
						0 => 'all',
					),
					'filters' => array (
						0 => 'search',
					),
					'result_elements' => array (
						0 => 'post_type',
						1 => 'post_title',
					),
					'max' => 4,
				),
				array (
					'key' => 'field_55fbae2f07c47',
					'label' => '',
					'name' => '',
					'type' => 'text',
					'default_value' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'formatting' => 'html',
					'maxlength' => '',
				),
			),
			'location' => array (
				array (
					array (
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'post',
						'order_no' => 0,
						'group_no' => 0,
					),
				),
			),
			'options' => array (
				'position' => 'normal',
				'layout' => 'no_box',
				'hide_on_screen' => array (
				),
			),
			'menu_order' => 0,
		));
	}
}

/**
	 Preveri in opozori, če ni naložen advanced-custum-fields plugin
*/
add_action( 'admin_notices', 'rp_check_plugin' );

function rp_check_plugin()
{
	if ( is_plugin_inactive( 'advanced-custom-fields/acf.php' ) )
	{
		echo"<div class=\"error\"> <p>Related Posts MON: Plugin Advanced Custom Fields ni naložen ali aktiviran!</p></div>"; 
	}
}

/**
	 Naloži in izpiši poste
*/
add_action( 'related_posts', 'related_posts' );

function rp_izbrani()
{
	global $post;
	global $rp_x;
	global $rp_prikazani;

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

//		wp_reset_postdata();
	}
}

function rp_url()
{
	global $post;
	global $rp_x;
	global $rp_prikazani;

	if ( $rp_x < 4 )
	{
		$rp_relacije = get_field('links');
		$rp_povezave = explode ( "\n" , trim( $rp_relacije ) );

		foreach ( $rp_povezave as $rp_item )
		{
			if(filter_var($rp_item, FILTER_VALIDATE_URL))
			{
				$rp_html = file_get_contents( trim( $rp_item ) );

				$rp_pattern = '/(?<=og:title" content=")(.*)(?=")/'; //Najdi naslov
				preg_match($rp_pattern, $rp_html, $rp_matches);
				$post->post_title = $rp_matches[0];

				$rp_pattern = '/(?<=og:description" content=")(.*)(?=")/'; //Najdi opis
				preg_match($rp_pattern, $rp_html, $rp_matches);
				$post->post_excerpt = $rp_matches[0];

				$rp_pattern = '/(?<=og:url" content=")(.*)(?=")/'; //Najdi url
				preg_match($rp_pattern, $rp_html, $rp_matches);
				$rp_povezava = $rp_matches[0];

				$rp_pattern = '/(?<=og:image" content=")(.*)(?=")/'; //Najdi sliko
				preg_match($rp_pattern, $rp_html, $rp_matches);
				$rp_slika = $rp_matches[0];

		  		rp_izpis($rp_povezava, $rp_slika);

				$rp_x++;

				if ($rp_x == 4) 
				{
					break;
				}
			}
		}
		wp_reset_postdata();
	}
}

function related_posts()
{
  	echo "<h3 class=\"dividing-title\">Oglejte si še</h3>";
	global $post;
	global $rp_x;
	global $rp_prikazani;
	$rp_prikazani[0] = get_the_ID();

	rp_izbrani();
//	rp_url();

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

function rp_izpis($rp_povezava, $rp_slika)
{
	if ( !assert( locate_template( 'rp_template.php', true, false ) ) )
	{
		include "rp_template.php";
	}
}

?>
