<?php get_header(); ?>

<div id="primary" class="content-area <?php echo get_post_type(); ?> dlspb">
    <div id="content" class="site-content" role="main">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

            <header class="entry-header">
                <h1 class="entry-title"><?php _e('Portfolio', 'dlspb'); ?></h1>
            </header><!-- .entry-header -->

            <div class="entry-content">
                <?php if (have_posts()) : ?>

                    <?php dlspb_the_filter_form(); ?>

                    <?php /* The loop */ ?>
                    <ul class="dlspb-list dlspb-<?php echo get_option('dlspb_list_layout'); ?>">
                    <?php while (have_posts()) : the_post(); ?>
                        <?php dlspb_get_template_part('loop', get_post_type()); ?>
                    <?php endwhile; ?>
                    </ul><!-- .dlspb-list -->

                    <?php dlspb_pagination(); ?>

                <?php else : ?>

                    <p><?php _e('Apologies, but no results were found. Perhaps searching will help find a related item.', 'dlspb' ); ?></p>
                    <?php get_search_form(); ?>

                <?php endif; ?>

            </div><!-- .entry-content -->

        </article>
    </div><!-- #content -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>