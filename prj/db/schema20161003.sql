DROP DATABASE IF EXISTS `app_common`;
CREATE DATABASE app_common DEFAULT CHARACTER SET=utf8;
use app_common;

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Table `players_identities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players_identities` ;

CREATE TABLE IF NOT EXISTS `players_identities` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'プレイヤーID',
  `user_id` VARCHAR(45) NULL COMMENT 'パブリッシャー ユーザーID',
  `invitation_id` VARCHAR(9) NULL COMMENT '招待コード',
  `name` VARCHAR(45) NOT NULL COMMENT 'player名',
  `gender` TINYINT NOT NULL COMMENT '性別',
  `level` SMALLINT NOT NULL DEFAULT 1 COMMENT 'レベル (players.level のコピー)',
  `device_platform` TINYINT NOT NULL,
  `push_registration_id` TEXT DEFAULT NULL,
  `last_login_at` DATETIME NULL COMMENT '最終ログイン時刻 (1日に一度更新する)',
  `town_entry_reserve_id` INT DEFAULT -1 COMMENT 'タウンへ入室する予定のタウンID',
  `town_entry_reserve_expire` DATETIME DEFAULT NULL COMMENT 'タウン入室予約期限',
  `created_at` DATETIME NOT NULL COMMENT '登録時刻',
  `updated_at` TIMESTAMP NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT 'プレイヤーアカウント状況',
  `useragent` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_user_id` (`user_id` ASC),
  UNIQUE INDEX `uniq_invitation_id` (`invitation_id` ASC),
  INDEX `idx_last_login_at_level` (`last_login_at` DESC, `level` ASC),
  INDEX `idx_invitation_id` (`invitation_id` ASC),
  INDEX `idx_created_at_device_platform` (`created_at` ASC, `device_platform` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `app_versions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `app_versions` ;

CREATE TABLE IF NOT EXISTS `app_versions` (
  `id` INT NOT NULL COMMENT 'ID (= プラットフォーム. 1: iOS, 2: Android)',
  `required_version` VARCHAR(16) NOT NULL COMMENT '必須アプリバージョン',
  `applying_version` VARCHAR(16) NULL COMMENT '申請中アプリバージョン',
  `abdb_version` VARCHAR(4) NOT NULL,
  `created_at` DATETIME NOT NULL COMMENT '作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT '更新時刻',
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `maintenances`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `maintenances` ;

CREATE TABLE IF NOT EXISTS `maintenances` (
  `id` INT NOT NULL COMMENT 'ID(1のみ)',
  `start_at` DATETIME NULL COMMENT 'メンテナンス開始時刻',
  `end_at` DATETIME NULL COMMENT 'メンテナンス終了時刻',
  `message` VARCHAR(255) NOT NULL COMMENT 'メンテナンス中メッセージ',
  `created_at` DATETIME NOT NULL COMMENT '作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT '更新時刻',
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `maintenances`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `info_messages`;

CREATE TABLE info_messages (
  `id` INT NOT NULL AUTO_INCREMENT,
  `kind` INT NOT NULL DEFAULT 0 COMMENT '種類',
  `message` VARCHAR(255) NOT NULL COMMENT 'メッセージ本文',
  `start_at` DATETIME NOT NULL COMMENT '開始日時',
  `end_at` DATETIME NOT NULL COMMENT '終了日時',
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `notices`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `notices` ;

CREATE TABLE IF NOT EXISTS `notices` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'お知らせID',
  `title` VARCHAR(45) NOT NULL COMMENT 'お知らせタイトル',
  `body` TEXT NOT NULL COMMENT 'お知らせ本文',
  `friend_point` INT NOT NULL DEFAULT 0 COMMENT 'フレンドポイント',
  `bg_id` INT NOT NULL DEFAULT 0 COMMENT '背景ID',
  `effect_id` INT NOT NULL DEFAULT 0 COMMENT '演出ID',
  `start_at` DATETIME NOT NULL COMMENT 'お知らせ公開開始時刻',
  `end_at` DATETIME NOT NULL COMMENT 'お知らせ公開終了時刻',
  `platform` TINYINT NOT NULL DEFAULT 0 COMMENT '対象プラットフォーム',
  `withoutnew` TINYINT NOT NULL DEFAULT 0 COMMENT '新規ユーザーに送る場合0',
  PRIMARY KEY (`id`),
  INDEX `idx_start_at_end_at` (`start_at` ASC, `end_at` ASC))
ENGINE = InnoDB
COMMENT = 'お知らせ';

-- -----------------------------------------------------
-- Table `game_parameters`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `game_parameters` ;

CREATE TABLE IF NOT EXISTS `game_parameters` (
  `id` INT NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `value` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = '汎用ゲームパラメータ（定数）';

-- -----------------------------------------------------
-- Table `login_bonuses`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `login_bonuses` ;

CREATE TABLE IF NOT EXISTS `login_bonuses` (
  `id` INT NOT NULL,
  `login_cnt` INT NOT NULL COMMENT 'ログイン回数',
  `friend_point` INT NOT NULL DEFAULT 0 COMMENT '盟友ポイント',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_login_cnt` (`login_cnt` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `towns`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `towns` ;

CREATE TABLE IF NOT EXISTS `towns` (
  `id` INT NOT NULL,
  `inquiry_id` VARCHAR(32) NOT NULL COMMENT 'TOWN(ROOM)問い合わせID',
  `description` VARCHAR(32) NOT NULL COMMENT 'タウン名',
  `max_entries` INT NOT NULL COMMENT '最大参加人数',
  `entries` INT NOT NULL COMMENT '参加者数',
  PRIMARY KEY (`id`),
  INDEX `idx_inquiry` (`inquiry_id`)
)
ENGINE = InnoDB
COMMENT = 'タウン(ロビー)';

-- -----------------------------------------------------
-- Table `town_entry_reserves`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `town_entry_reserves` ;

CREATE TABLE IF NOT EXISTS `town_entry_reserves` (
  `id` INT NOT NULL COMMENT 'プレイヤー毎に持つタウン予約レコードID(プレイヤーIDと同じ)',
  `town_id` INT DEFAULT -1 COMMENT 'タウンへ入室する予定のタウンID',
  `expire` DATETIME DEFAULT NULL COMMENT 'タウン入室予約期限',
  PRIMARY KEY (`id`),
  INDEX `idx_expire` (`expire` ASC),
  INDEX `idx_town_id` (`town_id` ASC)
)
ENGINE = InnoDB
COMMENT = 'タウン入室予約';

-- -----------------------------------------------------
-- Table `battle_fields`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `battle_fields` ;

CREATE TABLE IF NOT EXISTS `battle_fields` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'バトルフィールドID',
  `inquiryId` VARCHAR(32) NOT NULL COMMENT 'ROOM問い合わせID',
  `status` INT NOT NULL COMMENT 'バトル状況',
  `max_entries` INT NOT NULL COMMENT '最大参加人数',
  `entries` INT NOT NULL COMMENT '参加者数',
  `created_at` DATETIME NOT NULL COMMENT '作成時刻',
  PRIMARY KEY (`id`),
  INDEX `idx_inquiry_id` (`inquiryId`)
)
ENGINE = InnoDB
COMMENT = 'バトルフィールド';

-- -----------------------------------------------------
-- Tables `battle_players`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `battle_players` ;

CREATE TABLE IF NOT EXISTS `battle_players` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `player_id` INT NOT NULL COMMENT 'プレイヤーID',
  `player_lv` INT NOT NULL COMMENT 'プレイヤーレベル',
  `name` VARCHAR(45) NOT NULL COMMENT 'プレイヤー名',
  `battlefield_id` INT NOT NULL COMMENT 'battle_fields ID',
  `device_platform` TINYINT NOT NULL COMMENT 'デバイス',
  PRIMARY KEY (`id`),
  INDEX `idx_player_id` (`player_id` ASC),
  INDEX `idx_battlefield_id` (`battlefield_id` ASC))
ENGINE = InnoDB
COMMENT = '参加プレイヤー情報';

-- -----------------------------------------------------
DROP DATABASE IF EXISTS `app_shard_000`;
CREATE DATABASE app_shard_000 DEFAULT CHARACTER SET=utf8;
use app_shard_000;

-- -----------------------------------------------------
-- Table `players`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players` ;

CREATE TABLE IF NOT EXISTS `players` (
  `id` INT NOT NULL COMMENT 'プレイヤーID',
  `town_id` INT NOT NULL DEFAULT -1 COMMENT '所属タウンID(-1の時はクライアントで選択させて、選んだタウンを保存する)',
  `level` SMALLINT NOT NULL DEFAULT 1 COMMENT 'レベル',
  `exp` INT NOT NULL DEFAULT 0 COMMENT '経験値',
  `level_exp` INT NOT NULL DEFAULT 0 COMMENT '現在のレベルになってからの経験値.',
  `friend_point` INT NOT NULL DEFAULT 0 COMMENT 'friend ポイント',
  `liked` INT NOT NULL DEFAULT 0 COMMENT 'いいねポイント',
  `last_login_at` DATETIME NULL COMMENT '最終ログイン日時',
  `login_count` SMALLINT NOT NULL DEFAULT 0 COMMENT '実プレイ日数',
  `login_streak_count` SMALLINT NOT NULL DEFAULT 0 COMMENT 'ログイン継続日数',
  `last_checkin_at` DATETIME NULL,
  `latest_area_id` INT NULL COMMENT '最終チェックインエリアID',
  `lat` DOUBLE(9,6) NULL COMMENT '最終チェックイン緯度',
  `lng` DOUBLE(9,6) NULL COMMENT '最終チェックイン経度',
  `total_movement` INT NOT NULL DEFAULT 0 COMMENT '位置情報総移動距離',
  `checkin_count` INT NOT NULL DEFAULT 0 COMMENT '累積チェックイン回数',
  `extra_friend_capacity` SMALLINT NOT NULL DEFAULT 0 COMMENT 'フレンド数上限追加分',
  `created_at` DATETIME NOT NULL,
  `updated_at` TIMESTAMP NOT NULL,
  `sns_otp` VARCHAR(45) NULL COMMENT 'チェックイン後のSNS投稿用ワンタイムパスワード',
  `tutorial1` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ1',
  `tutorial2` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ2',
  `tutorial3` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ3',
  `tutorial4` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ4',
  `tutorial5` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ5',
  `tutorial6` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ6',
  `tutorial7` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ7',
  `tutorial8` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ8',
  `device_platform` TINYINT NOT NULL DEFAULT 0 COMMENT 'プラットフォーム',
  `session_id` VARCHAR(36) NULL COMMENT 'セッションID',
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'プレイヤーデータ';

-- -----------------------------------------------------
-- Table `players_social_activities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players_social_activities` ;

CREATE TABLE IF NOT EXISTS `players_social_activities` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` INT NOT NULL COMMENT 'プレイヤーID',
  `invitation_count` INT NOT NULL DEFAULT 0,
  `like_count` INT NOT NULL DEFAULT 0 COMMENT 'いいねされた回数',
  `like_friend_point` INT NOT NULL DEFAULT 0 COMMENT 'いいねされた報酬',
  `created_at` DATETIME NOT NULL COMMENT 'データ作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT 'データ更新時刻',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_player_id` (`player_id` ASC))
ENGINE = InnoDB
COMMENT = 'プレイヤーのソーシャルな活動によって更新されるデータ';

-- -----------------------------------------------------
-- Table `players_daily_activities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players_daily_activities` ;

CREATE TABLE IF NOT EXISTS `players_daily_activities` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `player_id` INT NOT NULL COMMENT 'プレイヤーID',
  `date` DATE NOT NULL COMMENT '日付',
  `device_platform` TINYINT NOT NULL COMMENT 'プラットフォーム',
  `movement` INT NOT NULL DEFAULT 0 COMMENT 'その日の移動距離',
  `visitor` INT NOT NULL DEFAULT 0 COMMENT 'その日の訪問者',
  `likes` INT NOT NULL DEFAULT 0 COMMENT 'その日のいいね',
  `created_at` DATETIME NOT NULL COMMENT '作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT '更新時刻',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_players_daily_activities` (`player_id` ASC, `date` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `mails`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `mails` ;

CREATE TABLE IF NOT EXISTS `mails` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `player_id` INT NOT NULL COMMENT 'メール受信プレイヤーID',
  `sender_player_id` INT NOT NULL COMMENT '送信元プレイヤーID',
  `body` VARCHAR(255) NOT NULL COMMENT '本文',
  `is_read` TINYINT(1) NOT NULL DEFAULT FALSE COMMENT '既読フラグ',
  `created_at` DATETIME NOT NULL,
  `updated_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_player_id_is_read_created_at` (`player_id` ASC, `is_read` ASC, `created_at` ASC),
  INDEX `idx_player_id_is_read` (`player_id` ASC, `is_read` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
DROP DATABASE IF EXISTS `app_shard_001`;
CREATE DATABASE app_shard_001 DEFAULT CHARACTER SET=utf8;
use app_shard_001;

-- -----------------------------------------------------
-- Table `players`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players` ;

CREATE TABLE IF NOT EXISTS `players` (
  `id` INT NOT NULL COMMENT 'プレイヤーID',
  `town_id` INT NOT NULL DEFAULT -1 COMMENT '所属タウンID(-1の時はクライアントで選択させて、選んだタウンを保存する)',
  `level` SMALLINT NOT NULL DEFAULT 1 COMMENT 'レベル',
  `exp` INT NOT NULL DEFAULT 0 COMMENT '経験値',
  `level_exp` INT NOT NULL DEFAULT 0 COMMENT '現在のレベルになってからの経験値.',
  `friend_point` INT NOT NULL DEFAULT 0 COMMENT 'friend ポイント',
  `liked` INT NOT NULL DEFAULT 0 COMMENT 'いいねポイント',
  `last_login_at` DATETIME NULL COMMENT '最終ログイン日時',
  `login_count` SMALLINT NOT NULL DEFAULT 0 COMMENT '実プレイ日数',
  `login_streak_count` SMALLINT NOT NULL DEFAULT 0 COMMENT 'ログイン継続日数',
  `last_checkin_at` DATETIME NULL,
  `latest_area_id` INT NULL COMMENT '最終チェックインエリアID',
  `lat` DOUBLE(9,6) NULL COMMENT '最終チェックイン緯度',
  `lng` DOUBLE(9,6) NULL COMMENT '最終チェックイン経度',
  `total_movement` INT NOT NULL DEFAULT 0 COMMENT '位置情報総移動距離',
  `checkin_count` INT NOT NULL DEFAULT 0 COMMENT '累積チェックイン回数',
  `extra_friend_capacity` SMALLINT NOT NULL DEFAULT 0 COMMENT 'フレンド数上限追加分',
  `created_at` DATETIME NOT NULL,
  `updated_at` TIMESTAMP NOT NULL,
  `sns_otp` VARCHAR(45) NULL COMMENT 'チェックイン後のSNS投稿用ワンタイムパスワード',
  `tutorial1` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ1',
  `tutorial2` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ2',
  `tutorial3` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ3',
  `tutorial4` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ4',
  `tutorial5` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ5',
  `tutorial6` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ6',
  `tutorial7` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ7',
  `tutorial8` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ8',
  `device_platform` TINYINT NOT NULL DEFAULT 0 COMMENT 'プラットフォーム',
  `session_id` VARCHAR(36) NULL COMMENT 'セッションID',
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'プレイヤーデータ';

-- -----------------------------------------------------
-- Table `players_social_activities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players_social_activities` ;

CREATE TABLE IF NOT EXISTS `players_social_activities` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` INT NOT NULL COMMENT 'プレイヤーID',
  `invitation_count` INT NOT NULL DEFAULT 0,
  `like_count` INT NOT NULL DEFAULT 0 COMMENT 'いいねされた回数',
  `like_friend_point` INT NOT NULL DEFAULT 0 COMMENT 'いいねされた報酬',
  `created_at` DATETIME NOT NULL COMMENT 'データ作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT 'データ更新時刻',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_player_id` (`player_id` ASC))
ENGINE = InnoDB
COMMENT = 'プレイヤーのソーシャルな活動によって更新されるデータ';

-- -----------------------------------------------------
-- Table `players_daily_activities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players_daily_activities` ;

CREATE TABLE IF NOT EXISTS `players_daily_activities` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `player_id` INT NOT NULL COMMENT 'プレイヤーID',
  `date` DATE NOT NULL COMMENT '日付',
  `device_platform` TINYINT NOT NULL COMMENT 'プラットフォーム',
  `movement` INT NOT NULL DEFAULT 0 COMMENT 'その日の移動距離',
  `visitor` INT NOT NULL DEFAULT 0 COMMENT 'その日の訪問者',
  `likes` INT NOT NULL DEFAULT 0 COMMENT 'その日のいいね',
  `created_at` DATETIME NOT NULL COMMENT '作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT '更新時刻',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_players_daily_activities` (`player_id` ASC, `date` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `mails`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `mails` ;

CREATE TABLE IF NOT EXISTS `mails` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `player_id` INT NOT NULL COMMENT 'メール受信プレイヤーID',
  `sender_player_id` INT NOT NULL COMMENT '送信元プレイヤーID',
  `body` VARCHAR(255) NOT NULL COMMENT '本文',
  `is_read` TINYINT(1) NOT NULL DEFAULT FALSE COMMENT '既読フラグ',
  `created_at` DATETIME NOT NULL,
  `updated_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_player_id_is_read_created_at` (`player_id` ASC, `is_read` ASC, `created_at` ASC),
  INDEX `idx_player_id_is_read` (`player_id` ASC, `is_read` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
DROP DATABASE IF EXISTS `app_shard_002`;
CREATE DATABASE app_shard_002 DEFAULT CHARACTER SET=utf8;
use app_shard_002;

-- -----------------------------------------------------
-- Table `players`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players` ;

CREATE TABLE IF NOT EXISTS `players` (
  `id` INT NOT NULL COMMENT 'プレイヤーID',
  `town_id` INT NOT NULL DEFAULT -1 COMMENT '所属タウンID(-1の時はクライアントで選択させて、選んだタウンを保存する)',
  `level` SMALLINT NOT NULL DEFAULT 1 COMMENT 'レベル',
  `exp` INT NOT NULL DEFAULT 0 COMMENT '経験値',
  `level_exp` INT NOT NULL DEFAULT 0 COMMENT '現在のレベルになってからの経験値.',
  `friend_point` INT NOT NULL DEFAULT 0 COMMENT 'friend ポイント',
  `liked` INT NOT NULL DEFAULT 0 COMMENT 'いいねポイント',
  `last_login_at` DATETIME NULL COMMENT '最終ログイン日時',
  `login_count` SMALLINT NOT NULL DEFAULT 0 COMMENT '実プレイ日数',
  `login_streak_count` SMALLINT NOT NULL DEFAULT 0 COMMENT 'ログイン継続日数',
  `last_checkin_at` DATETIME NULL,
  `latest_area_id` INT NULL COMMENT '最終チェックインエリアID',
  `lat` DOUBLE(9,6) NULL COMMENT '最終チェックイン緯度',
  `lng` DOUBLE(9,6) NULL COMMENT '最終チェックイン経度',
  `total_movement` INT NOT NULL DEFAULT 0 COMMENT '位置情報総移動距離',
  `checkin_count` INT NOT NULL DEFAULT 0 COMMENT '累積チェックイン回数',
  `extra_friend_capacity` SMALLINT NOT NULL DEFAULT 0 COMMENT 'フレンド数上限追加分',
  `created_at` DATETIME NOT NULL,
  `updated_at` TIMESTAMP NOT NULL,
  `sns_otp` VARCHAR(45) NULL COMMENT 'チェックイン後のSNS投稿用ワンタイムパスワード',
  `tutorial1` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ1',
  `tutorial2` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ2',
  `tutorial3` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ3',
  `tutorial4` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ4',
  `tutorial5` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ5',
  `tutorial6` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ6',
  `tutorial7` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ7',
  `tutorial8` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ8',
  `device_platform` TINYINT NOT NULL DEFAULT 0 COMMENT 'プラットフォーム',
  `session_id` VARCHAR(36) NULL COMMENT 'セッションID',
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'プレイヤーデータ';

-- -----------------------------------------------------
-- Table `players_social_activities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players_social_activities` ;

CREATE TABLE IF NOT EXISTS `players_social_activities` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` INT NOT NULL COMMENT 'プレイヤーID',
  `invitation_count` INT NOT NULL DEFAULT 0,
  `like_count` INT NOT NULL DEFAULT 0 COMMENT 'いいねされた回数',
  `like_friend_point` INT NOT NULL DEFAULT 0 COMMENT 'いいねされた報酬',
  `created_at` DATETIME NOT NULL COMMENT 'データ作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT 'データ更新時刻',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_player_id` (`player_id` ASC))
ENGINE = InnoDB
COMMENT = 'プレイヤーのソーシャルな活動によって更新されるデータ';

-- -----------------------------------------------------
-- Table `players_daily_activities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players_daily_activities` ;

CREATE TABLE IF NOT EXISTS `players_daily_activities` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `player_id` INT NOT NULL COMMENT 'プレイヤーID',
  `date` DATE NOT NULL COMMENT '日付',
  `device_platform` TINYINT NOT NULL COMMENT 'プラットフォーム',
  `movement` INT NOT NULL DEFAULT 0 COMMENT 'その日の移動距離',
  `visitor` INT NOT NULL DEFAULT 0 COMMENT 'その日の訪問者',
  `likes` INT NOT NULL DEFAULT 0 COMMENT 'その日のいいね',
  `created_at` DATETIME NOT NULL COMMENT '作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT '更新時刻',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_players_daily_activities` (`player_id` ASC, `date` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `mails`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `mails` ;

CREATE TABLE IF NOT EXISTS `mails` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `player_id` INT NOT NULL COMMENT 'メール受信プレイヤーID',
  `sender_player_id` INT NOT NULL COMMENT '送信元プレイヤーID',
  `body` VARCHAR(255) NOT NULL COMMENT '本文',
  `is_read` TINYINT(1) NOT NULL DEFAULT FALSE COMMENT '既読フラグ',
  `created_at` DATETIME NOT NULL,
  `updated_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_player_id_is_read_created_at` (`player_id` ASC, `is_read` ASC, `created_at` ASC),
  INDEX `idx_player_id_is_read` (`player_id` ASC, `is_read` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
DROP DATABASE IF EXISTS `app_shard_003`;
CREATE DATABASE app_shard_003 DEFAULT CHARACTER SET=utf8;
use app_shard_003;

-- -----------------------------------------------------
-- Table `players`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players` ;

CREATE TABLE IF NOT EXISTS `players` (
  `id` INT NOT NULL COMMENT 'プレイヤーID',
  `town_id` INT NOT NULL DEFAULT -1 COMMENT '所属タウンID(-1の時はクライアントで選択させて、選んだタウンを保存する)',
  `level` SMALLINT NOT NULL DEFAULT 1 COMMENT 'レベル',
  `exp` INT NOT NULL DEFAULT 0 COMMENT '経験値',
  `level_exp` INT NOT NULL DEFAULT 0 COMMENT '現在のレベルになってからの経験値.',
  `friend_point` INT NOT NULL DEFAULT 0 COMMENT 'friend ポイント',
  `liked` INT NOT NULL DEFAULT 0 COMMENT 'いいねポイント',
  `last_login_at` DATETIME NULL COMMENT '最終ログイン日時',
  `login_count` SMALLINT NOT NULL DEFAULT 0 COMMENT '実プレイ日数',
  `login_streak_count` SMALLINT NOT NULL DEFAULT 0 COMMENT 'ログイン継続日数',
  `last_checkin_at` DATETIME NULL,
  `latest_area_id` INT NULL COMMENT '最終チェックインエリアID',
  `lat` DOUBLE(9,6) NULL COMMENT '最終チェックイン緯度',
  `lng` DOUBLE(9,6) NULL COMMENT '最終チェックイン経度',
  `total_movement` INT NOT NULL DEFAULT 0 COMMENT '位置情報総移動距離',
  `checkin_count` INT NOT NULL DEFAULT 0 COMMENT '累積チェックイン回数',
  `extra_friend_capacity` SMALLINT NOT NULL DEFAULT 0 COMMENT 'フレンド数上限追加分',
  `created_at` DATETIME NOT NULL,
  `updated_at` TIMESTAMP NOT NULL,
  `sns_otp` VARCHAR(45) NULL COMMENT 'チェックイン後のSNS投稿用ワンタイムパスワード',
  `tutorial1` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ1',
  `tutorial2` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ2',
  `tutorial3` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ3',
  `tutorial4` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ4',
  `tutorial5` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ5',
  `tutorial6` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ6',
  `tutorial7` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ7',
  `tutorial8` SMALLINT NOT NULL DEFAULT 0 COMMENT 'チュートリアル進行状況フラグ8',
  `device_platform` TINYINT NOT NULL DEFAULT 0 COMMENT 'プラットフォーム',
  `session_id` VARCHAR(36) NULL COMMENT 'セッションID',
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = 'プレイヤーデータ';

-- -----------------------------------------------------
-- Table `players_social_activities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players_social_activities` ;

CREATE TABLE IF NOT EXISTS `players_social_activities` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` INT NOT NULL COMMENT 'プレイヤーID',
  `invitation_count` INT NOT NULL DEFAULT 0,
  `like_count` INT NOT NULL DEFAULT 0 COMMENT 'いいねされた回数',
  `like_friend_point` INT NOT NULL DEFAULT 0 COMMENT 'いいねされた報酬',
  `created_at` DATETIME NOT NULL COMMENT 'データ作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT 'データ更新時刻',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_player_id` (`player_id` ASC))
ENGINE = InnoDB
COMMENT = 'プレイヤーのソーシャルな活動によって更新されるデータ';

-- -----------------------------------------------------
-- Table `players_daily_activities`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `players_daily_activities` ;

CREATE TABLE IF NOT EXISTS `players_daily_activities` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `player_id` INT NOT NULL COMMENT 'プレイヤーID',
  `date` DATE NOT NULL COMMENT '日付',
  `device_platform` TINYINT NOT NULL COMMENT 'プラットフォーム',
  `movement` INT NOT NULL DEFAULT 0 COMMENT 'その日の移動距離',
  `visitor` INT NOT NULL DEFAULT 0 COMMENT 'その日の訪問者',
  `likes` INT NOT NULL DEFAULT 0 COMMENT 'その日のいいね',
  `created_at` DATETIME NOT NULL COMMENT '作成時刻',
  `updated_at` TIMESTAMP NOT NULL COMMENT '更新時刻',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uniq_players_daily_activities` (`player_id` ASC, `date` ASC))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `mails`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `mails` ;

CREATE TABLE IF NOT EXISTS `mails` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `player_id` INT NOT NULL COMMENT 'メール受信プレイヤーID',
  `sender_player_id` INT NOT NULL COMMENT '送信元プレイヤーID',
  `body` VARCHAR(255) NOT NULL COMMENT '本文',
  `is_read` TINYINT(1) NOT NULL DEFAULT FALSE COMMENT '既読フラグ',
  `created_at` DATETIME NOT NULL,
  `updated_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_player_id_is_read_created_at` (`player_id` ASC, `is_read` ASC, `created_at` ASC),
  INDEX `idx_player_id_is_read` (`player_id` ASC, `is_read` ASC))
ENGINE = InnoDB;
