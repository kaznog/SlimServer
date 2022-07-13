<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
<script type="text/javascript"><!--
$(function () {
  $('#all_check').click(function(){
    if($("input:checkbox[id=all_check]").prop('checked') == true){
      $("input:checkbox[id='check']").prop({'checked':true});
    }else{
      $("input:checkbox[id='check']").prop({'checked':false});
    }
  });
});
//--></script>

<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li><a href="/admin/roles">権限レベル確認</a></li>
          <li class="active"><?= $role['comments'] ?></li>
        </ol>
      </div>
      
      <?php $patterns = \App\Services\AdminUserService::getPatterns(); ?>
      <?php $roles = \App\Services\AdminUserService::getRoles(); ?>
      
      <form class="form-horizontal" action="/admin/roles" method="post">
        <div class="form-group col-md-1">名称 </div>
        <div class="form-group col-md-4">
          <input type="input" class="form-control" id="comments" name="comments" value="<?= $role['comments'] ?>">
          <input type="hidden" class="form-control" id="id" name="id" value="<?= $role['id'] ?>">
        </div>
        <div class="form-group col-md-1"></div>
        <button class="btn btn-default" type="submit" name="action" value="update_comments"><span class="glyphicon glyphicon-paperclip"></span> 名称変更</button>
        <button class="btn btn-danger" data-toggle="modal" data-target="#modal-lg"><span class="glyphicon glyphicon-trash"></span> 削除</button>
      </form>
      
      <br>
      <form class="form-horizontal" action="/admin/roles" method="post">
        <div class="form-group col-md-3">制限対象サービス設定をコピーする </div>
        <div class="form-group col-md-4">
          <select class="form-control" id="src" name="src">
            <option value="-1" selected>コピー元</option>
            <?php foreach ($roles as $tmp_role): ?>
              <?php if ($tmp_role['id'] == 0) continue; ?>
              <option value="<?= $tmp_role['id'] ?>"><?= $tmp_role['comments'] ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" class="form-control" id="id" name="id" value="<?= $tmp_role['id'] ?>">
        </div>
        <div class="form-group col-md-1"></div>
        <button class="btn btn-default" type="submit" name="action" value="copy_ignore_pages"><span class="glyphicon glyphicon-paperclip"></span> コピー</button>
      </form>
      
      <br>
      <b>アクセス制限対象サービス</b>
      <form class="form-horizontal" action="/admin/roles" method="post">
      <table class="table table-striped table-hover table-condensed">
        <tr>
          <th><input type="checkbox" id="all_check" tabindex="1" style="width:18px;height:18px"></th>
          <th>名称</th>
          <th>URL</th>
        </tr>
        <?php foreach ($patterns as $pattern) :?>
        <tr>
          <td>
            <!-- ignore設定されているかの確認. -->
            <?php $exsist = false; ?>
            <?php foreach ($role['pages'] as $idx => $page) :?>
              <?php
                if ($page['method'] == $pattern['method'] && $page['pattern'] == $pattern['pattern']) {
                    $exsist = true;
                    break;
                }
              ?>
            <?php endforeach; ?>
            <input type="checkbox" id="check" name="check_<?= $pattern['id'] ?>" <?php if ($exsist == true) echo 'checked' ?> tabindex="1">
          </td>
          <td><?= $pattern['comments'] ?></td>
          <td><?= $pattern['method'] ?> <a href="/admin<?= $pattern['pattern'] ?>" tabindex="2"><?= $pattern['pattern'] ?></a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <input type="hidden" class="form-control" id="id" name="id" value="<?= $role['id'] ?>">
      <button class="btn btn-default" type="submit" name="action" value="update_ignore_pages"><span class="glyphicon glyphicon-paperclip"></span> 設定変更</button>
      </form>
    
      <form class="form-horizontal"  action="/admin/roles" method="post">
			  <div class="modal fade" id="modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">閉じる</span></button>
                <h4 class="modal-title" id="myLargeModalLabel">削除確認</h4>
              </div>
              <div class="modal-body"><p>
                  未使用の権限かつ、一番最後の権限のみ削除が可能です。<br>
                  削除しますか？<br>
              </p></div>
              <div class="modal-footer">
                <input type="hidden" class="form-control" id="id" name="id" value="<?= $role['id'] ?>">
                <button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>
                <button class="btn btn-danger" type="submit" name="action" value="delete_role"><span class="glyphicon glyphicon-trash"></span> 削除</button>
              </div>
            </div>
          </div>
        </div>
        <br>
      </form>
        
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
