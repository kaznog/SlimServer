use app_common;
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

INSERT INTO towns VALUES(0, 'town1', 'タウン1', 30, 0), (1, 'town2', 'タウン2', 30, 0), (2, 'town3', 'タウン3', 30, 0);