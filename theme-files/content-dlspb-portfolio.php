<?php
$screenshots = dlspb_get_screenshots();
$relateds = dlspb_get_related();
$detail_image = dlspb_get_detail_image('large');
?>

<article id="dlspb-portfolio-<?php the_ID(); ?>" <?php post_class(); ?>>

    <header class="entry-header">
        <h1 class="entry-title"><?php the_title(); ?></h1>
    </header><!-- .entry-header -->

    <div class="entry-content">

        <?php if (!empty($detail_image)): ?>
            <div id="dlspb-detail-image">
                <?php echo $detail_image; ?>
            </div>
        <?php endif; ?>

        <div id="dlspb-tabs" class="dlspb-min"><!--

            --><ul class="dlspb-js-only"><!--
                --><li><a href="#dlspb-description"><? _e('Description', 'dlspb'); ?></a></li><!--
                <?php if (!empty($screenshots)): ?>--><li><a href="#dlspb-screenshots"><?php _e('Screenshots', 'dlspb'); ?></a></li><!--<?php endif; ?>
                <?php if (!empty($relateds)): ?>--><li><a href="#dlspb-related"><?php _e('Related Projects', 'dlspb'); ?></a></li><!--<?php endif; ?>
            --></ul>

            <div id="dlspb-description">
                <h2 class="dlspb-no-js"><? _e('Description', 'dlspb'); ?></h2>
                <?php the_content(); ?>
            </div>

            <?php if (!empty($screenshots)): ?>
            <div id="dlspb-screenshots">
                <h2 class="dlspb-no-js"><? _e('Screenshots', 'dlspb'); ?></h2>
                <?php
                echo '<ul class="dlspb-screenshots">';
                foreach ($screenshots as $screenshot) {
                    $full_image = wp_get_attachment_image_src($screenshot['id'], 'full');
                    echo '<li><a href="'.$full_image[0].'" rel="dlspb_screenshots">'.wp_get_attachment_image($screenshot['id'], 'thumbnail').'</a></li>';
                }
                echo '</ul><!-- dlspb-screenshots -->';
                ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($relateds)): ?>
            <div id="dlspb-related">
                <h2 class="dlspb-no-js"><? _e('Related Projects', 'dlspb'); ?></h2>
                <?php
                echo '<ul class="dlspb-related">';
                foreach ($relateds as $related) {
                    echo '<li><a href="'.get_permalink($related->ID).'">'.$related->post_title.'</a></li>';
                }
                echo '</ul><!-- dlspb-related -->';
                ?>
            </div>
            <?php endif; ?>

        </div>

        <?php dlspb_the_social_media(); ?>

    </div><!-- .entry-content -->

</article><!-- #dlspb-portfolio-<?php the_ID(); ?> -->
