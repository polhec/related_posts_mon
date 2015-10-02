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
			$rp_cat_list[] = $rp_category->term_id;
		}

		$rp_oznake = get_the_tags();
		foreach($rp_oznake as $rp_oznaka) 
		{
			$rp_tag_list[] = $rp_oznaka->term_id;
		}

		$rp_args = array(
			'posts_per_page'   	=> 6-$rp_x,
			'offset'           	=> 0,
			'orderby'          	=> 'date',
			'order'            	=> 'DESC',
			'post_type'        	=> 'post',
			'post_status'      	=> 'publish',
			'suppress_filters' 	=> true,
			'tax_query'			=> array(
				'relation' 			=> 'OR',
					array(
						'taxonomy' => 'post_tag',
						'field'    => 'term_id',
						'terms'    => $rp_tag_list
					),
					array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => $rp_cat_list
					),
				),
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

	if ( empty( locate_template( 'rp_template.php', true, false ) ) )
	{
		include "rp_template.php";
	}
}

?>
