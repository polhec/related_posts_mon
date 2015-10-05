

<?php
/**
 * @package RelatedPosts MON
 * @version 0.3
 */
/*
Plugin Name: Related Posts MON
Plugin URI: http://med.over.net
Description: Povezava med posti za Med.Over.Net
Author: Tine Dolžan
Version: 0.3 
Author URI: http://med.over.net
*/

//V file od teme single.php je potrebno za tag </article> dodati hook: do_action('related_posts')

add_action( 'admin_notices', 'RelatedPost::check_ACF' );
add_action( 'init', 'RelatedPost::add_fields' );
add_action( 'related_posts', 'related_posts' );

function related_posts()
{
	$rel_posts = new RelatedPost;
	$rel_posts->selected();
	$rel_posts->url();
	$rel_posts->print_posts();
	$rel_posts->print_ex_posts();

	echo $rel_posts->x;
}

class RelatedPost {
	public 		$x = 0; //Counter of found posts
	public 		$show_posts = 4; //Number of posts to show
	private 	$posts_array = array();
	private 	$ex_posts_array = array();

/**
	 Get external posts
*/
	 function url()
	{
		$relacije = get_field('links');
		$povezave = explode ( "\n" , trim( $relacije ) );

		foreach ( $povezave as $rp_item )
		{
			if(filter_var($rp_item, FILTER_VALIDATE_URL))
			{
				$this->ex_posts_array[] = array();
				$num = count($this->ex_posts_array) - 1;

				$rp_html = file_get_contents( trim( $rp_item ) );

				$rp_pattern = '/(?<=og:title" content=")(.*)(?=")/'; //Najdi naslov
				preg_match($rp_pattern, $rp_html, $rp_matches);
				$this->ex_posts_array[$num][post_title] = $rp_matches[0];

				$rp_pattern = '/(?<=og:description" content=")(.*)(?=")/'; //Najdi opis
				preg_match($rp_pattern, $rp_html, $rp_matches);
				$this->ex_posts_array[$num][post_excerpt] = $rp_matches[0];

				$rp_pattern = '/(?<=og:url" content=")(.*)(?=")/'; //Najdi url
				preg_match($rp_pattern, $rp_html, $rp_matches);
				$this->ex_posts_array[$num][link] = $rp_matches[0];

				$rp_pattern = '/(?<=og:image" content=")(.*)(?=")/'; //Najdi sliko
				preg_match($rp_pattern, $rp_html, $rp_matches);
				$this->ex_posts_array[$num][image] = $rp_matches[0];

				$this->x++;
			}
		}
	}
/**
	 Get selected posts
*/
	public function selected()
	{
		$relacije = get_field('relacije');	//ACF function, no plugin no fun
		if ($relacije)
		{
			$args = array( 'post__in' => $relacije );
			$post_array_temp = get_posts( $args );

			$this->posts_array = array_merge($this->posts_array, $post_array_temp);
			$this->x += count( $this->posts_array);
		}
	}
/**
	 Print found posts
*/
	function print_posts()
	{
		global $post;
		$i = 0;
		foreach ( $this->posts_array as $post)##and $i < $this->show_posts)
		{
			setup_postdata($post);
			if ( !assert( locate_template( 'rp_template.php', true, false ) ) )
			{
				include "rp_template.php";
			}
			$i++;
		}
		wp_reset_postdata();
	}

	function print_ex_posts()
	{
		foreach ( $this->ex_posts_array as $post)##and $i < $this->show_posts)
		{
			if ( !assert( locate_template( 'rp_template_ex.php', true, false ) ) )
			{
				include "rp_template_ex.php";
			}
		}
	}
	/**
Get some random posts
*/
function random()
{
	//Če niso izbrani 4 posti, poišči dodatne, glede na kategorijo ali tag
	if ( $this->x < 4 )
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


/**
Test if ACF plugin is present and activated
*/
	function check_ACF()
	{
		if ( is_plugin_inactive( 'advanced-custom-fields/acf.php' ) )
		{
			echo"<div class=\"error\"> <p>Related Posts MON: Plugin Advanced Custom Fields ni naložen ali aktiviran!</p></div>"; 
		}
	}
/**
Generate custom fields (ACF plugin required)
*/
	function add_fields()
	{
		if(function_exists("register_field_group"))
		{
			//Texbox custom field for links to other sites
			register_field_group(array (
				'id' => 'acf_povezave-do-povezanih-clankov',
				'title' => 'Povezave do povezanih člankov',
				'fields' => array (
					array (
						'key' => 'field_560cd59df27ac',
						'label' => 'Povezave do sorodnih člankov',
						'name' => 'links',
						'type' => 'textarea',
						'instructions' => 'Dodaj povezave do sorodnih člankov (največ 4)',
						'default_value' => 'http://www...',
						'placeholder' => '',
						'maxlength' => '',
						'rows' => 2,
						'formatting' => 'none',
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
			//Relasionship custom field
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
}

$rp_x = 0;	//Štetje, koliko postov je bilo izpisanih
$rp_prikazani = array();	//ID glavnega posta, da se ne ponovi v predlogih

?>
