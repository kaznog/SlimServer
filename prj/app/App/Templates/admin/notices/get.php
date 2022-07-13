<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li><a href="/admin/notices">お知らせ</a></li>
          <li class="active">ID: <?= $id ?></li>
        </ol>
      </div>

      <?php if (isset($flash["success"][0])): ?>
      <div class="row">
        <div class="alert alert-success">
          <?= $flash["success"][0] ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="row">
        <div class="well well-sm">
          <a href="/admin/notices/<?= $id ?>/edit" class="btn btn-primary" role="button"><span class="glyphicon glyphicon-cog"></span> 編集</a>
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
            <td><?= $id ?></td>
          </tr>
          <tr>
            <th>タイトル</th>
            <td><?= $entity->title ?></td>
          </tr>
          <tr>
            <th>本文</th>
            <td>
              <pre style="border:none;padding:0;margin:0;background:none"><?= $entity->body ?></pre>
            </td>
          </tr>
          <tr>
            <th>銭</th>
            <td><?= $entity->zeni ?></td>
          </tr>
          <tr>
            <th>米</th>
            <td><?= $entity->food ?></td>
          </tr>
          <tr>
            <th>宝玉</th>
            <td><?= $entity->ryo ?></td>
          </tr>
          <tr>
            <th>盟友ポイント</th>
            <td><?= $entity->friend_point ?></td>
          </tr>
          <tr>
            <th>建物ID</th>
            <td><?= $entity->building_id ?> <?php if ($building) { echo "( {$building->name} )"; } ?></td>
          </tr>
          <tr>
            <th>特産品ID</th>
            <td><?= $entity->speciality_id ?> <?php if ($speciality) { echo "( {$speciality->name} )"; } ?></td>
          </tr>
          <tr>
            <th>カードID</th>
            <td><?= $entity->commander_id ?> <?php if ($commander) { echo "( {$commander->name} )"; } ?></td>
          </tr>
          <tr>
            <th>アイテムID</th>
            <td><?= $entity->item_id ?> <?php if ($item) { echo "( {$item->name} )"; } ?></td>
          </tr>
          <tr>
            <th>背景</th>
            <td><?= $entity->bg_id ?></td>
          </tr>
          <tr>
            <th>演出</th>
            <td><?= $entity->effect_id ?></td>
          </tr>
          <tr>
            <th>プラットフォーム</th>
            <td>
              <?php if ($entity->platform == \App\Models\Platform::PLATFORM_IOS): ?>
                <span class="label label-default">iOS</span>
              <?php elseif ($entity->platform == \App\Models\Platform::PLATFORM_ANDROID): ?>
                <span class="label label-default">Android</span></span>
              <?php elseif ($entity->platform == 0): ?>
                <span class="label label-default">iOS/Android</span></span>
              <?php else: ?>
                <?= $entity->platform ?>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th>新規登録者</th>
            <td>
              <?php if ($entity->withoutnew == 1): ?>
                <span class="label label-warning">お知らせ期間開始後の新規ユーザー対象外</span>
              <?php elseif ($entity->withoutnew == 0): ?>
                <span class="label label-primary">お知らせ期間開始後の新規ユーザーも対象</span></span>
              <?php else: ?>
                <?= $entity->withoutnew ?>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th>開始時刻</th>
            <td><?= $entity->start_at ?></td>
          </tr>
          <tr>
            <th>終了時刻</th>
            <td><?= $entity->end_at ?></td>
          </tr>
        </table>
      </div><!-- .row -->
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
