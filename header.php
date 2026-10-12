<!DOCTYPE html>
<html lang="en">
  <head>
    <title>
      <?php 
        if(!is_home()) {
          wp_title(''); 
          echo ' | ';
        }
      ?>
      <?php echo get_bloginfo('name'); ?>
    </title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="<?php get_bloginfo('description'); ?>">

    <link rel="icon" href="<?php echo get_bloginfo('template_directory'); ?>/luffman_guess_favicon.png">
    <?php wp_head(); ?>

    <link rel="apple-touch-icon" href="<?php echo get_bloginfo('template_directory'); ?>/luffman_guess_favicon.png" />
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo get_bloginfo('template_directory'); ?>/luffman_guess_favicon.png" />
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo get_bloginfo('template_directory'); ?>/luffman_guess_favicon.png" />
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo get_bloginfo('template_directory'); ?>/luffman_guess_favicon.png" />

    <script src="https://cdn.jsdelivr.net/npm/@tsparticles/confetti@3.0.3/tsparticles.confetti.bundle.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat+Brush&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  
    </head>

  <body>