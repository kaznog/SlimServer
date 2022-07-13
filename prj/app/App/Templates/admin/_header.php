<!doctype html>
<html ng-app>
  <head>
    <meta charset="UTF-8">
    <title>APP Admin Tool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/admin/css/bootstrap.min.css" rel="stylesheet">
    <link href="/admin/css/battlemap.css" rel="stylesheet">
    <link href="/admin/css/slider.css" rel="stylesheet">
    <link href="/admin/css/style.css" rel="stylesheet" />
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <script src="/admin/js/bootstrap.min.js"></script>
    <script src="/admin/js/bootstrap-slider.js"></script>
    <style type="text/css">
    <!--
        body { background-image: url(/admin/img/<?= $app->mode; ?>.png); }
    -->
    </style>
  </head>
  <body class="<?= $app->mode; ?>">
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="/admin">APP Admin Tool <span class="label label-info"><?= $app->mode; ?></span></a>
        </div>

        <div class="collapse navbar-collapse navbar-ex1-collapse">
          <ul class="nav navbar-nav navbar-left">
            <li><a href="/admin/players"><span class="glyphicon glyphicon-user"></span> プレイヤー管理</a></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-list"></span> マスターデータ <b class="caret"></b></a>
              <ul class="dropdown-menu">
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-wrench"></span> 運営データ管理 <b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a href="/admin/maintenances">メンテナンス</a></li>
                <li class="divider"></li>
                <li><a href="/admin/app_versions">アプリバージョン</a></li>
                <li class="divider"></li>
                <li><a href="/admin/notices">お知らせ</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-flash"></span> API <b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a href="/admin/console/top">APIコンソール</a></li>
                <li class="divider"></li>
              </ul>
            </li>
            
            <?php if ($app->getContainer()['cookie']->get('uid') != "") :?>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-user"></span> <?php echo mb_substr($app->getContainer()['cookie']->get('uid'), 0, 10)."..."; ?> <b class="caret"></b></a>
              <ul class="dropdown-menu">
                  <?php
                    $adminUserService = new \App\Services\AdminUserService();
                    $role_idx = $adminUserService->getRole($app->getContainer()['cookie']->get('uid'));
                  ?>
                  <?php if ($role_idx == 0) :?>     <li><span class="label label-danger"> <?= $app->getContainer()['cookie']->get('uid') ?> でログイン中 </span></li>
                  <?php elseif ($role_idx == 1) :?> <li><span class="label label-warning"><?= $app->getContainer()['cookie']->get('uid') ?> でログイン中 </span></li>
                  <?php elseif ($role_idx == 2) :?> <li><span class="label label-info">   <?= $app->getContainer()['cookie']->get('uid') ?> でログイン中 </span></li>
                  <?php elseif ($role_idx == 3) :?> <li><span class="label label-success"><?= $app->getContainer()['cookie']->get('uid') ?> でログイン中 </span></li>
                  <?php elseif ($role_idx == 4) :?> <li><span class="label label-primary"><?= $app->getContainer()['cookie']->get('uid') ?> でログイン中 </span></li>
                  <?php elseif ($role_idx == 5) :?> <li><span class="label label-default"><?= $app->getContainer()['cookie']->get('uid') ?> でログイン中 </span></li>
                  <?php else :?>                    <li><span class="label label-default"><?= $app->getContainer()['cookie']->get('uid') ?> でログイン中 </span></li>
                  <?php endif; ?>
                <li><a href="/admin/login">再ログイン</a></li>
                <li><a href="/admin/password">パスワード変更</a></li>
                <li class="divider"></li>
                <li><a href="/admin/invitation">管理ユーザ招待</a></li>
                <li><a href="/admin/admin_users">管理ユーザ管理</a></li>
                <li class="divider"></li>
                <li><a href="/admin/roles">権限レベル確認</a></li>
                <li><a href="/admin/logs">管理ツールログ</a></li>
                <li><a href="#">...</a></li>
              </ul>
            </li>
            <?php endif; ?>
            
          </ul>
        </div>
      </div>
    </nav>

    <?php
      $maintenanceService = new \App\Services\MaintenanceService();
      if ($maintenanceService->isUnderMaintenance()):
    ?>
    <div class="container">
      <div class="row">
        <div class="alert alert-warning">
          <span class="glyphicon glyphicon glyphicon-warning-sign"></span> <strong>メンテナンス中です!</strong>
          <a href="/admin/maintenances/1" class="btn btn-warning" role="button" style="margin-left: 10px"><span class="glyphicon glyphicon glyphicon-cog"></span> 設定</a>
        </div>
      </div>
    </div><!-- .container -->
    <?php endif; ?>
