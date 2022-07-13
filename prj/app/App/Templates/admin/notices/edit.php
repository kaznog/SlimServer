<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container">
      <div class="row">
        <ol class="breadcrumb">
          <li><a href="/admin">Home</a></li>
          <li><a href="/admin/notices">お知らせ</a></li>
          <li><a href="/admin/notices/<?= $id ?>">ID: <?= $id ?></a></li>
          <li class="active">編集</li>
        </ol>
      </div>

      <?php if (isset($flash["errors"])): ?>
      <div class="row">
        <div class="alert alert-danger">
          入力内容にエラーがあります.
        </div>
      </div>
      <?php endif; ?>

      <div class="row">
        <form action="/admin/notices/<?= $id ?>" method="post" class="form-horizontal" role="form">
          <div class="form-group">
            <label for="id" class="col-sm-3 control-label">ID</label>
            <div class="col-sm-9">
              <p class="form-control-static"><?= $id ?></p>
            </div>
          </div>

          <?php include(dirname(__FILE__) . '/_form.php') ?>

          <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
              <input type="hidden" name="_METHOD" value="PUT"/>
              <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-save"></span> 保存</button>
            </div>
          </div>
        </form>
      </div><!-- .row -->
    </div><!-- .container -->

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
