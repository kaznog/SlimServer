<?php include(dirname(__FILE__) . '/_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li>Home</li>
        </ol>
      </div>

      <div class="row">
        <div class="col-sm-8 col-md-9">
          <?php if (isset($flash["success"][0])): ?>
          <div class="row">
            <div class="alert alert-success">
              <?= $flash["success"][0] ?>
            </div>
          </div>
          <?php endif; ?>
          <?php if (isset($flash["error"][0])): ?>
          <div class="row">
            <div class="alert alert-danger">
              <?= $flash["error"][0] ?>
            </div>
          </div>
          <?php endif; ?>
        </div><!-- .span9 (main) -->
      </div><!-- .row -->

    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/_footer.php') ?>
