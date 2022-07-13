<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li class="active">お知らせ</li>
        </ol>
      </div>

      <div class="row">
        <div class="well well-sm">
          <a href="/admin/notices/new" class="btn btn-primary" role="button"><span class="glyphicon glyphicon-plus"></span> 新規</a>
        </div>
      </div>

      <?php if (!empty($entities)): ?>

      <div class="row">
        <?php include(dirname(__FILE__) . '/../_pager.php') ?>
      </div>

      <div class="row">
        <table class="table table-striped table-hover table-condensed">
          <tr>
            <th>ID</th>
            <th>タイトル</th>
            <th>本文</th>
            <th>プレゼント</th>
            <th>開始時刻</th>
            <th>終了時刻</th>
            <th>プラットフォーム</th>
            <th>新規登録者</th>
            <th></th>
          </tr>
          <?php foreach ($entities as $entity): ?>
          <tr>
            <td><?= $entity->id ?></td>
            <td><?= $entity->title ?></td>
            <td>
              <?php if(mb_strlen($entity->body) > 10): ?>
                <?= mb_substr($entity->body, 0, 10, 'utf8') . ".." ?>
              <?php else: ?>
                <?= $entity->body ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($entity->zeni > 0 ||
                        $entity->food > 0 ||
                        $entity->ryo ||
                        $entity->friend_point ||
                        $entity->building_id ||
                        $entity->speciality_id ||
                        $entity->commander_id ||
                        $entity->item_id ||
                        $entity->bg_id ||
                        $entity->effect_id):
              ?>
              <span class="label label-success">あり</span>
              <?php else: ?>
              <span class="label label-default">なし</span>
              <?php endif; ?>
            </td>
            <td><?= $entity->start_at ?></td>
            <td><?= $entity->end_at ?></td>
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
            <td>
              <?php if ($entity->withoutnew == 1): ?>
                <span class="label label-warning">お知らせ期間開始後の新規ユーザー対象外</span>
              <?php elseif ($entity->withoutnew == 0): ?>
                <span class="label label-primary">お知らせ期間開始後の新規ユーザーも対象</span></span>
              <?php else: ?>
                <?= $entity->withoutnew ?>
              <?php endif; ?>
            </td>
            <td>
              <a href="/admin/notices/<?= $entity->id ?>" class="btn btn-primary btn-xs" role="button">詳細</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div class="row">
        <?php include(dirname(__FILE__) . '/../_pager.php') ?>
      </div>

      <?php endif; ?>

    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
