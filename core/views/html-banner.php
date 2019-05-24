<?php
/**
 * HTML View for the Scheduled Banner
 * If needed, this can be overridden by the Theme
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div <?php post_class( array( 'callout', 'quaternary' ) ); ?>>
	
	<div class="row">
		
		<div class="small-12 columns">
	
			<?php the_content(); ?>
			
		</div>
		
	</div>
	
</div>