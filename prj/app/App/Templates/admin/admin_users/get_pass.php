<?php include(dirname(__FILE__) . '/../_header.php') ?>
    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li>パスワード変更</li>
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
        <div class="col-sm-10 col-md-10">
          <form class="form-horizontal" method="post" role="form">
            <fieldset>
              <div class="form-group">
                <label for="user_id" class="col-lg-2 control-label">メールアドレス </label>
                <div class="col-lg-10">
                    <input type="text" readonly="readonly" name="user_id" value="<?= $user_id ?>" class="form-control"/>
                </div>
              </div>
              <div class="form-group">
                <label for="pass_now" class="col-lg-2 control-label">現在のパスワード</label>
                <div class="col-lg-10">
                    <input type="password" name="pass_now" value="<?= $pass_now ?>" class="form-control"/>
                </div>
              </div>
              <div class="form-group">
                <label for="pass1" class="col-lg-2 control-label">パスワード</label>
                <div class="col-lg-10">
                    <input type="password" name="pass1" value="<?= $pass1 ?>" class="form-control"/>
                </div>
                <label for="pass2" class="col-lg-2 control-label">パスワード(確認)</label>
                <div class="col-lg-10">
                    <input type="password" name="pass2" value="<?= $pass2 ?>" class="form-control"/>
                </div>
              </div>
              <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                  <button type="submit" class="btn btn-primary" id="submit">変更</button>
                </div>
              </div>
            </fieldset>
          </form>
        </div><!-- .span9 (main) -->
      </div><!-- .row -->

    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
