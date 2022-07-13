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
          <a class="navbar-brand" href="#">APP Admin Tool <span class="label label-info"><?= $app->mode; ?></span></a>
        </div>

        
      </div>
    </nav>
