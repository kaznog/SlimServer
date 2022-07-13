<?php include(dirname(__FILE__) . '/../_header.php') ?>

    <div class="container" id="api-container" ng-controller="ApiConsoleCtrl">
      <div class="row">
        <div class="col-sm-4 col-md-3" id="api-list">
          <div class="panel-group" id="api">
            <div class="panel panel-default" ng-repeat="group in apis.groups">
              <div class="panel-heading">
                <h4 class="panel-title">
                  <a class="accordion-toggle" data-toggle="collapse" data-parent="#api" href="#api-group-{{$index}}">
                    {{group.name}}
                  </a>
                </h4>
              </div><!-- .panel-heading -->
              <div id="api-group-{{$index}}" class="panel-collapse collapse">
                <div class="list-group">
                  <a ng-repeat="api in group.contents" class="list-group-item {{activeApiClass(api)}}" ng-click="setApi(api)">
                    <span class="label label-default" ng-show="apis[api].loginNotRequired">ログイン不要</span>
                    {{apis[api].title}}
                  </a>
                </div>
              </div>
            </div>
          </div><!-- .panel-group -->
        </div><!-- .span3 (sidebar) -->

        <div class="col-sm-8 col-md-9" id="api-content">
          <form class="form-horizontal">
            <fieldset>
              <div class="form-group">
                <label for="url" class="col-lg-2 control-label">URL</label>
                <div class="col-lg-10">
                  <input type="text" ng-model="api.url" name="url" value="" class="form-control"/>
                </div>
              </div>
              <div class="form-group">
                <label for="method" class="col-lg-2 control-label">Method</label>
                <div class="col-lg-10">
                  <label class="radio-inline"><input type="radio" ng-model="api.method" name="method" value="GET" />GET</label>
                  <label class="radio-inline"><input type="radio" ng-model="api.method" name="method" value="POST" />POST</label>
                </div>
              </div>
              <div class="form-group">
                <label for="sessionId" class="col-lg-2 control-label">Session ID</label>
                <div class="col-lg-10">
                  <input type="text" ng-model="sessionId" name="sessionId" class="form-control"/>
                </div>
              </div>
              <div class="form-group">
                <label for="requestHash" class="col-lg-2 control-label">Request Hash</label>
                <div class="col-lg-10" style="word-break: break-all;">
                  <input type="text" name="requestHash" class="form-control col-lg-10" value="{{requestHash()}}"/>
                  <span class="help-block alert-warning"><span class="label label-warning">Base String</span> {{baseString()}}</span>
                </div>
              </div>
              <div class="form-group">
                <label for="json" class="col-lg-2 control-label">JSON</label>
                <div class="col-lg-10">
                  <textarea ng-model="api.json" ng-disabled="api.method=='GET'" name="json" rows="3" class="form-control"></textarea>
                </div>
              </div>
              <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                  <button ng-click="callApi()" type="submit" class="btn btn-primary" id="submit">Submit</button>
                </div>
              </div>
            </fieldset>
          </form>

          <div class="tabbable">
            <ul class="nav nav-tabs">
              <li class="active">
                <a href="#response-pane" data-toggle="tab">Response</a>
              </li>
              <li>
                <a href="#error-pane" data-toggle="tab">Error</a>
              </li>
            </ul>
            <div class="tab-content">
              <div class="tab-pane active" id="response-pane">
                <pre ng-model="response.json" id="response" class="alert {{response.statusClass}}" ng-show="response.result">{{response.result}}</pre>
              </div>
              <div class="tab-pane" id="error-pane">
                <pre id="error" class="alert alert-danger" ng-show="response.error">{{response.error}}</pre>
                <!--
                <pre id="error-dump" class="alert" ng-show="response.errorDump">{{response.errorDump}}</pre>
                -->
                <div ng-bind-html="response.errorDump">
                <div><span ng-bind-html-unsafe="response.errorDump"></span></div>
              </div>
            </div>
          </div>

        </div><!-- .span9 (main) -->
      </div><!-- .row -->
    </div><!-- .container -->

    <script>
      var hashSecret = "<?= $app->getContainer()['settings']['requesthash.secret']; ?>";
    </script>
    <script src="/admin/js/md5-min.js"></script>
    <script src="/admin/js/api-console.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/angularjs/1.0.2/angular.min.js"></script>

<?php include(dirname(__FILE__) . '/../_footer.php') ?>
