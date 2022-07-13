<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li><a href="/admin/players">プレイヤー</a></li>
          <li><a href="/admin/players/<?= $playerId ?>">ID: <?= $playerId ?></a></li>
          <li class="active">編集</li>
        </ol>
      </div>

      <div class="row">
        <?php include(dirname(__FILE__) . '/_sidebar.php') ?>

        <div class="col-sm-8 col-md-9">
          <?php if (isset($flash["errors"])): ?>
          <div class="row">
            <div class="alert alert-danger">
              入力内容にエラーがあります.
            </div>
          </div>
          <?php endif; ?>

          <div class="row">
            <form action="/admin/players/<?= $playerId ?>" method="POST" class="form-horizontal" role="form">
              <div class="form-group">
                <label for="id" class="col-sm-3 control-label">ID</label>
                <div class="col-sm-9">
                  <p class="form-control-static"><?= $playerId ?></p>
                </div>
              </div>
              <div class="form-group">
                <label for="shard" class="col-sm-3 control-label">シャード</label>
                <div class="col-sm-9">
                  <p class="form-control-static">
                  <?php
                    $servers = ['shard_000', 'shard_001', 'shard_002', 'shard_003'];
                    $n = $playerId % 4;
                    echo sprintf("shard_%03d (%s)", $n, $servers[$n]);
                  ?>
                  </p>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['name'])) { echo 'has-error';} ?>">
                <label for="name" class="col-sm-3 control-label">名前</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="name" name="name" value="<?= $params['name'] ?>" placeholder="名前">
                  <?php if(isset($flash['errors']['name'])): ?>
                  <span class="help-block">name <?= $flash['errors']['name'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['gender'])) { echo 'has-error';} ?>">
                <label for="gender" class="col-sm-3 control-label">性別</label>
                <div class="col-sm-9">
                  <select class="form-control" name="gender">
                    <option value="1" <?php if ($params['gender'] == 1) { echo 'selected="selected"';} ?>>男性</option>
                    <option value="2" <?php if ($params['gender'] == 2) { echo 'selected="selected"';} ?>>女性</option>
                  </select>
                  <?php if(isset($flash['errors']['gender'])): ?>
                  <span class="help-block">gender <?= $flash['errors']['gender'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group">
                <label for="uuid" class="col-sm-3 control-label">User ID</label>
                <div class="col-sm-9">
                  <p class="form-control-static"><?= $playersIdentity->user_id ?></p>
                </div>
              </div>
              <div class="form-group">
                <label for="invitation_id" class="col-sm-3 control-label">招待ID</label>
                <div class="col-sm-9">
                  <p class="form-control-static"><?= $playersIdentity->invitation_id ?></p>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['device_platform'])) { echo 'has-error';} ?>">
                <label for="device_platform" class="col-sm-3 control-label">プラットフォーム</label>
                <div class="col-sm-9">
                  <select class="form-control" name="device_platform">
                    <option value="1" <?php if ($params['device_platform'] == 1) { echo 'selected="selected"';} ?>>iOS</option>
                    <option value="2" <?php if ($params['device_platform'] == 2) { echo 'selected="selected"';} ?>>Android</option>
                  </select>
                  <?php if(isset($flash['errors']['device_platform'])): ?>
                  <span class="help-block">device_platform <?= $flash['errors']['device_platform'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['push_registration_id'])) { echo 'has-error';} ?>">
                <label for="push_registration_id" class="col-sm-3 control-label">プッシュ通知用ID</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="push_registration_id" name="push_registration_id" value="<?= $params['push_registration_id'] ?>" placeholder="プッシュ通知用ID">
                  <?php if(isset($flash['errors']['push_registration_id'])): ?>
                  <span class="help-block">push_registration_id <?= $flash['errors']['push_registration_id'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['level'])) { echo 'has-error';} ?>">
                <label for="level" class="col-sm-3 control-label">レベル</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="level" name="level" value="<?= $params['level'] ?>" placeholder="レベル">
                  <?php if(isset($flash['errors']['level'])): ?>
                  <span class="help-block">level <?= $flash['errors']['level'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['level_exp'])) { echo 'has-error';} ?>">
                <label for="level_exp" class="col-sm-3 control-label">現在のレベルでの経験値</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="level_exp" name="level_exp" value="<?= $params['level_exp'] ?>" placeholder="現在のレベルでの経験値">
                  <?php if(isset($flash['errors']['level_exp'])): ?>
                  <span class="help-block">level_exp <?= $flash['errors']['level_exp'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['friend_point'])) { echo 'has-error';} ?>">
                <label for="friend_point" class="col-sm-3 control-label">フレンドポイント</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="friend_point" name="friend_point" value="<?= $params['friend_point'] ?>" placeholder="盟友ポイント">
                  <?php if(isset($flash['errors']['friend_point'])): ?>
                  <span class="help-block">friend_point <?= $flash['errors']['friend_point'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['liked'])) { echo 'has-error';} ?>">
                <label for="liked" class="col-sm-3 control-label">いいねポイント</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="liked" name="liked" value="<?= $params['liked'] ?>" placeholder="いいねポイント">
                  <?php if(isset($flash['errors']['liked'])): ?>
                  <span class="help-block">liked <?= $flash['errors']['liked'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['extra_friend_capacity'])) { echo 'has-error';} ?>">
                <label for="extra_friend_capacity" class="col-sm-3 control-label">フレンド数上限増分</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="extra_friend_capacity" name="extra_friend_capacity" value="<?= $params['extra_friend_capacity'] ?>" placeholder="フレンド数上限増分">
                  <?php if(isset($flash['errors']['extra_friend_capacity'])): ?>
                  <span class="help-block">extra_friend_capacity <?= $flash['errors']['extra_friend_capacity'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['total_movement'])) { echo 'has-error';} ?>">
                <label for="total_movement" class="col-sm-3 control-label">総移動距離</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="total_movement" name="total_movement" value="<?= $params['total_movement'] ?>" placeholder="総移動距離">
                  <?php if(isset($flash['errors']['total_movement'])): ?>
                  <span class="help-block">total_movement <?= $flash['errors']['total_movement'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
               <div class="form-group <?php if (isset($flash['errors']['last_checkin_at:formatted'])) { echo 'has-error';} ?>">
                <label for="last_checkin_at" class="col-sm-3 control-label">最終チェックイン時刻</label>
                <div class="col-sm-9">
                  <input type="datetime-local" step="1" class="form-control" id="last_checkin_at" name="last_checkin_at" value="<?= $params['last_checkin_at'] ?>" placeholder="最終チェックイン時刻">
                  <?php if(isset($flash['errors']['last_checkin_at:formatted'])): ?>
                  <span class="help-block">last_checkin_at <?= $flash['errors']['last_checkin_at:formatted'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['last_login_at_today:formatted'])) { echo 'has-error';} ?>">
                <label for="last_login_at_today" class="col-sm-3 control-label">最終ログイン日の最初のログイン時刻</label>
                <div class="col-sm-9">
                  <input type="datetime-local" step="1" class="form-control" id="last_login_at_today" name="last_login_at_today" value="<?= $params['last_login_at_today'] ?>" placeholder="最終ログイン日の最初のログイン時刻">
                  <?php if(isset($flash['errors']['last_login_at_today:formatted'])): ?>
                  <span class="help-block">last_login_at_today <?= $flash['errors']['last_login_at_today:formatted'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['last_login_at:formatted'])) { echo 'has-error';} ?>">
                <label for="last_login_at" class="col-sm-3 control-label">最終ログイン時刻</label>
                <div class="col-sm-9">
                  <input type="datetime-local" step="1" class="form-control" id="last_login_at" name="last_login_at" value="<?= $params['last_login_at'] ?>" placeholder="最終ログイン時刻">
                  <?php if(isset($flash['errors']['last_login_at:formatted'])): ?>
                  <span class="help-block">last_login_at <?= $flash['errors']['last_login_at:formatted'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['login_count'])) { echo 'has-error';} ?>">
                <label for="login_count" class="col-sm-3 control-label">実プレイ日数</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="login_count" name="login_count" value="<?= $params['login_count'] ?>" placeholder="実プレイ日数">
                  <?php if(isset($flash['errors']['login_count'])): ?>
                  <span class="help-block">login_count <?= $flash['errors']['login_count'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['login_streak_count'])) { echo 'has-error';} ?>">
                <label for="login_streak_count" class="col-sm-3 control-label">ログイン継続日数</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="login_streak_count" name="login_streak_count" value="<?= $params['login_streak_count'] ?>" placeholder="ログイン継続日数">
                  <?php if(isset($flash['errors']['login_streak_count'])): ?>
                  <span class="help-block">login_streak_count <?= $flash['errors']['login_streak_count'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['status'])) { echo 'has-error';} ?>">
                <label for="status" class="col-sm-3 control-label">アカウント状態</label>
                <div class="col-sm-9">
                  <select class="form-control" name="status">
                    <option value="0" <?php if ($params['status'] == 0) { echo 'selected="selected"';} ?>>[通常]</option>
                    <option value="1" <?php if ($params['status'] == 1) { echo 'selected="selected"';} ?>>[凍結]</option>
                    <option value="2" <?php if ($params['status'] == 2) { echo 'selected="selected"';} ?>>[未使用フラグ1]</option>
                    <option value="3" <?php if ($params['status'] == 3) { echo 'selected="selected"';} ?>>[凍結][未使用フラグ1]</option>
                  </select>
                  <?php if(isset($flash['errors']['status'])): ?>
                  <span class="help-block">status <?= $flash['errors']['status'] ?></span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="form-group">
                <div class="col-sm-offset-3 col-sm-9">
                  <input type="hidden" name="_METHOD" value="PUT"/>
                  <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-save"></span> 保存</button>
                </div>
              </div>
            </form>
          </div>
        </div><!-- .span9 (main) -->
      </div><!-- .row -->
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>