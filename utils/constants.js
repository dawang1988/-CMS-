/**
 * 通用常量定义
 * 统一管理小程序中的各种映射关系，避免重复定义
 */

// 房间类型映射
export const ROOM_TYPE_MAP = {
  0: '特价包',
  1: '小包',
  2: '中包',
  3: '大包',
  4: '豪包',
  5: '商务包',
  6: '斯洛克',
  7: '中式黑八',
  8: '美式球桌'
}

// 房间类型列表（用于picker选择）
export const ROOM_TYPE_LIST = [
  { id: null, name: '不限制' },
  { id: 1, name: '小包' },
  { id: 2, name: '中包' },
  { id: 3, name: '大包' },
  { id: 4, name: '豪包' },
  { id: 5, name: '商务包' },
  { id: 6, name: '斯洛克' },
  { id: 7, name: '中式黑八' },
  { id: 8, name: '美式球桌' }
]

// 房间业态分类
export const ROOM_CLASS_MAP = {
  0: '棋牌',
  1: '台球',
  2: 'KTV'
}

// 订单状态映射
export const ORDER_STATUS_MAP = {
  0: '未开始',
  1: '进行中',
  2: '已完成',
  3: '已取消'
}

// 订单状态列表（用于筛选）
export const ORDER_STATUS_LIST = [
  { text: '全部状态', value: -1 },
  { text: '未开始', value: 0 },
  { text: '进行中', value: 1 },
  { text: '已完成', value: 2 },
  { text: '已取消', value: 3 }
]

// 支付方式映射
export const PAY_TYPE_MAP = {
  0: '管理员',
  1: '微信',
  2: '余额',
  3: '团购',
  4: '套餐',
  5: '预订'
}

// 优惠券类型映射
export const COUPON_TYPE_MAP = {
  1: '抵扣券',
  2: '满减券',
  3: '加时券'
}

// 优惠券类型列表
export const COUPON_TYPE_LIST = [
  { id: 1, name: '抵扣券' },
  { id: 2, name: '满减券' },
  { id: 3, name: '加时券' }
]

// 优惠券状态映射
export const COUPON_STATUS_MAP = {
  0: '可使用',
  1: '已使用',
  2: '已过期'
}

// 商品订单状态映射
export const PRODUCT_ORDER_STATUS_MAP = {
  0: '待支付',
  1: '待配送',
  2: '已完成',
  3: '已取消'
}

// 用户类型映射
export const USER_TYPE_MAP = {
  11: '普通用户',
  12: '管理员',
  13: '超级管理员',
  14: '保洁员'
}

// 余额变动类型映射
export const BALANCE_TYPE_MAP = {
  1: '在线充值',
  2: '充值赠送',
  3: '订单支付',
  4: '订单退款',
  5: '管理员赠送',
  6: '管理员清空'
}

// 房间状态映射
export const ROOM_STATUS_MAP = {
  0: '禁用',
  1: '空闲',
  2: '待清洁',
  3: '使用中',
  4: '已预约'
}

// 排序选项
export const ORDER_SORT_LIST = [
  { text: '默认排序', value: '' },
  { text: '下单时间', value: 'createTime' },
  { text: '预约时间', value: 'startTime' }
]
