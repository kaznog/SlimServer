<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li><a href="/admin/players">プレイヤー</a></li>
          <li class="active">ID: <?= $playerId ?></li>
        </ol>
      </div>

      <div class="row">
        <?php include(dirname(__FILE__) . '/_sidebar.php') ?>

        <div class="col-sm-8 col-md-9">
          <?php if (isset($flash["success"])): ?>
          <div class="row">
            <div class="alert alert-success">
              <?= $flash["success"][0] ?>
            </div>
          </div>
          <?php endif; ?>

          <div class="row">
            <div class="well well-sm">
              <a href="/admin/players/<?= $playerId ?>/edit" class="btn btn-primary" role="button"><span class="glyphicon glyphicon-cog"></span> 編集</a>
            </div>
          </div>

          <div class="row">
            <table class="table table-striped table-hover table-condensed">
              <colgroup>
                <col class="col-xs-3">
                <col class="col-xs-9">
              </colgroup>
              <tr>
                <th>ID</th>
                <td><?= $playerId ?> (<?= $scrambledId ?>)</td>
              </tr>
              <tr>
                <th>シャード</th>
                <td>
                <?php
                  $servers = ['shard_000', 'shard_001', 'shard_002', 'shard_003'];
                  $n = $playerId % 4;
                  echo sprintf("shard_%03d (%s)", $n, $servers[$n]);
                ?>
                </td>
              </tr>
              <tr>
                <th>名前</th>
                <td><?= $playersIdentity->name ?></td>
              </tr>
              <tr>
                <th>性別</th>
                <td>
                  <?php if ($playersIdentity->gender == \App\Models\PlayersIdentity::GENDER_MALE): ?>
                    <span class="label label-info">男性</span>
                  <?php elseif ($playersIdentity->gender == \App\Models\PlayersIdentity::GENDER_FEMALE): ?>
                    <span class="label label-warning">女性</span></span>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <th>User ID</th>
                <td><?= $playersIdentity->user_id ?></td>
              </tr>
              <tr>
                <th>招待ID</th>
                <td><?= $playersIdentity->invitation_id ?></td>
              </tr>
              <tr>
                <th>プラットフォーム</th>
                <td>
                  <?php if ($playersIdentity->device_platform == \App\Models\Platform::PLATFORM_IOS): ?>
                    <span class="label label-default">iOS</span>
                  <?php elseif ($playersIdentity->device_platform == \App\Models\Platform::PLATFORM_ANDROID): ?>
                    <span class="label label-default">Android</span></span>
                  <?php endif; ?>
              </tr>
              <tr>
                <th>プッシュ通知用ID</th>
                <td><?= $playersIdentity->push_registration_id ?></td>
              </tr>
              <tr>
                <th>レベル</th>
                <td><?= $player->level ?></td>
              </tr>
              <tr>
                <th>経験値</th>
                <td><?= $player->exp ?> (現在のレベルでの経験値: <?= $player->level_exp ?>)</td>
              </tr>
              <tr>
                <th>フレンドポイント</th>
                <td><?= $player->friend_point ?></td>
              </tr>
              <tr>
                <th>いいねポイント</th>
                <td><?= $player->liked ?></td>
              </tr>
              <tr>
                <th>フレンド数上限増分</th>
                <td><?= $player->extra_friend_capacity ?></td>
              </tr>
            </table>

            <table class="table table-striped table-hover table-condensed">
              <colgroup>
                <col class="col-xs-3">
                <col class="col-xs-9">
              </colgroup>
              <tr>
                <th>最終ログイン日の最初のログイン時刻</th>
                <td><?= $playersIdentity->last_login_at ?></td>
              </tr>
              <tr>
                <th>最終ログイン時刻</th>
                <td><?= $player->last_login_at ?></td>
              </tr>
              <tr>
                <th>実プレイ日数</th>
                <td><?= $player->login_count ?></td>
              </tr>
              <tr>
                <th>ログイン継続日数</th>
                <td><?= $player->login_streak_count ?></td>
              </tr>
              <tr>
                <th>登録時刻</th>
                <td><?= $playersIdentity->created_at ?></td>
              </tr>
              <tr>
                <th>最終保存時刻</th>
                <td><?= isset($playersSaves->updated_at) ? $playersSaves->updated_at : "なし" ?></td>
              </tr>
              <tr>
                <th>アカウント状態</th>
                <td>
                  <?php if ($playersIdentity->status == 0): ?>[通常]<?php endif; ?>
                  <?php if ($playersIdentity->status & \App\Models\PlayersIdentity::STATUS_FLAG_BAN): ?>[凍結]<?php endif; ?>
                  <?php if ($playersIdentity->status & \App\Models\PlayersIdentity::STATUS_FLAG_TUTORIAL_CLEAR): ?>[未使用フラグ1]<?php endif; ?>
                </td>
              </tr>

              <tr>
                <th>端末、OSバージョン</th>
                <td><?= $playersIdentity->useragent ?></td>
              </tr>
              <tr>
                <th>セッションID（参考）</th>
                <td><?= $player->session_id ?></td>
              </tr>
            </table>
          </div>
        </div><!-- .span9 (main) -->
      </div><!-- .row -->
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>