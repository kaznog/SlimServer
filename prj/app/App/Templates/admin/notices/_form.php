              <div class="form-group <?php if (isset($flash['errors']['title'])) { echo 'has-error';} ?>">
                <label for="title" class="col-sm-3 control-label">タイトル（プレゼントに付くテキスト）</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="title" name="title" value="<?= $params['title'] ?>" placeholder="タイトル">
                  <?php if(isset($flash['errors']['title'])): ?>
                  <span class="help-block">title <?= $flash['errors']['title'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['body'])) { echo 'has-error';} ?>">
                <label for="body" class="col-sm-3 control-label">本文<br>（プレゼント未登録時のメール本文）</label>
                <div class="col-sm-9">
                  <textarea class="form-control" rows="3" id="body" name="body" placeholder="本文"><?= $params['body'] ?></textarea>
                  <?php if(isset($flash['errors']['body'])): ?>
                  <span class="help-block">body <?= $flash['errors']['body'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['friend_point'])) { echo 'has-error';} ?>">
                <label for="friend_point" class="col-sm-3 control-label">フレンドポイント</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="friend_point" name="friend_point" value="<?= $params['friend_point'] ?>" placeholder="盟友ポイント">
                  <?php if(isset($flash['errors']['friend_point'])): ?>
                  <span class="help-block">friend_point <?= $flash['errors']['friend_point'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['bg_id'])) { echo 'has-error';} ?>">
                <label for="bg_id" class="col-sm-3 control-label">背景ID</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="bg_id" name="bg_id" value="<?= $params['bg_id'] ?>" placeholder="背景ID">
                  <?php if(isset($flash['errors']['bg_id'])): ?>
                  <span class="help-block">bg_id <?= $flash['errors']['bg_id'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['effect_id'])) { echo 'has-error';} ?>">
                <label for="effect_id" class="col-sm-3 control-label">演出ID</label>
                <div class="col-sm-9">
                  <input type="number" class="form-control" id="effect_id" name="effect_id" value="<?= $params['effect_id'] ?>" placeholder="演出ID">
                  <?php if(isset($flash['errors']['effect_id'])): ?>
                  <span class="help-block">effect_id <?= $flash['errors']['effect_id'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
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
              <div class="form-group <?php if (isset($flash['errors']['platform'])) { echo 'has-error';} ?>">
                <label for="platform" class="col-sm-3 control-label">プラットフォーム</label>
                <div class="col-sm-9">
                  <select class="form-control" name="platform">
                    <option value="0" <?php if ($params['platform'] == 0) { echo 'selected="selected"';} ?>>iOS/Android</option>
                    <option value="1" <?php if ($params['platform'] == \App\Models\Platform::PLATFORM_IOS) { echo 'selected="selected"';} ?>>iOS</option>
                    <option value="2" <?php if ($params['platform'] == \App\Models\Platform::PLATFORM_ANDROID) { echo 'selected="selected"';} ?>>Android</option>
                  </select>
                  <?php if(isset($flash['errors']['platform'])): ?>
                  <span class="help-block">platform <?= $flash['errors']['platform'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-group <?php if (isset($flash['errors']['withoutnew'])) { echo 'has-error';} ?>">
                <label for="withoutnew" class="col-sm-3 control-label">開始時刻後の登録ユーザーを対象にするか</label>
                <div class="col-sm-9">
                  <select class="form-control" name="withoutnew">
                    <option value="1" <?php if ($params['withoutnew'] == 1) { echo 'selected="selected"';} ?>>お知らせ期間開始後の新規ユーザーは対象外</option>
                    <option value="0" <?php if ($params['withoutnew'] == 0) { echo 'selected="selected"';} ?>>お知らせ期間開始後の新規ユーザーも対象</option>
                  </select>
                  <?php if(isset($flash['errors']['withoutnew'])): ?>
                  <span class="help-block">withoutnew <?= $flash['errors']['withoutnew'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
