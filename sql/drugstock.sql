/*
 Navicat Premium Data Transfer

 Source Server         : Notebook
 Source Server Type    : MySQL
 Source Server Version : 100017
 Source Host           : 192.168.100.138:3306
 Source Schema         : drugstock

 Target Server Type    : MySQL
 Target Server Version : 100017
 File Encoding         : 65001

 Date: 09/03/2026 10:25:25
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for daily_usage
-- ----------------------------
DROP TABLE IF EXISTS `daily_usage`;
CREATE TABLE `daily_usage`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `sub_id` int NOT NULL,
  `quantity_used` int NOT NULL,
  `usage_date` date NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sub_id`(`sub_id`) USING BTREE,
  CONSTRAINT `daily_usage_ibfk_1` FOREIGN KEY (`sub_id`) REFERENCES `sub_warehouse` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = tis620 COLLATE = tis620_thai_ci ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of daily_usage
-- ----------------------------

-- ----------------------------
-- Table structure for login_otp
-- ----------------------------
DROP TABLE IF EXISTS `login_otp`;
CREATE TABLE `login_otp`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `otp_code` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expire_at` datetime NOT NULL,
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 39 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of login_otp
-- ----------------------------
INSERT INTO `login_otp` VALUES (1, 2, '782728', '2026-01-28 22:31:17', '2026-01-28 22:26:17');
INSERT INTO `login_otp` VALUES (2, 2, '713903', '2026-01-28 22:37:35', '2026-01-28 22:32:35');
INSERT INTO `login_otp` VALUES (3, 2, '556115', '2026-01-28 22:42:34', '2026-01-28 22:37:34');
INSERT INTO `login_otp` VALUES (4, 2, '259836', '2026-01-28 22:44:19', '2026-01-28 22:39:19');
INSERT INTO `login_otp` VALUES (5, 2, '383780', '2026-01-28 22:47:39', '2026-01-28 22:42:39');
INSERT INTO `login_otp` VALUES (6, 2, '633523', '2026-01-28 22:54:48', '2026-01-28 22:49:48');
INSERT INTO `login_otp` VALUES (7, 2, '870873', '2026-01-28 23:09:45', '2026-01-28 23:04:45');
INSERT INTO `login_otp` VALUES (8, 9, '557213', '2026-01-28 23:11:45', '2026-01-28 23:06:45');
INSERT INTO `login_otp` VALUES (9, 10, '329490', '2026-01-28 23:12:59', '2026-01-28 23:07:59');
INSERT INTO `login_otp` VALUES (10, 1, '638514', '2026-01-07 11:49:40', '2026-01-07 11:44:40');
INSERT INTO `login_otp` VALUES (11, 6, '305287', '2026-01-07 11:57:23', '2026-01-07 11:52:23');
INSERT INTO `login_otp` VALUES (12, 1, '275606', '2026-02-01 08:35:27', '2026-02-01 08:30:27');
INSERT INTO `login_otp` VALUES (13, 1, '103607', '2026-02-06 20:53:27', '2026-02-06 20:48:27');
INSERT INTO `login_otp` VALUES (14, 1, '268891', '2026-02-11 14:20:26', '2026-02-11 14:15:26');
INSERT INTO `login_otp` VALUES (15, 1, '343984', '2026-02-11 21:51:52', '2026-02-11 21:46:52');
INSERT INTO `login_otp` VALUES (16, 15, '490504', '2026-02-11 22:06:05', '2026-02-11 22:01:05');
INSERT INTO `login_otp` VALUES (17, 15, '872233', '2026-02-11 22:06:13', '2026-02-11 22:01:13');
INSERT INTO `login_otp` VALUES (18, 1, '285246', '2026-02-20 10:54:41', '2026-02-20 10:49:41');
INSERT INTO `login_otp` VALUES (19, 1, '587136', '2026-02-20 11:00:41', '2026-02-20 10:55:41');
INSERT INTO `login_otp` VALUES (20, 1, '983185', '2026-02-20 11:07:30', '2026-02-20 11:02:30');
INSERT INTO `login_otp` VALUES (21, 1, '281287', '2026-02-20 11:07:30', '2026-02-20 11:02:30');
INSERT INTO `login_otp` VALUES (22, 1, '791436', '2026-02-20 11:11:26', '2026-02-20 11:06:26');
INSERT INTO `login_otp` VALUES (23, 1, '148385', '2026-02-20 11:12:31', '2026-02-20 11:07:31');
INSERT INTO `login_otp` VALUES (24, 1, '485421', '2026-02-20 11:16:23', '2026-02-20 11:11:23');
INSERT INTO `login_otp` VALUES (25, 1, '191086', '2026-02-20 11:42:13', '2026-02-20 11:37:13');
INSERT INTO `login_otp` VALUES (26, 1, '412466', '2026-02-20 12:00:24', '2026-02-20 11:55:24');
INSERT INTO `login_otp` VALUES (27, 2, '821467', '2026-02-20 12:01:39', '2026-02-20 11:56:39');
INSERT INTO `login_otp` VALUES (28, 7, '259636', '2026-02-20 12:26:05', '2026-02-20 12:21:05');
INSERT INTO `login_otp` VALUES (29, 1, '832832', '2026-02-20 12:27:42', '2026-02-20 12:22:42');
INSERT INTO `login_otp` VALUES (30, 1, '648991', '2026-02-20 13:02:10', '2026-02-20 12:57:10');
INSERT INTO `login_otp` VALUES (31, 2, '294323', '2026-02-20 13:02:57', '2026-02-20 12:57:57');
INSERT INTO `login_otp` VALUES (32, 2, '955723', '2026-02-20 13:35:46', '2026-02-20 13:30:46');
INSERT INTO `login_otp` VALUES (33, 1, '888429', '2026-02-20 13:36:34', '2026-02-20 13:31:34');
INSERT INTO `login_otp` VALUES (34, 2, '812781', '2026-02-20 13:38:14', '2026-02-20 13:33:14');
INSERT INTO `login_otp` VALUES (35, 9, '754006', '2026-02-20 13:46:49', '2026-02-20 13:41:49');
INSERT INTO `login_otp` VALUES (36, 1, '190236', '2026-02-20 14:48:58', '2026-02-20 14:43:58');
INSERT INTO `login_otp` VALUES (37, 1, '335657', '2026-02-20 15:20:43', '2026-02-20 15:15:43');
INSERT INTO `login_otp` VALUES (38, 1, '872158', '2026-02-20 15:43:31', '2026-02-20 15:38:31');

-- ----------------------------
-- Table structure for main_warehouse
-- ----------------------------
DROP TABLE IF EXISTS `main_warehouse`;
CREATE TABLE `main_warehouse`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `drug_name` varchar(255) CHARACTER SET tis620 COLLATE tis620_thai_ci NOT NULL,
  `lot_no` varchar(50) CHARACTER SET tis620 COLLATE tis620_thai_ci NOT NULL,
  `units` enum('เม็ด','แคปซูล','ขวด','หลอด','ซอง','Amp','แผ่น','แท่ง','แผง','กระปุก','อัน','Vial') CHARACTER SET tis620 COLLATE tis620_thai_ci NOT NULL DEFAULT 'เม็ด',
  `quantity` int NOT NULL,
  `price` decimal(10, 2) NULL DEFAULT NULL,
  `received_date` date NULL DEFAULT NULL,
  `expiry_date` date NULL DEFAULT NULL,
  `remaining` int NOT NULL,
  `note` text CHARACTER SET tis620 COLLATE tis620_thai_ci NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = tis620 COLLATE = tis620_thai_ci ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of main_warehouse
-- ----------------------------
INSERT INTO `main_warehouse` VALUES (2, 11, 'ACETYLCYSTEINE POWDER (ละลายเสมหะ) 100 MG', 'L52348', 'ซอง', 100, 1.50, '2026-01-28', '2030-01-31', 100, 'นำเข้าคลังใหญ่');
INSERT INTO `main_warehouse` VALUES (3, 5, 'ACETYLCYSTEINE POWDER (ละลายเสมหะ) 100 MG', 'L52348', 'ซอง', 100, 1.50, '2026-01-28', '2030-01-31', 100, 'นำเข้าคลังใหญ่');

-- ----------------------------
-- Table structure for non_drug_usage
-- ----------------------------
DROP TABLE IF EXISTS `non_drug_usage`;
CREATE TABLE `non_drug_usage`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `non_drug_id` int NULL DEFAULT NULL,
  `used_qty` int NULL DEFAULT NULL,
  `used_date` date NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `non_drug_id`(`non_drug_id`) USING BTREE,
  CONSTRAINT `non_drug_usage_ibfk_1` FOREIGN KEY (`non_drug_id`) REFERENCES `non_drug_warehouse` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = tis620 COLLATE = tis620_thai_ci ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of non_drug_usage
-- ----------------------------

-- ----------------------------
-- Table structure for non_drug_warehouse
-- ----------------------------
DROP TABLE IF EXISTS `non_drug_warehouse`;
CREATE TABLE `non_drug_warehouse`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `item_name` varchar(255) CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT NULL,
  `unit` varchar(50) CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT NULL,
  `quantity` int NULL DEFAULT NULL,
  `price` decimal(10, 2) NULL DEFAULT NULL,
  `received_date` date NULL DEFAULT NULL,
  `expiry_date` date NULL DEFAULT NULL,
  `remaining` int NULL DEFAULT NULL,
  `note` text CHARACTER SET tis620 COLLATE tis620_thai_ci NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = tis620 COLLATE = tis620_thai_ci ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of non_drug_warehouse
-- ----------------------------

-- ----------------------------
-- Table structure for opdconfig
-- ----------------------------
DROP TABLE IF EXISTS `opdconfig`;
CREATE TABLE `opdconfig`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `hospitalcode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hospitalname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of opdconfig
-- ----------------------------
INSERT INTO `opdconfig` VALUES (4, '02742', 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงหนองระเวียง', '2026-01-21 12:57:52');

-- ----------------------------
-- Table structure for sub_warehouse
-- ----------------------------
DROP TABLE IF EXISTS `sub_warehouse`;
CREATE TABLE `sub_warehouse`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `sub_name` varchar(255) CHARACTER SET tis620 COLLATE tis620_thai_ci NOT NULL,
  `drug_id` int NOT NULL,
  `quantity` int NOT NULL,
  `received_date` date NOT NULL,
  `remaining` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `drug_id`(`drug_id`) USING BTREE,
  CONSTRAINT `sub_warehouse_ibfk_1` FOREIGN KEY (`drug_id`) REFERENCES `main_warehouse` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = tis620 COLLATE = tis620_thai_ci ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of sub_warehouse
-- ----------------------------

-- ----------------------------
-- Table structure for units
-- ----------------------------
DROP TABLE IF EXISTS `units`;
CREATE TABLE `units`  (
  `unit_id` int NOT NULL AUTO_INCREMENT,
  `unit_code` varchar(20) CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT NULL,
  `unit_name` varchar(255) CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT 'active',
  PRIMARY KEY (`unit_id`) USING BTREE,
  UNIQUE INDEX `unit_code`(`unit_code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = tis620 COLLATE = tis620_thai_ci ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of units
-- ----------------------------
INSERT INTO `units` VALUES (0, '00000', 'ผู้ดูแลระบบข้อมูล', 'active');
INSERT INTO `units` VALUES (1, '02725', 'โรงพยาบาลส่งเสริมสุขภาพตำบลบ้านซึม', 'active');
INSERT INTO `units` VALUES (2, '02726', 'โรงพยาบาลส่งเสริมสุขภาพตำบลสัมฤทธิ์พัฒนา', 'active');
INSERT INTO `units` VALUES (3, '02730', 'โรงพยาบาลส่งเสริมสุขภาพตำบลท่าหลวง', 'active');
INSERT INTO `units` VALUES (4, '02731', 'โรงพยาบาลส่งเสริมสุขภาพตำบลจาร์ตำรา', 'active');
INSERT INTO `units` VALUES (5, '02735', 'โรงพยาบาลส่งเสริมสุขภาพตำบลนิคมสร้างตนเอง 1', 'active');
INSERT INTO `units` VALUES (6, '02736', 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองหญ้าขาว', 'active');
INSERT INTO `units` VALUES (7, '02737', 'โรงพยาบาลส่งเสริมสุขภาพตำบลดงน้อย', 'active');
INSERT INTO `units` VALUES (8, '02738', 'โรงพยาบาลส่งเสริมสุขภาพตำบลดงใหญ่', 'active');
INSERT INTO `units` VALUES (9, '02740', 'โรงพยาบาลส่งเสริมสุขภาพตำบลมะค่าระเว', 'active');
INSERT INTO `units` VALUES (10, '02741', 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองขาม', 'active');
INSERT INTO `units` VALUES (11, '02742', 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียง', 'active');
INSERT INTO `units` VALUES (12, '00236', 'สำนักงานสาธาณสุขอำเภอพิมาย', 'active');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_id` int NOT NULL,
  `username` varchar(100) CHARACTER SET tis620 COLLATE tis620_thai_ci NOT NULL,
  `password` varchar(255) CHARACTER SET tis620 COLLATE tis620_thai_ci NOT NULL,
  `fullname` varchar(255) CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT NULL,
  `role` enum('admin','staff','demo') CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') CHARACTER SET tis620 COLLATE tis620_thai_ci NULL DEFAULT 'active',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = tis620 COLLATE = tis620_thai_ci ROW_FORMAT = COMPACT;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 0, 'admin', '$2y$10$IwwHIKxBvvcpXFKQGksAYeiSALXzLuw7hGPZRw/fkb/y1K35uwjz6', 'นายวิษณุ เสาะสาย', 'admin', '2026-01-13 11:58:20', 'active');
INSERT INTO `users` VALUES (2, 11, '02742', '$2y$10$1iNyIotdd2jJdemXJ0eovemMFhOgwxYOBHQJyVl2CfoKBWuicQbHe', 'น.ส.สุจิตรา สดโคกกรวด', 'staff', '2026-01-16 09:10:59', 'active');
INSERT INTO `users` VALUES (3, 11, 'demo', '$2y$10$kM4v5MOzyNn6Y51mhLXJre/ORxyAnHWl1F550PI3EpDzbJhymZUqu', 'ทดสอบระบบ', 'demo', '2026-01-22 14:21:27', 'active');
INSERT INTO `users` VALUES (5, 1, '02725', '$2y$10$srrW5zm3qF/FEWgPM6XG4.6qp/DjTi2X3GxE/VR4iR77rF/9qCK5G', 'รพ.สต.บ้านซึม', 'staff', '2026-01-23 12:07:58', 'inactive');
INSERT INTO `users` VALUES (6, 2, '02726', '$2y$10$W3F7Q3tbA9FbazinIl0kwOjS6gDqwaHNLagqaOQn0yAaDy5ERet6G', 'รพ.ส.ต.บ้านสัมฤทธิ์พัฒนา', 'staff', '2026-01-23 14:17:44', 'active');
INSERT INTO `users` VALUES (7, 3, '02730', '$2y$10$2VvAcJzCQ5kE7fScHMst9.lWWGlqR2AyIn.7zvPZShEqEdA1seNNS', 'รพ.สต.ท่าหลวง', 'staff', '2026-01-26 09:53:35', 'active');
INSERT INTO `users` VALUES (8, 4, '02731', '$2y$10$gleR3mgBKwzTY1msPKFWceNGphofM.jtMqpDqhgHF4MkYvSnhdM7W', 'รพ.สต.จารย์ตำรา', 'staff', '2026-01-26 09:54:19', 'active');
INSERT INTO `users` VALUES (9, 5, '02735', '$2y$10$I31fYuugOXRQoVE4.a.TDOqyLD4nY6IBGvoDjqfof5WlTP1shc1W2', 'รพ.สต.นิคมฯ1', 'staff', '2026-01-26 09:55:35', 'active');
INSERT INTO `users` VALUES (10, 6, '02736', '$2y$10$InrIhXhGc4T56gv8Wv2rFeIgxissbIT5d380nz1jTlugyq4W06..2', 'รพ.สต.หนองหญ้าขาว', 'staff', '2026-01-26 10:01:13', 'active');
INSERT INTO `users` VALUES (11, 7, '02737', '$2y$10$z.bEEe46b8TwKrQPJ3YGLe4lCzDnuKv2yhUpRlmaA7pFVh9fLnvza', 'รพ.สต.ดงน้อย', 'staff', '2026-01-26 10:09:41', 'active');
INSERT INTO `users` VALUES (12, 8, '02738', '$2y$10$U7eCbfwz8kvGAEyglZbS/uIeT14cGKiXZTwWZsDRwfcWWbW4pEG/a', 'รพ.สต.ดงใหญ่', 'staff', '2026-01-26 10:09:59', 'active');
INSERT INTO `users` VALUES (13, 9, '02740', '$2y$10$G641Nx3LIUNBdTIruBiQ8OxoQox9OBB4fsj/pydcEQk/UDAUlbr5q', 'รพ.สต.มะค่าระเว', 'staff', '2026-01-26 10:10:16', 'active');
INSERT INTO `users` VALUES (14, 10, '02741', '$2y$10$BCMhMRF4an/n2vKin5bNsuiiZme6pDc//DBe7F11ajjdTqCculLa.', 'รพ.สต.หนองขาม', 'staff', '2026-01-26 10:10:36', 'active');
INSERT INTO `users` VALUES (15, 12, '00236', '$2y$10$SnYXDAoc2rVqfYuWDPWpsuVROe7XXLpsqqojKwYQ8soYvxf2Rkehe', 'สสอ.พิมาย', 'demo', '2026-02-11 21:59:45', 'active');

SET FOREIGN_KEY_CHECKS = 1;
