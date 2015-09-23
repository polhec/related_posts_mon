			<div class="box span span-2_1 fak">
				<a href="<?php the_permalink() ?>" class="link-holder">
					<div class="box-top">
						<div class="meta">
							<span class="date"></span>
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