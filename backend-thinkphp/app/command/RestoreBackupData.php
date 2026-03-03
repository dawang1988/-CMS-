<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class RestoreBackupData extends Command
{
    protected function configure()
    {
        $this->setName('db:restore-backup')
            ->setDescription('恢复备份的数据');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始恢复备份数据...');
        $output->writeln('');

        $tenantId = '88888888';

        $output->writeln('恢复 ss_banner 数据...');
        Db::table('ss_banner')->where('id', 1)->delete();
        Db::table('ss_banner')->insert([
            'id' => 1,
            'tenant_id' => $tenantId,
            'store_id' => 0,
            'title' => '欢迎光临',
            'image' => 'https://via.placeholder.com/800x400',
            'link' => null,
            'sort' => 1,
            'status' => 1,
            'create_time' => '2026-02-08 00:19:42'
        ]);
        $output->writeln('  ✓ ss_banner: 1 条记录');

        $output->writeln('恢复 ss_help 数据...');
        Db::table('ss_help')->delete(true);
        $helpData = [
            [
                'id' => 1,
                'tenant_id' => $tenantId,
                'category' => 'usage',
                'title' => '如何预约房间？',
                'content' => '1. 选择门店\n2. 选择房间\n3. 选择时间段\n4. 提交订单\n5. 完成支付',
                'sort' => 1,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:24:35',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 2,
                'tenant_id' => $tenantId,
                'category' => 'usage',
                'title' => '如何开门？',
                'content' => '在订单详情页点击"开门"按钮，系统会自动打开房间门锁。',
                'sort' => 2,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:24:35',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 3,
                'tenant_id' => $tenantId,
                'category' => 'payment',
                'title' => '支持哪些支付方式？',
                'content' => '目前支持微信支付、余额支付、支付宝支付。',
                'sort' => 3,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:24:35',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 4,
                'tenant_id' => $tenantId,
                'category' => 'coupon',
                'title' => '如何使用优惠券？',
                'content' => '在提交订单时，选择可用的优惠券即可自动抵扣。',
                'sort' => 4,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:24:35',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 5,
                'tenant_id' => $tenantId,
                'category' => '账户关系',
                'title' => '如何充值？',
                'content' => '1. 登录小程序\n2. 进入"我的"页面\n3. 点击"充值"\n4. 选择充值金额\n5. 选择支付方式\n6. 完成支付',
                'sort' => 5,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:45:13',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 6,
                'tenant_id' => $tenantId,
                'category' => '预约关系',
                'title' => '如何预约房间',
                'content' => '1. 在首页选择门店\n2. 选择房间类型\n3. 选择时间段\n4. 确认订单\n5. 支付定金\n6. 预约成功',
                'sort' => 6,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:45:13',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 7,
                'tenant_id' => $tenantId,
                'category' => '会员关系',
                'title' => '会员卡使用说明',
                'content' => '1. 购买会员卡后自动激活\n2. 次卡按次数抵扣\n3. 时长卡按时间扣除\n4. 储值卡按余额抵扣\n5. 注意有效期限',
                'sort' => 7,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:45:13',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 8,
                'tenant_id' => $tenantId,
                'category' => '订单关系',
                'title' => '退单政策',
                'content' => '1. 未使用的订单可以申请退单\n2. 已使用的订单不支持退单\n3. 会员卡购买后不支持退单\n4. 特殊情况请联系客服',
                'sort' => 8,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:45:13',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 9,
                'tenant_id' => $tenantId,
                'category' => '使用指南',
                'title' => '设备使用指南',
                'content' => '1. 电脑开机密码：无需密码\n2. 游戏账号：自带steam账号\n3. 外设使用：扫码键鼠机自取\n4. 门锁维修：联系前台',
                'sort' => 9,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:45:13',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 10,
                'tenant_id' => $tenantId,
                'category' => '会员关系',
                'title' => '积分规则',
                'content' => '1. 消费100元获1积分\n2. 积分可兑换商品\n3. 积分可抵扣现金\n4. 积分永久有效',
                'sort' => 10,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:45:13',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 11,
                'tenant_id' => $tenantId,
                'category' => '活动关系',
                'title' => '优惠活动',
                'content' => '1. 每周三会员日8折\n2. 生日当天7折\n3. 充值满500送50\n4. 推荐好友奖励20元',
                'sort' => 11,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:45:13',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 12,
                'tenant_id' => $tenantId,
                'category' => '社交功能',
                'title' => '开黑场说明',
                'content' => '1. 发起开黑场\n2. 等待其他玩家加入\n3. 人数满后开始游戏\n4. 结束后可以互相评价',
                'sort' => 12,
                'view_count' => 1,
                'status' => 1,
                'create_time' => '2026-02-09 13:45:13',
                'update_time' => '2026-02-15 10:30:06'
            ]
        ];
        Db::table('ss_help')->insertAll($helpData);
        $output->writeln('  ✓ ss_help: 12 条记录');

        $output->writeln('恢复 ss_franchise 数据...');
        Db::table('ss_franchise')->delete(true);
        $franchiseData = [
            [
                'id' => 1,
                'tenant_id' => $tenantId,
                'user_id' => 1,
                'store_id' => 1,
                'name' => '张明',
                'contact_name' => '张明',
                'contact_phone' => '13900139001',
                'province' => '北京',
                'city' => '北京市',
                'district' => '朝阳区',
                'address' => '有5年棋牌茶楼经营经验，看好电玩城行业，计划30家',
                'status' => 0,
                'create_time' => '2026-02-06 13:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 2,
                'tenant_id' => $tenantId,
                'user_id' => 2,
                'store_id' => 2,
                'name' => '李华',
                'contact_name' => '李华',
                'contact_phone' => '13900139002',
                'province' => '上海',
                'city' => '上海市',
                'district' => '浦东新区',
                'address' => '在上海有200平米茶餐厅，想转型做电玩城，计划50家',
                'status' => 1,
                'create_time' => '2026-02-04 13:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 3,
                'tenant_id' => $tenantId,
                'user_id' => 3,
                'store_id' => 3,
                'name' => '王强',
                'contact_name' => '王强',
                'contact_phone' => '13900139003',
                'province' => '广州',
                'city' => '广州市',
                'district' => '天河区',
                'address' => '刚创业，想创业，预算30家',
                'status' => 2,
                'create_time' => '2026-02-02 13:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 4,
                'tenant_id' => $tenantId,
                'user_id' => 4,
                'store_id' => 4,
                'name' => '赵敏',
                'contact_name' => '赵敏',
                'contact_phone' => '13900139004',
                'province' => '深圳',
                'city' => '深圳市',
                'district' => '南山区',
                'address' => '有成功的棋牌经营经验，想拓展新业务，计划100家',
                'status' => 1,
                'create_time' => '2026-02-07 13:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 5,
                'tenant_id' => $tenantId,
                'user_id' => 5,
                'store_id' => 5,
                'name' => '孙丽',
                'contact_name' => '孙丽',
                'contact_phone' => '13900139005',
                'province' => '成都',
                'city' => '成都市',
                'district' => '华阳区',
                'address' => '本地人，熟悉成都市市场，计划50家',
                'status' => 0,
                'create_time' => '2026-02-08 13:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 6,
                'tenant_id' => $tenantId,
                'user_id' => 6,
                'store_id' => 6,
                'name' => '周伟',
                'contact_name' => '周伟',
                'contact_phone' => '13900139006',
                'province' => '杭州',
                'city' => '杭州市',
                'district' => '西湖区',
                'address' => '有电玩城从商从商经验，预算70家',
                'status' => 0,
                'create_time' => '2026-02-09 09:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 7,
                'tenant_id' => $tenantId,
                'user_id' => 7,
                'store_id' => 7,
                'name' => '吴军',
                'contact_name' => '吴军',
                'contact_phone' => '13900139007',
                'province' => '南京',
                'city' => '南京市',
                'district' => '江宁区',
                'address' => '大学生创业项目，预算40家',
                'status' => 2,
                'create_time' => '2026-02-03 13:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 8,
                'tenant_id' => $tenantId,
                'user_id' => 8,
                'store_id' => 8,
                'name' => '郑强',
                'contact_name' => '郑强',
                'contact_phone' => '13900139008',
                'province' => '武汉',
                'city' => '武汉市',
                'district' => '洪山区',
                'address' => '有多家门店运营经验，资金充足，计划50家',
                'status' => 1,
                'create_time' => '2026-02-06 13:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 9,
                'tenant_id' => $tenantId,
                'user_id' => 9,
                'store_id' => 9,
                'name' => '陈静',
                'contact_name' => '陈静',
                'contact_phone' => '13900139009',
                'province' => '西安',
                'city' => '西安市',
                'district' => '雁塔区',
                'address' => '看好西安市市场潜力，计划25家',
                'status' => 0,
                'create_time' => '2026-02-07 13:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ],
            [
                'id' => 10,
                'tenant_id' => $tenantId,
                'user_id' => 10,
                'store_id' => 10,
                'name' => '林涛',
                'contact_name' => '林涛',
                'contact_phone' => '13900139010',
                'province' => '重庆',
                'city' => '重庆市',
                'district' => '渝中区',
                'address' => '有商业地资源，预算65家',
                'status' => 0,
                'create_time' => '2026-02-08 13:32:34',
                'update_time' => '2026-02-15 10:30:06'
            ]
        ];
        Db::table('ss_franchise')->insertAll($franchiseData);
        $output->writeln('  ✓ ss_franchise: 10 条记录');

        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln('数据恢复完成！');
        $output->writeln('========================================');

        return 0;
    }
}