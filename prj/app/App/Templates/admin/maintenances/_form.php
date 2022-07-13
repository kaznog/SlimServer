
              <div class="form-group <?php if (isset($flash['errors']['start_at:formatted'])) { echo 'has-error';} ?>">
                <label for="start_at" class="col-sm-3 control-label">開始時刻</label>
                <div class="col-sm-9">
                  <input type="datetime-local" step="1" class="form-control" id="start_at" name="start_at" value="<?= $params['start_at'] ?>" placeholder="開始時刻">
                  <?php if(isset($flash['errors']['start_at:formatted'])): ?>
                  <span class="help-block">start_at <?= $flash['errors']['start_at:formatted'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['end_at:formatted'])) { echo 'has-error';} ?>">
                <label for="end_at" class="col-sm-3 control-label">終了時刻</label>
                <div class="col-sm-9">
                  <input type="datetime-local" step="1" class="form-control" id="end_at" name="end_at" value="<?= $params['end_at'] ?>" placeholder="終了時刻">
                  <?php if(isset($flash['errors']['end_at:formatted'])): ?>
                  <span class="help-block">end_at <?= $flash['errors']['end_at:formatted'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['message'])) { echo 'has-error';} ?>">
                <label for="message" class="col-sm-3 control-label">メッセージ</label>
                <div class="col-sm-9">
                  <textarea class="form-control" rows="3" id="message" name="message" placeholder="メッセージ"><?= $params['message'] ?></textarea>
                  <?php if(isset($flash['errors']['message'])): ?>
                  <span class="help-block">message <?= $flash['errors']['message'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
