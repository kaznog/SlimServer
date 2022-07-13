<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li class="active">メンテナンス</li>
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
          <a href="/admin/maintenances/1/edit" class="btn btn-primary" role="button"><span class="glyphicon glyphicon glyphicon-cog"></span> メンテナンス設定</a>
          <?php
            $maintenanceService = new \App\Services\MaintenanceService();
            if ($maintenanceService->isUnderMaintenance()):
          ?>
          <form action="/admin/maintenances/1" method="post" role="form" style="display:inline-block">
            <input type="hidden" name="_METHOD" value="DELETE"/>
            <button type="submit" class="btn btn-success"><span class="glyphicon glyphicon glyphicon-stop"></span> メンテナンス終了</button>
          </form>
          <?php else: ?>
          <form action="/admin/maintenances/1/start" method="post" role="form" style="display:inline-block">
          <input type="hidden" name="_METHOD" value="PUT"/>
            <button type="submit" class="btn btn-warning"><span class="glyphicon glyphicon glyphicon-play"></span>メンテナンス開始</button>　特定のIPアドレスからはメンテナンス中もアクセスできます。
          </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="row">
        <table class="table table-striped table-hover table-condensed">
          <colgroup>
            <col class="col-xs-3">
            <col class="col-xs-9">
          </colgroup>
          <tr>
            <th>開始時刻</th>
            <td><?= $entity->start_at ?></td>
          </tr>
          <tr>
            <th>終了時刻</th>
            <td><?= $entity->end_at ?></td>
          </tr>
          <tr>
            <th>メッセージ</th>
            <td><?= $entity->message ?></td>
          </tr>
        </table>
      </div><!-- .row -->
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
