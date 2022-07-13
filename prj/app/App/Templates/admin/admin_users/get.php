<?php include(dirname(__FILE__) . '/../_header.php') ?>
    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li><a href="/admin/admin_users">管理ユーザ管理</a></li>
          <li>管理ユーザ更新</li>
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
                    <input type="text" readonly="readonly" name="uid" value="<?= $user_id ?>" class="form-control"/>
                </div>
              </div>
              <div class="form-group">
                <label for="uid" class="col-lg-2 control-label">権限レベル </label>
                <div class="col-lg-10">
                  <select class="form-control" id="role" name="role">
                    <?php $myUser = \App\Services\AdminUserService::getMyAdminUser(); ?>
                    <?php foreach ($roles as $idx => $role_data) :?>
                    <?php if ($idx < $myUser['role']): continue;endif; ?>
                    <option value="<?= $idx ?>"<?php if ($role == $idx) echo 'selected' ?>><?= $role_data['comments'] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                  <button type="submit" class="btn btn-primary" id="submit" name="action" value="update">変更</button>
                  <button type="submit" class="btn btn-danger" id="submit" name="action" value="delete">削除</button>
                </div>
              </div>
            </fieldset>
          </form>
        </div><!-- .span9 (main) -->
      </div><!-- .row -->

    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
