<?php 

      require_once plugin_dir_path(__DIR__) . "/includes/Application.php";

      $result = null;

      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $result = $app->handleSubmit();
      }

      if ( isset($_GET['history']) ) {
            $result = $app->readHistory($_GET['history'] ?? null);
      }

      // clear history
      $app->clearHistory();

?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
      <meta charset="<?php bloginfo( 'charset' ); ?>" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <?php wp_head(); ?>
      <!-- bootstrap -->
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
      <!-- font awesome -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
      <!-- custom css -->
      <link rel="stylesheet" href="<?=plugins_url('counter/assets/css/main.css'); ?>">
      <link rel="stylesheet" href="<?=plugins_url('counter/assets/css/filter.css'); ?>">
</head>

<body <?php body_class(); ?>>
      <?php wp_body_open(); ?>

      <div class="wrapper">
            <div class="section">
                  <div class="top_navbar">
                        <!-- nav menu -->
                        <div class="hamburger">
                              <a href="#">
                                    <i class="fas fa-bars"></i>
                              </a>
                        </div>
                        <!-- nav menu end -->

                        <!-- download option start -->
                        <div class="dwonload">
                              <?php  if ( isset($_GET['history']) && $_GET['history'] != "") : ?>
                              <a download href="<?=$app->download($_GET['history']);?>">
                                    Download <i class="fa-solid fa-download"></i>
                              </a>
                              <?php endif;?>
                        </div>
                        <!-- download option end -->
                  </div>
                  <div class="container-custom">
                        <!-- start object buffaring -->
                        <?php ob_start(); ?>
                        <!-- hide form when its not history -->
                        <?php if ( ! isset($_GET['history']) ) : ?>
                        <form class="form container-fluid w-100" method="POST" enctype="multipart/form-data"
                              action="<?=$app->toolPermalink();?>">
                              <!-- wp nonce field to very submission -->
                              <?php  wp_nonce_field('handleSubmit');?>

                              <div class="row">
                                    <div class="form-group col-md-12 col-12">
                                          <label for="target_url">Target URL</label>
                                          <input type="text" class="form-control"
                                                value="<?= $result && ! is_string($result) ? $result->old("target_url") : '' ?>"
                                                name="target_url" id="target_url">

                                          <!-- Error if target url is empty or invalid -->
                                          <?php if ( $result && ! is_string($result) && $result->error('target_url') ) : ?>
                                          <p class="error"><?=$result->error('target_url'); ?></p>
                                          <?php endif; ?>
                                    </div>
                                    <div class="form-group col-md-12 col-12">
                                          <label for="xlsx_file" for="xlsx_file">Excel File</label>
                                          <input type="file" class="form-control" name="xlsx_file" id="xlsx_file"
                                                style="cursor: pointer;">

                                          <!-- Error if excel file empty or invalid format -->
                                          <?php if ( $result && ! is_string($result) && $result->error('xlsx_file') ) : ?>
                                          <p class="error"><?=$result->error('xlsx_file') ?></p>
                                          <?php endif; ?>
                                    </div>

                                    <div class="form-group col-12">
                                          <button class="btn btn-md btn-secondary w-100" type="submit">Get
                                                Result</button>
                                    </div>
                              </div>
                        </form>
                        <?php endif; ?>
                        <!-- hide form when its not history end-->
                        <div class="table">
                              <div class="table-responsive">
                                    <?php if ( is_string($result) ) {
                                                echo $result;
                                          } 
                                    ?>
                              </div>
                        </div>
                        <?php $__output = ob_get_clean();?>
                        <!-- add page content -->
                        <?= preg_replace($app->shortcodeMatchPatter,$__output,get_the_content()); ?>
                        <!-- add page content end -->
                  </div>

            </div>
            <!-- Sidebar section -->
            <div class="sidebar <?= $app->user ? 'sidebar-logged-in' : '' ?>">
                  <!-- This history menu would be visibe only to logged in user -->
                  <?php if ( $app->user ) : ?>
                  <div class="profile">
                        <img src="<?=$app->getProfile();?>" alt="profile_picture">
                        <h3><?=$app->userName(); ?></h3>
                  </div>
                  <?php endif; ?>

                  <?php 
                        wp_nav_menu( array(
                              'theme_location' => 'primary',
                              'container'      => false,
                              'menu_class'     => 'menu',
                        ) );
                  ?>
                  <!-- This history menu would be visibe only to logged in user -->
                  <?php if ( $app->user ) : ?>
                  <ul>
                        <li>
                              <a href="#" class="active with-submenu" style="text-decoration: none;">
                                    <span class="icon">
                                          <i class="fa-solid fa-timer"></i>
                                    </span>
                                    <span class="item">History</span>
                                    <i class="fa fa-caret-down" aria-hidden="true"></i>
                              </a>
                              <div class="dropdown <?= isset($_GET['history']) ? 'show' : '' ?>">
                                    <ul>
                                          <?php foreach($app->getHistory() as $history) :?>
                                          <li>
                                                <a href="?history=<?=$history?>"
                                                      class="<?= isset($_GET['history']) && $history == $_GET['history'] ? 'active' : ''?>">
                                                      <?= ucfirst($history) ?>
                                                </a>
                                          </li>
                                          <?php endforeach;?>
                                    </ul>
                              </div>
                        </li>
                  </ul>
                  <?php endif; ?>
                  <!-- clear history button -->
                  <?php if ( count($app->getHistory()) > 0) : ?>
                  <a href="?clear=all" class="clear-history">
                        <i class="fa fa-trash"></i>Clear history
                  </a>
                  <?php endif; ?>
            </div>
      </div>

      <?php wp_footer(); ?>
      <!-- Bootstrap JavaScript Libraries -->
      <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"
            integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous">
      </script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.min.js"
            integrity="sha384-7VPbUDkoPSGFnVtYi0QogXtr74QeVeeIs99Qfg5YCF+TidwNdjvaKZX19NZ/e6oz" crossorigin="anonymous">
      </script>
      <!-- Custom javascript -->
      <script src="<?=plugins_url('counter/assets/js/jquery-3.6.3.min.js'); ?>"></script>
      <script src="<?=plugins_url('counter/assets/js/filter.js'); ?>"></script>
      <script src="<?=plugins_url('counter/assets/js/custom.js'); ?>"></script>
</body>

</html>