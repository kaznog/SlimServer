<?php include(dirname(__FILE__) . '/_header.php') ?>
    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li>ログイン</li>
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
        <div class="col-sm-8 col-md-9">
          <form class="form-horizontal" method="post" role="form">
            <fieldset>
              <div class="form-group">
                <label for="uid" class="col-lg-2 control-label">メールアドレス </label>
                <div class="col-lg-10">
                    <input type="text" name="uid" value="<?= $user_id ?>" class="form-control"/>
                </div>
              </div>
              <div class="form-group">
                <label for="pass" class="col-lg-2 control-label">パスワード</label>
                <div class="col-lg-10">
                  <input type="password" name="pass" value="" class="form-control"/>
                </div>
              </div>
              <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                  <button type="submit" class="btn btn-primary" id="submit">ログイン</button>
                </div>
              </div>
            </fieldset>
          </form>
        </div><!-- .span9 (main) -->
      </div><!-- .row -->

    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/_footer.php') ?>
