              <div class="form-group <?php if (isset($flash['errors']['required_version'])) { echo 'has-error';} ?>">
                <label for="required_version" class="col-sm-3 control-label">必須アプリバージョン</label>
                <div class="col-sm-9">
                  <input type="text" pattern="[0-9.]+" class="form-control" id="required_version" name="required_version" value="<?= $params['required_version'] ?>" placeholder="必須アプリバージョン">
                  <?php if(isset($flash['errors']['required_version'])): ?>
                  <span class="help-block">required_version <?= $flash['errors']['required_version'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['applying_version'])) { echo 'has-error';} ?>">
                <label for="applying_version" class="col-sm-3 control-label">申請中アプリバージョン</label>
                <div class="col-sm-9">
                  <input type="text" pattern="[0-9.]*" class="form-control" id="applying_version" name="applying_version" value="<?= $params['applying_version'] ?>" placeholder="申請中アプリバージョン">
                  <?php if(isset($flash['errors']['applying_version'])): ?>
                  <span class="help-block">applying_version <?= $flash['errors']['applying_version'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['abdb_version'])) { echo 'has-error';} ?>">
                <label for="abdb_version" class="col-sm-3 control-label">アセットバンドルDBバージョン</label>
                <div class="col-sm-9">
                  <input type="text" pattern="[0-9.]*" class="form-control" id="abdb_version" name="abdb_version" value="<?= $params['abdb_version'] ?>" placeholder="アセットバンドルDBバージョン">
                  <?php if(isset($flash['errors']['abdb_version'])): ?>
                  <span class="help-block">abdb_version <?= $flash['errors']['abdb_version'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
