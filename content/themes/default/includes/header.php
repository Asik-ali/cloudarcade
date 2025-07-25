<!DOCTYPE html>
<html <?php the_html_attrs() ?>>
	<head>
		<meta charset="utf-8">
		<meta name="google-adsense-account" content="ca-pub-3121432182108183">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
		<title><?php echo get_page_title() ?></title>
		<meta name="description" content="<?php echo substr(esc_string($meta_description), 0, 160) ?>">
		<?php the_canonical_link() ?>
		<?php
			if(isset($game)){ //Game page
				?>
				<meta name="twitter:card" content="summary_large_image" />
				<meta name="twitter:title" content="<?php echo htmlspecialchars( $page_title )?>" />
				<meta name="twitter:description" content="<?php echo substr(esc_string($meta_description), 0, 200) ?>" />
				<?php
				if(isset($game->thumb_1)){
					$thumb = $game->thumb_1;
					if(substr($thumb, 0, 1) == '/'){
						$thumb = DOMAIN . substr($thumb, 1);
					}
					echo('<meta name="twitter:image:src" content="'.$thumb.'">');
					echo('<meta property="og:image" content="'.$thumb.'">');
				}
			}
		?>
		<?php load_plugin_headers() ?>
		<link rel="stylesheet" type="text/css" href="<?php echo DOMAIN . TEMPLATE_PATH; ?>/style/bootstrap.min.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo DOMAIN . TEMPLATE_PATH; ?>/style/jquery-comments.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo DOMAIN . TEMPLATE_PATH; ?>/style/user.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo DOMAIN . TEMPLATE_PATH; ?>/style/style.css" />
		<link rel="stylesheet" type="text/css" href="<?php echo DOMAIN . TEMPLATE_PATH; ?>/style/custom.css" />
		<!-- Font Awesome icons (free version)-->
		<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
		<!-- Google fonts-->
		<link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css" />
		<link href="https://fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic" rel="stylesheet" type="text/css" />
		<?php widget_aside('head') ?>
	</head>
	<body id="page-top">
		<!-- Navigation-->
		<nav class="navbar navbar-expand-lg navbar-dark top-nav" id="mainNav">
			<div class="container">
				<button id="toggler" class="navbar-toggler navbar-toggler-left collapsed" type="button" data-toggle="collapse" data-target="#navb" aria-expanded="false">
					<span class="navbar-toggler-icon"></span>
				</button>
				<a class="navbar-brand js-scroll-trigger" href="<?php echo DOMAIN ?>"><img src="<?php echo DOMAIN .SITE_LOGO ?>" class="site-logo" alt="site-logo"></a>
				<?php include  TEMPLATE_PATH . "/parts/navigation-top.php" ?>
				<?php show_user_profile_header() ?>
			</div>
		</nav>
		<div class="nav-categories">
			<div class="container">
				<?php include  TEMPLATE_PATH . "/parts/navigation-categories.php" ?>
			</div>
		</div>
