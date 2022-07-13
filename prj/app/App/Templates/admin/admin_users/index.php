<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li class="active">管理ユーザ管理</li>
        </ol>
      </div>
      
      <div class="row">
        <?php include(dirname(__FILE__) . '/../_pager.php') ?>
      </div>
        
      <div class="row">
        <table class="table table-striped table-hover table-condensed">
          <tr>
            <th>ユーザ名</th>
            <th>権限レベル</th>
            <th>権限コメント</th>
            <th></th>
          </tr>
          <?php $myUser = App\Services\AdminUserService::getMyAdminUser(); ?>
          <?php foreach ($users as $idx => $user) :?>
            <?php $role = ($user['role'] < 1) ? 1 : $user['role'] ;?><!-- .権限0のユーザは1として表示する -->
            <?php if ($role < $myUser['role']) continue; ?><!-- .自分より格上は見せない -->
            <tr>
              <td><?= $user['user_id'] ?></td>
              <td><?= $role ?></td>
              <td><?= $comments[$role] ?></td>
              <td><a href="/admin/admin_users/<?= $user['user_id'] ?>" class="btn btn-primary btn-xs" role="button">変更 / 削除</a></td>
            </tr>
          <?php endforeach; ?>
        </table>
        
      </div>
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
