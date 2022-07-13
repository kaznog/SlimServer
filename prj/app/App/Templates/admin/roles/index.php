<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li class="active">権限レベル確認</li>
        </ol>
      </div>
        
      <?php $myUser = \App\Services\AdminUserService::getMyAdminUser(); ?>
      <?php $patterns = \App\Services\AdminUserService::getPatterns(); ?>
        
      <br>
      <b>アクセス制限対象サービス</b>
      <!--タブ-->
      <ul class="nav nav-tabs">
        <?php foreach ($roles as $idx => $role_data) :?>
          <?php if ($idx < $myUser['role']): continue;endif; ?>
          <li class="<?php if ($idx == $myUser['role']) echo 'active' ?>"><a href="#tab<?= $idx ?>" data-toggle="tab">権限 <?= $idx ?></a></li>
        <?php endforeach; ?>
        <li><a href="#tab_add" data-toggle="tab">追加</a></li>
      </ul>
      
      <!-- / タブ-->
      <div id="myTabContent" class="tab-content">
        <?php foreach ($roles as $idx => $role_data) :?>
        <div class="tab-pane fade <?php if ($idx == $myUser['role']) echo 'in active' ?>" id="tab<?= $idx ?>">
          <br>
          <a href="/admin/roles/<?= $idx ?>"><?= $role_data['comments'] ?></a>
          <table class="table table-striped table-hover table-condensed">
          <tr>
            <th>名称</th>
            <th>URL</th>
          </tr>
          <?php foreach ($role_data['pages'] as $page) :?>
          <tr>
            <td><?= $page['comments'] ?></td>
            <td>
              <?php if (isset($page['method'])): ?><?= $page['method'] ?><?php endif; ?>
              <?php if (isset($page['pattern'])): ?><a href="/admin<?= $page['pattern'] ?>"><?= $page['pattern'] ?></a><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </table>
        </div>
        <?php endforeach; ?>
        <div class="tab-pane fade" id="tab_add">
          <br>
          <?php
            // 新規作成ID.
            $newId = 0;
            foreach ($roles as $role) {
                if ($role['id'] != $newId) { break; }
                $newId++;
            }
          ?>
          <form class="form-horizontal" method="post" role="form">
            <div class="form-group col-md-4">
              <input type="input" class="form-control" id="comments" name="comments" value="権限 <?= $newId ?>: ">
            </div>
            <div class="form-group col-md-1">
              <input type="hidden" class="form-control" id="id" name="id" value="<?= $newId ?>">
            </div>
            <button class="btn btn-default" type="submit" name="action" value="insert_role"><span class="glyphicon glyphicon-paperclip"></span> 追加</button>
          </form>
        </div>
      </div>
        
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
