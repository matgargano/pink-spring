<?php get_template_part( 'templates/header' ); ?>
<section class="content <?php if ( spring_display_sidebar() ) { echo 'has_sidebar'; } ?>">
	<main class="content--body <?php echo spring_main_class(); ?>" role="main">
	        <?php include spring_template_path(); ?>
	</main>
    <!-- /.main -->
    <?php if ( spring_display_sidebar() ) : ?>
        <aside class="sidebar content--sidebar <?php echo spring_sidebar_class(); ?>" role="complementary">
            <?php include spring_sidebar_path(); ?>
        </aside><!-- /.sidebar -->
    <?php endif; ?>
</section><!-- /.content -->

<?php get_template_part( 'templates/footer' ); ?>

</body>
</html>
