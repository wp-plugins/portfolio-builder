<li id="dlspb-portfolio-<?php the_ID(); ?>">
    <div class="dlspb-portfolip-inner">

        <a href="<?php the_permalink(); ?>" rel="bookmark">
            <div class="dlspb-image" style="background-image: url('<?php dlspb_the_banner_url(); ?>');"></div>
            <div class="dlspb-text">
                <span class="dlspb-title"><?php the_title(); ?></span>
                <span class="dlspb-subtitle"><?php echo get_post_meta($post->ID, 'dlspb_subtitle', true); ?></span>
                <button class="button primary"><?php _e('Read More', 'dlspb'); ?></button>
            </div><!-- .dlspb-text -->
        </a>

    </div><!-- .dlspb-portfolip-inner -->
</li><!-- #dlspb-portfolio-<?php the_ID(); ?> -->