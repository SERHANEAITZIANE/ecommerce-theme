<?php
/**
 * AYRA Homewear - Page Template
 */
defined('ABSPATH') || exit;

get_header();
?>

<main id="main" class="site-main" role="main">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <?php
        while (have_posts()) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header mb-8 text-center">
                    <?php the_title('<h1 class="entry-title text-3xl font-bold mb-4">', '</h1>'); ?>
                </header>

                <div class="entry-content">
                    <?php
                    the_content();
                    ?>
                </div>
            </article>
            <?php
        endwhile;
        ?>
    </div>
</main>

<?php get_footer(); ?>
