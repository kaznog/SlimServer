<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li class="active">管理ツールログ</li>
        </ol>
      </div>
      
      <?php $my_user = \App\Services\AdminUserService::getMyAdminUser(); ?>
      <?php $users = \App\Services\AdminUserService::getAdminUsers(); ?>
      <?php $patterns = \App\Services\AdminUserService::getPatterns(); ?>
        
      <div class="row">
        <form action="/admin/logs" method="GET" class="form-group">
          <div class="panel panel-default" >
            <div class="panel-body">
              
              <div class="form-group col-md-10">
                <label for="rule" class="col-sm-2 control-label">検索</label>
                <input type="search" class="form-control" id="search" name="search" value="<?= $search ?>" placeholder="パターン / コメント">
              </div><!-- /form-group -->
                
              <div class="form-group col-md-10">
              <label for="rule" class="col-sm-2 control-label">ユーザ指定</label>
              <div class="col-sm-4">
                <select class="form-control" name="user_id">
                  <option value="" <?php if ($my_user['role'] > 1) echo 'disabled="disabled"' ?>>指定なし</option>
                  <?php foreach ($users as $idx => $user): ?>
                    <?php if (($my_user['role'] > 1 && $my_user['user_id'] != $user['user_id'])) continue; ?>
                    <option value="<?= $user['user_id'] ?>" <?php if ($user_id == $user['user_id']) echo 'selected="selected"' ?>><?= $user['user_id'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <label for="rule" class="col-sm-2 control-label">メソッド</label>
              <div class="col-sm-4">
                <select class="form-control" name="method">
                  <option value="">指定なし</option>
                  <option value="GET" <?php if ($method == 'GET') echo 'selected="selected"' ?>>GET</option>
                  <option value="POST" <?php if ($method == 'POST') echo 'selected="selected"' ?>>POST</option>
                  <option value="PUT" <?php if ($method == 'PUT') echo 'selected="selected"' ?>>PUT</option>
                  <option value="DELETE" <?php if ($method == 'DELETE') echo 'selected="selected"' ?>>DELETE</option>
                </select>
              </div>
              </div>
              
              <div class="form-group col-md-10">
              <label for="rule" class="col-sm-2 control-label">パターン</label>
              <div class="col-sm-8">
                <select class="form-control" name="ptn">
                  <option value="-1" selected="selected">指定なし</option>
                  <?php foreach ($patterns as $idx => $pattern): ?>
                  <option value="<?= $idx ?>" <?php if ($idx == $ptn) echo 'selected="selected"' ?>><?= $pattern['comments'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              </div>
                
              <div class="col-sm-offset-8 col-sm-8">
                <button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-search"></span> 検索</button>
              </div>
            </div>
          </div>
        </form>
      </div>
        
      <div class="row">
        <?php include(dirname(__FILE__) . '/../_pager.php') ?>
      </div>
      <div class="row">
        <?php include(dirname(__FILE__) . '/../_slider.php') ?>
      </div>
        
      <div class="row">
        <table class="table table-striped table-hover table-condensed" width="300">
          <tr>
            <th>日時</th>
            <th>ユーザ</th>
            <th>メソッド</th>
            <th>パターン</th>
            <th>追加コメント</th>
          </tr>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><?= $log['created_at'] ?></td>
              <td><?= $log['user'] ?></td>
              <td><?= $log['method'] ?></td>
              <?php if ($log['pattern_name'] != null): ?>
              <?php $comments = $log['pattern_name']['comments'] ?>
              <?php else: ?>
              <?php $comments = $log['pattern'] ?>
              <?php endif; ?>
              <td><?= $comments ?><br>(<a href="/admin<?= $log['pattern'] ?>"><?= $log['pattern'] ?></a>)</td>
              <td width="50%">
                <?php if (strpos($log['comments'], "csv:") === 0) :?>
                  <?php $filename = str_replace("csv:", '', $log['comments']) ;?>
                  <a href="/admin/logs/get_csv?filename=<?= $filename ?>"><?= $log['comments'] ?></a>
                <?php else: ?>
                  <?= $log['comments'] ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
        
      </div>
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
