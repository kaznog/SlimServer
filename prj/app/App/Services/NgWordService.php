<?php
namespace App\Services;

use \App\Models\ResultCode;
use \App\App;

/**
 * NGワードチェック処理.
 */
class NgWordService
{
    /**
     * 文字列にNGワードが含まれているかどうかをチェックする.
     * NGワードが含まれていたらエラー終了.
     * @param string $word
     */
    public function check($word)
    {
        foreach (static::$ngWords as $ngWord) {
            $pos = strpos($word, $ngWord);
            if ($pos !== false) {
                $app = App::getInstance();
                $app->logger->addNotice("NG word ({$ngWord}) is found: {$word}");
                $app->responseArray = [
                    "resultCode" => ResultCode::NG_WORD,
                    "word" => $word,
                    "ngWord" => $ngWord,
                ];
                $app->halt(200);
            }
        }
    }

    // NGワード
    private static $ngWords = [
        'ペイ患',
        'ペイ中',
        '薬物中毒',
        'コカイン',
        'ヘロイン',
        '麻薬',
        'マリファナ',
        'まりふぁな',
        '覚せい剤',
        '覚醒剤',
        '覚醒薬',
        '大麻',
        'サイレース',
        'ganja',
        'hemp',
        'lsd',
        'marijuana',
        'pcp',
        'マイクロドット',
        'まいくろどっと',
        'マジックマッシュルーム',
        'まじっくまっしゅるーむ',
        'マリワナ',
        'メチルフェニデート',
        'めちるふぇにでーと',
        'メチレンジオキシメタンフェタミン',
        'めちれんじおきしめたんふぇたみん',
        '売色',
        '買春',
        '姦通',
        '強姦',
        '集団レイプ',
        'ストリート・ガール',
        'すとりーと・がーる',
        'ストリートガール',
        '美人局',
        'ブルセラ',
        '売春',
        'ラブホテル',
        '彼女募集',
        '彼氏募集',
        '飲尿',
        'スカトロ',
        '血みどろ',
        '土佐衛門',
        'どざえもん',
        'ドザエモン',
        '脱糞',
        '溺死',
        '愛液',
        '青姦',
        '会陰',
        'イマラチオ',
        'いまらちお',
        'いらまちお',
        'イラマチオ',
        '陰部',
        '陰毛',
        '淫乱',
        '後背位',
        'オナ汁',
        'オナニスト',
        'オナペット',
        'オナホール',
        'オナリスト',
        'おまωこ',
        '女犯',
        '顔射',
        '我慢汁',
        'ガマンジル',
        '騎上位',
        '騎乗位',
        '虐犯',
        '巨孔',
        '玉門',
        '巨根',
        'きんしんそうかん',
        'キンシンソウカん',
        '栗鳥巣',
        'クリトリス',
        'クリニングス',
        'くりにんぐす',
        'くんにりんぐす',
        'クンニリングス',
        '口内発射',
        '米青身寸',
        'コンドーム',
        'ザーメン',
        'ザー麺',
        '自慰',
        '失禁',
        '射精',
        '尻の穴',
        'スケベ椅子',
        'ストリーキング',
        'ストリッパ',
        'ストリップ',
        'スペルマ',
        '素股',
        'スワッピング',
        'すわっぴんぐ',
        '正上位',
        '正常位',
        '性戯',
        '性春',
        '絶倫',
        'ゼツリン',
        'ダッチワイフ',
        'だっちわいふ',
        'チ○コ',
        'ち○こ',
        'チ〇コ',
        'ち〇こ',
        'チωコ',
        'ちωこ',
        'チωこ',
        'ちωコ',
        '恥辱',
        '痴汁',
        'チングリ返し',
        'チン毛',
        'チン拓',
        'ちん拓',
        '手コキ',
        '手淫',
        'テレホンSEX',
        'テレホンセックス',
        'トルコ嬢',
        'トルコ風呂',
        'トルコブロ',
        '生尺',
        '生ふぇら',
        '肉体関係',
        'にくたいかんけい',
        '肉便所',
        'パイズリ',
        '恥女',
        '恥帯',
        '恥肉',
        '恥部',
        '恥棒',
        '恥蜜',
        'バター犬',
        'ハメ撮り',
        'はめ撮り',
        'はめ撮',
        '秘部',
        '姫初め',
        'ピンクサロン',
        'ぴんくさろん',
        'ピンクローター',
        'ぴんくろーたー',
        'ファッションヘルス',
        'フィストファック',
        'ふぃすとふぁっく',
        '不生女',
        'ふぇらちお',
        'フェラチオ',
        'ペッティング',
        'ぺってぃんぐ',
        '粗チン',
        '本気汁',
        '本気じる',
        '本気ぢる',
        'マ○コ',
        'ま○こ',
        'マ〇コ',
        'ま〇こ',
        'マωコ',
        'まωこ',
        'まωコ',
        'マωこ',
        'マスターベーション',
        'ますたーべーしょん',
        'まんぐり返し',
        'マングリ返し',
        'マン毛',
        'まん毛',
        'マン汁',
        'マンジル',
        'マンズリ',
        '夢精',
        '幼児プレイ',
        '夜這',
        'ラブジュース ',
        '乱交',
        'ランジェリーパブ',
        'らんじぇりーぱぶ',
        '輪姦',
        '淫水',
        '姦婦',
        '近親相姦',
        '手こき',
        '手マン',
        '汁男優',
        '獣姦',
        '処女膜',
        '女体盛り',
        '身寸米青',
        '精射',
        '千摺り',
        '足ｺｷ',
        '男根',
        '恥根',
        '痴女',
        '肉便器',
        '濡れマン',
        '放尿',
        '朝立ち',
        'アスホール',
        'あすほーる',
        '蟻の戸渡り',
        '蟻の門渡り',
        'イ本イ立',
        'イ本シ夜',
        '裏スジ',
        '裏すじ',
        '裏筋',
        'ウラスジ',
        'ぉ○んこ',
        '扇返し',
        'オウギガエシ',
        'オナる',
        'おまんちょ',
        'オマンチョ',
        'カリ首',
        '顔面シャワー',
        '顔面発射',
        '毛雪駄',
        'ケチャマン',
        'けつの穴',
        'ケツノアナ',
        'けつのあな',
        '交差位',
        '素チン',
        '双成り',
        '即尺',
        'ソクシャク',
        '垂れパイ',
        '丹穴',
        'ダンコン',
        'ちｿこ',
        'チョルボッケ',
        '土手マン',
        'とるこふろ',
        'とるこぶろ',
        'ナマシャク',
        '生シャブ',
        '肉壷',
        '肉壺',
        'ニクツボ',
        'にくべんき',
        '花びら回転',
        'ハメドリ',
        '船玉様',
        'べドフィリア',
        'べどふぃりあ',
        'ベドフイリア',
        'べどふいりあ',
        'ペドフィリア',
        'ぺどふぃりあ',
        'ペドフイリア',
        '枕営業',
        '松葉くずし',
        '松葉崩し',
        'マツバクズシ',
        '短珍棒',
        '彦頁身寸',
        '蓮華・パドマ',
        '孛力走己',
        'Gスポット',
        'M字開脚 ',
        'えむじかいきゃく',
        '愛人バンク',
        '御香箱',
        '足コキ',
        '穴兄弟',
        '泡姫',
        'エロ画像',
        'エロ写',
        '大人のおもちゃ',
        '生殖器',
        '性病',
        '性欲',
        '乳首',
        '乳房',
        '生本番',
        '肉棒',
        '乳頭',
        '乳輪',
        '発情',
        '避妊',
        '子宮',
        '大人のオモチャ',
        '大人の玩具',
        '筆下ろし',
        '膜破り',
        'ミ包女臣',
        '裏ビデオ',
        '排卵日',
        'ass hole',
        '愛撫',
        'ヴァギナ',
        'オーガスム',
        '陰核',
        '陰唇',
        '陰裂',
        'ガマン汁',
        'がまん汁',
        '亀頭',
        '去勢',
        '睾丸',
        '女陰',
        '初潮',
        '精液',
        '性感',
        '性器',
        '性行為',
        '精子',
        '精巣',
        '早漏',
        '恥丘',
        '恥毛',
        '恥裂',
        '恥骨',
        'ペニス',
        '包茎',
        '勃起',
        '陰茎',
        '陰嚢',
        '吉舌',
        '性交',
        '恥核',
        '恥垢',
        '情婦',
        'チンカス',
        '童貞',
        '町のダニ',
        '淫売',
        '淫売婦',
        '売春婦',
        'しりがるおんな',
        'シリガルオンナ',
        'お里が知れる',
        '御里が知れる',
        'オサトガシレル',
        'おさとがしれる',
        'チェリーボーイ',
        '教養がない',
        '雌ギツネ',
        '醜男',
        '尻軽女',
        'あいぬじん',
        'アイヌ人',
        'アキメクラ',
        '慰安婦',
        '躄',
        '伊勢こじき',
        '伊勢乞食',
        '井戸掘り人夫',
        '井戸堀り人夫',
        '売女',
        '越後の米つき',
        '沖縄スラム',
        '沖縄ｽﾗﾑ',
        '害児',
        '片ちんば',
        '片輪',
        '片跛',
        '河原者',
        '奇形児',
        '北傀',
        '窮民',
        '狂人',
        '愚民',
        '欠損家族',
        '欠損家庭',
        '毛唐',
        '毛唐人',
        'ゲンバクスラム',
        '原爆スラム',
        'げんばくすらむ',
        '乞食',
        '三国人',
        '白痴',
        'ズベ公',
        '精神異常',
        'セイシンイジョウ',
        '精神薄弱',
        'せいしんびょうじゃくしゃ',
        '精神病弱者',
        'セイシンビョウジャクシャ',
        '精薄',
        '女衒',
        '知恵遅れ',
        '売国奴',
        'バイシュンフ',
        '白雉',
        '低脳',
        '不具者',
        '文盲',
        '細民',
        '未開人',
        '明盲',
        'ライ病',
        'らい病',
        '隠坊',
        '河原乞食',
        '過去帳',
        '基地外',
        '基地害',
        '妓生',
        '吃り',
        '吃る',
        '狂女',
        '三韓征伐',
        '手ん棒',
        '周旋屋',
        '夙',
        '台湾ハゲ',
        '低脳児',
        '半陰陽',
        '浮浪児',
        '明き盲',
        '聾',
        '傴僂',
        '畸形',
        '畸型',
        '癩病',
        '穢多',
        '藪睨み',
        '跛',
        'いどほりにんぷ',
        'イドホリニンプ',
        '卑しい階級',
        'えちごのこめつき',
        'エチゴノコメツキ',
        '近江どろぼう',
        '近江泥棒',
        'おうみどろぼう',
        'オウミドロボウ',
        'オキナワスラム',
        'おしつんぼ',
        'おちゅうどぶらく',
        'オチュウドブラク',
        'かたちんば',
        'カタチンバ',
        '片手落ち',
        'かたておち',
        'カタテオチ',
        '金聾',
        '寡婦',
        '上方贅六',
        'かみがたぜいろく',
        'カミガタゼイロク',
        '上方のぜい六',
        '上方の贅六',
        'カミガタノゼイロク',
        'かみがたのぜいろく',
        'かわらこじき',
        'カワラコジキ',
        'かん婦',
        '汚穢屋',
        '下賤',
        'けっそんかぞく',
        'ケッソンカゾク',
        'けっそんかてい',
        'ケッソンカテイ',
        '色覚異常',
        '地獄腹',
        'ジゴクバラ',
        'タケノコ医者',
        '筍医者',
        'たけのこいしゃ',
        '土手医者',
        'トバジュウギョウイン',
        '富山の三助',
        'トヤマノサンスケ',
        '非国民',
        'ひさべつぶらく',
        'ヒサベツブラク',
        '拡張員',
        '拡張団',
        '拡張団長',
        '保線工夫',
        '露助',
        '瘡っかき',
        '聾桟敷',
        '蒙古症',
        '顕正会',
        'イオマンテ',
        '犬神人',
        '犬殺し',
        '犬捕り',
        'きちがいざた',
        'キチガイザタ',
        'きちがいにはもの',
        'キチガイニハモノ',
        '首狩り族',
        'くびかりぞく',
        'クビカリゾク',
        'ちょうせんせいばつ',
        'チョウセンセイバツ',
        'ちょうりっぽ',
        'チョウリッポ',
        'ちょうりんぽう',
        'チョウリンポウ',
        'つんぼ桟敷',
        'つんぼさじき',
        'ツンボサジキ',
        '低開発国',
        'なんぶのしゃけのはなまがり',
        'ナンブノシャケノハナマガリ',
        '同和地区',
        'ﾀﾋね',
        'リストカット',
        '090',
        '080',
        '.com',
        '.jp',
        '://',
        '〒  ',
        '050',
        '070',
        'au',
        'docomo',
        'ezweb',
        'gree',
        'hotmail',
        'mail',
        'mbga',
        'mixi',
        'mixy',
        'msn',
        'myspace',
        'o90',
        'pr.cgiboy',
        'ptga',
        'skype',
        'softbank',
        'vodafone',
        'willcom',
        'www.',
        'yahoo',
        'ゼロキュウゼロ',
        'ぜろごうぜろ',
        'ゼロゴウゼロ',
        'ぜろななぜろ',
        'ゼロナナゼロ',
        'ぜろはちぜろ',
        'ゼロハチゼロ',
        'ソフトバンク',
        'そふとばんく',
        '携番',
        '直アド',
        '直メ',
        'ドットコム',
        'ドットジェイピー',
        'ドットネット',
        'ボーダフォン',
        'ぼーだふぉん',
        'ホットメール',
    ];
}
