        <div class="col-sm-4 col-md-3">
          <div class="panel panel-default">
            <!-- Default panel contents -->
            <div class="panel-heading">プレイヤー情報</div>
            <!-- List group -->
            <ul class="list-group">
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>">プレイヤーデータ</a></li>
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/present_box_entries">プレゼントボックス</a></li>
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/notices">お知らせ確認時刻</a></li>
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/daily_activities">行動履歴</a></li>
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/login_histories">ログイン履歴</a></li>
            </ul>
          </div>

          <div class="panel panel-default">
            <!-- Default panel contents -->
            <div class="panel-heading">フレンド</div>
            <!-- List group -->
            <ul class="list-group">
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/friendships">フレンド</a></li>
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/invitation_histories">招待履歴</a></li>
            </ul>
          </div>

          <div class="panel panel-default">
            <!-- Default panel contents -->
            <div class="panel-heading">課金</div>
            <!-- List group -->
            <ul class="list-group">
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/monthly_sales">月毎課金額</a></li>
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/purchase_histories">仮想通貨購入履歴</a></li>
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/vmoney_consume_histories">仮想通貨消費履歴</a></li>
            </ul>
          </div>

          <div class="panel panel-default">
            <!-- Default panel contents -->
            <div class="panel-heading">所持アイテム</div>
            <!-- List group -->
            <ul class="list-group">
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/items">アイテム</a></li>
            </ul>
          </div>

          <div class="panel panel-default">
            <!-- Default panel contents -->
            <div class="panel-heading">ミッション</div>
            <!-- List group -->
            <ul class="list-group">
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/mission_total_records">集計パラメータ</a></li>
              <li class="list-group-item"><a href="/admin/players/<?= $playerId ?>/mission_list">クリア情報</a></li>
            </ul>
          </div>

        </div><!-- .span3 (sidebar) -->