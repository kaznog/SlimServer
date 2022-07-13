<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
<script type="text/javascript"><!--
window.onload = function(){
    txtchg();
};
function txtchg() {
x = document.f.role.selectedIndex;  // 選択された要素index.
y = document.forms["f"].elements["role"].options[x].value;  // 選択された要素のvalue.
length = document.forms["f"].elements["role"].length;   // プルダウンの要素数.
length = (length <= y) ? y + 1 : length;    // 権限によって表示個数が変わるための対応.
    for (i = 0; i < length; i++) {
        data=(i == y) ? "block" : "none";   // 選択された要素以外は表示オフ.
        document.getElementById("disp_"+i).style.display = data;
    }
}
//--></script>

<?php include(dirname(__FILE__) . '/../_header.php') ?>
    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li>管理ユーザ招待</li>
        </ol>
      </div>
        
      <?php $myUser = \App\Services\AdminUserService::getMyAdminUser(); ?>
        
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
          <form class="form-horizontal" method="post" role="form" name="f">
            <fieldset>
              <div class="form-group">
                <label for="uid" class="col-lg-2 control-label">メールアドレス </label>
                <div class="col-lg-10">
                    <input type="text" name="uid" value="<?= $user_id ?>" class="form-control"/>
                </div>
              </div>
                <div class="form-group">
                  <label for="role" class="col-lg-2 control-label">権限レベル </label>
                    <div class="col-lg-10">
                      <select class="form-control" id="role" name="role" onChange="txtchg()">
                        <?php foreach ($roles as $idx => $role_data) :?>
                        <?php if ($idx < $myUser['role']): continue;endif; ?>
                        <option value="<?= $idx ?>"<?php if ($myUser['role'] == $idx) echo 'selected' ?>><?= $role_data['comments'] ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                </div>
              <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                  <button type="submit" class="btn btn-primary" id="submit">Send</button>
                </div>
              </div>
            </fieldset>
          </form>
        </div><!-- .span9 (main) -->
      </div><!-- .row -->
      
      <b>アクセス制限対象サービス</b>
      <form class="form-horizontal">
        <?php foreach ($roles as $idx => $role_data) :?>
        <div id="disp_<?= $idx ?>" hidden="">
        <?php echo $role_data['comments'] ?>
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
                <?php if (isset($page['pattern'])): ?><a href="<?= $page['pattern'] ?>"><?= $page['pattern'] ?></a><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
        </div>
        <?php endforeach; ?>
      </form>

    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
