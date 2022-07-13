<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li class="active">アプリバージョン</li>
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
        <table class="table table-striped table-hover table-condensed">
          <tr>
            <th>プラットフォーム</th>
            <th>必須アプリバージョン</th>
            <th>申請中アプリバージョン</th>
            <th>アセットバンドルDBバージョン</th>
            <th></th>
          </tr>
          <tr>
            <td>iOS</td>
            <td><?= $entities[1]->required_version ?></td>
            <td><?= $entities[1]->applying_version ?></td>
            <td><?= $entities[1]->abdb_version ?></td>
            <td><a href="/admin/app_versions/1/edit" class="btn btn-primary btn-xs" role="button"><span class="glyphicon glyphicon-cog"></span> 設定</a></td>
          </tr>
          <tr>
            <td>Android</td>
            <td><?= $entities[2]->required_version ?></td>
            <td><?= $entities[2]->applying_version ?></td>
            <td><?= $entities[2]->abdb_version ?></td>
            <td><a href="/admin/app_versions/2/edit" class="btn btn-primary btn-xs" role="button"><span class="glyphicon glyphicon-cog"></span> 設定</a></td>
          </tr>
        </table>
      </div><!-- .row -->
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
