
-- -----------------------------------------------------
-- MAJ Table `mydb`.`blog_category`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tb_bees_blog_category`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_category` (
  `id_bees_blog_category`  INT(10) NOT NULL AUTO_INCREMENT,
  `id_parent`       INT(11) NOT NULL,
  `position`        INT(11) NULL,
  `active`          INT(11) NULL,
  `date_add`        DATETIME NULL,
  `date_upd`        DATETIME NULL,
  PRIMARY KEY (`id_bees_blog_category`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `tb_bees_blog_category_lang`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_category_lang` (
  `id_bees_blog_category`   INT(10) NOT NULL AUTO_INCREMENT,
  `id_lang`                 INT(10) NOT NULL,
  `title`                   VARCHAR(256)  NULL,
  `description`             VARCHAR(512)  NULL,
  `link_rewrite`            VARCHAR(256) NULL,
  PRIMARY KEY (`id_bees_blog_category`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- -----------------------------------------------------
-- MAJ Table `mydb`.`blog_category`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tb_bees_blog_post`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_post` (
  `id_bees_blog_post`  INT(10) NOT NULL AUTO_INCREMENT,
  `active`             INT(10) NULL,
  `comments_enabled`   INT(10) NULL,
  `date_add`           DATETIME NULL,
  `date_upd`           DATETIME NULL,
  `published`          DATETIME NULL,
  `id_category`        INT(10) NULL,
  `id_employee`        TEXT  NULL,
  `image`              VARCHAR(128) NULL,
  `position`           INT(10) NULL,
  `post_type`          VARCHAR(255) NULL,
  `viewed`             INT(20) NULL,
  `title`              VARCHAR(255) NULL,
  `content`            TEXT NULL,
  `link_rewrite`       VARCHAR(255) NULL,
  `lang_active`        INT(10) NULL,
  PRIMARY KEY (`id_bees_blog_post`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `tb_bees_blog_post_lang`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_post_lang` (
  `id_bees_blog_post`  INT(10) NOT NULL AUTO_INCREMENT,
  `id_lang`            INT(10) NOT NULL,
  `title`              VARCHAR(255) NULL,
  `content`            TEXT NULL,
  `link_rewrite`       VARCHAR(255) NULL,
  `lang_active`        INT(10) NULL,
  PRIMARY KEY (`id_bees_blog_post`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `tb_bees_blog_post_shop`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_post_shop` (
  `id_bees_blog_post`  INT(10) NOT NULL AUTO_INCREMENT,
  `id_shop`            INT(10) NOT NULL,
  PRIMARY KEY (`id_bees_blog_post`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- -----------------------------------------------------
-- MAJ Table `mydb`.`tb_bees_blog_image_type`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tb_bees_blog_image_type`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_image_type` (
  `id_bees_blog_image_type`  INT(10) NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(255) NULL,
  `width`             INT(10) NULL,
  `height`            INT(10) NULL,
  `posts`             INT(10) NULL,
  `categories`        INT(10) NULL,
  PRIMARY KEY (`id_bees_blog_image_type`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `tb_bees_blog_image_type` (`id_bees_blog_image_type`, `name`, `width`, `height`, `posts`, `categories`) VALUES (NULL, 'aaa', '650', '200', '1', '0');

DROP TABLE IF EXISTS `tb_bees_blog_image_type_shop`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_image_type_shop` (
  `id_bees_blog_image_type`  INT(10) NOT NULL AUTO_INCREMENT,
  `id_shop`                  INT(10) NOT NULL,
  PRIMARY KEY (`id_bees_blog_image_type`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
