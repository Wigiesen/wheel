

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


INSERT INTO `ims_ewei_shop_plugin` VALUES (45, 45, 'qrcodetg', 'biz', '二维码推广', '1.0', '二开', 1, '../addons/ewei_shopv2/plugin/qrcodetg/static/qrcodetg.jpg', '', 0, 0, 1);

-- ----------------------------
-- Table structure for ims_ewei_shop_qrcodetg_amount
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_qrcodetg_amount`;
CREATE TABLE `ims_ewei_shop_qrcodetg_amount`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invite_code` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '邀请码',
  `use_amount` decimal(10, 2) NOT NULL COMMENT '已提现',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ims_ewei_shop_qrcodetg_cash_log
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_qrcodetg_cash_log`;
CREATE TABLE `ims_ewei_shop_qrcodetg_cash_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(10, 2) NOT NULL COMMENT '提现金额',
  `invite_code` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '推广员邀请码',
  `promoter` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '推广员姓名',
  `phone` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '推广员手机号码',
  `wechat` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '推广员微信号',
  `create_time` int(11) NOT NULL COMMENT '提现时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ims_ewei_shop_qrcodetg_log
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_qrcodetg_log`;
CREATE TABLE `ims_ewei_shop_qrcodetg_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invite_code` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '邀请码',
  `orderid` int(11) NOT NULL COMMENT '订单ID',
  `price` decimal(10, 2) NOT NULL COMMENT '订单总价格',
  `proportion` decimal(10, 2) NOT NULL COMMENT '分成比例',
  `order_status` int(11) NOT NULL COMMENT '订单状态 0.已下单,未付款 1.已付款,未收货  2.已收货',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ims_ewei_shop_qrcodetg_param
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_qrcodetg_param`;
CREATE TABLE `ims_ewei_shop_qrcodetg_param`  (
  `proportion` decimal(10, 2) NOT NULL COMMENT '分成比例'
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of ims_ewei_shop_qrcodetg_param
-- ----------------------------
INSERT INTO `ims_ewei_shop_qrcodetg_param` VALUES (8.00);

-- ----------------------------
-- Table structure for ims_ewei_shop_qrcodetg_qrcode
-- ----------------------------
DROP TABLE IF EXISTS `ims_ewei_shop_qrcodetg_qrcode`;
CREATE TABLE `ims_ewei_shop_qrcodetg_qrcode`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invite_code` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '邀请码',
  `shop_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '商品id 或 链接',
  `proportion` decimal(10, 2) NOT NULL COMMENT '分成比例',
  `leader` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '发码人',
  `promoter` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '推广人',
  `phone` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '推广人手机号码',
  `wechat` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '推广人微信号码',
  `qrcode_img` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '二维码图片链接',
  `qrcode_link` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '二维么url链接',
  `status` int(11) NOT NULL COMMENT '推广人二维码状态：1正常 2过期 0 禁止',
  `amount` int(11) NOT NULL COMMENT '扫码数量',
  `end_time` int(11) NOT NULL COMMENT '二维码截止有效时间',
  `add_time` int(11) NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;