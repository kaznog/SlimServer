<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li class="active">プレイヤー</li>
        </ol>
      </div>

      <div class="row">
        <form role="form" action="/admin/players" method="GET">
          <div class="panel panel-default">
            <div class="panel-body">
              <div class="form-group col-md-12">
                <input type="search" class="form-control" id="q" name="q" value="<?= $q ?>" placeholder="検索キーワード: id, 名前など">
              </div><!-- /form-group -->
              <div class="form-group col-md-4">
                <input type="number" class="form-control" id="fv" name="fv" value="<?= $fv ?>" min="1" placeholder="レベル">
              </div><!-- /form-group -->
              <div class="form-group col-md-4">
                <select class="form-control" id="fp" name="fp">
                  <option value="0"<?php if ($fp == 0) echo 'selected' ?>>プラットフォーム</option>
                  <option value="1"<?php if ($fp == 1) echo 'selected' ?>>iOS</option>
                  <option value="2"<?php if ($fp == 2) echo 'selected' ?>>Android</option>
                </select>
              </div><!-- /form-group -->
              <div class="form-group col-md-3 col-md-offset-10">
                <button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-search"></span> 検索</button>
                <button class="btn btn-default" type="button"><a href="/admin/players">リセット</a></button>
              </div>
            </div><!-- /panel-body -->
          </div><!-- /panel -->
        </form>
      </div>

      <?php if (!empty($playersIdentities)): ?>

      <div class="row">
        <?php include(dirname(__FILE__) . '/../_slider.php') ?>
      </div>

      <div class="row">
        <table class="table table-striped table-hover table-condensed">
          <tr>
            <th>ID</th>
            <th>名前</th>
            <th>性別</th>
            <th>レベル</th>
            <th>プラットフォーム</th>
            <th>最終ログイン時刻</th>
            <th>登録時刻</th>
            <th></th>
          </tr>
          <?php foreach ($playersIdentities as $playersIdentity): ?>
          <tr>
            <td><?= $playersIdentity->id ?></td>
            <td><?= $playersIdentity->name ?></td>
            <td>
              <?php if ($playersIdentity->gender == \App\Models\PlayersIdentity::GENDER_MALE): ?>
                <span class="label label-info">男性</span>
              <?php elseif ($playersIdentity->gender == \App\Models\PlayersIdentity::GENDER_FEMALE): ?>
                <span class="label label-warning">女性</span></span>
              <?php endif; ?>
            </td>
            <td><?= $playersIdentity->level ?></td>
            <td>
              <?php if ($playersIdentity->device_platform == \App\Models\Platform::PLATFORM_IOS): ?>
                <span class="label label-default">iOS</span>
              <?php elseif ($playersIdentity->device_platform == \App\Models\Platform::PLATFORM_ANDROID): ?>
                <span class="label label-default">Android</span></span>
              <?php endif; ?>
            </td>
            <td><?= $playersIdentity->last_login_at ?></td>
            <td><?= $playersIdentity->created_at ?></td>
            <td>
              <a href="/admin/players/<?= $playersIdentity->id ?>" class="btn btn-primary btn-xs" role="button">詳細</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <?php endif; ?>

    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
