<?php
/**
 * Template for displaying all category archives
 */

// Get the queried object (the current category)
$category = get_queried_object(); ?>

<div class="category-archive">
    <header class="category-header">
        <h1 class="category-title"><?php echo esc_html($category->name); ?></h1>
        
        <?php if (!empty($category->description)) : ?>
            <div class="category-description">
                <?php echo wp_kses_post($category->description); ?>
            </div>
        <?php endif; ?>
    </header>

    <div class="category-content">
        <?php if (have_posts()) : ?>
            <div class="posts-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="post-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <header class="post-header">
                            <h2 class="post-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            <div class="post-meta">
                                <?php echo get_the_date(); ?>
                            </div>
                        </header>
                        
                        <div class="post-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <a href="<?php the_permalink(); ?>" class="read-more">
                            Read More
                        </a>
                    </article>
                <?php endwhile; ?>
            </div>
            
            <?php the_posts_pagination(); ?>
            
        <?php else : ?>
            <p>No posts found in this category.</p>
        <?php endif; ?>
    </div>
</div> 