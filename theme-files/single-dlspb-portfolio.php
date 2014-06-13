<?php get_header(); ?>

<div id="primary" class="content-area <?php echo get_post_type(); ?> dlspb">
    <div id="content" class="site-content" role="main">

        <?php /* The loop */ ?>
        <?php while (have_posts()) : the_post(); ?>

            <?php dlspb_get_template_part('content', get_post_type()); ?>

        <?php endwhile; ?>

    </div><!-- #content -->
</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>