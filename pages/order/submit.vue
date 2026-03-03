<template>
<view class="container" :style="themeStyle + '; padding-top: ' + (statusBarHeight + 100) + 'rpx'">
  <view class="title-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
    <image src="/static/icon/home.png" style="width:52rpx;height:52rpx;" @tap="goHome"></image>
    <view class="storeName">{{ storeName }}</view>
  </view>
  <view class="top">
    <view class="roomInfo">
      <view class="left" @tap="imgYu">
        <view class="roomImg">
          <image class="img" v-if="roomImage" :src="roomImage" mode="scaleToFill"></image>
          <image class="img" v-else src="/static/logo.png" mode="scaleToFill"></image>
        </view>
      </view>
      <view class="right">
        <view class="roomName">
          {{ roominfodata.name || roominfodata.room_name || '加载中...' }}
          <view class="roomtype" v-if="roominfodata.type">
            [{{ roominfodata.type }}]
          </view>
        </view>
        <view class="tags" v-if="roominfodata.label">
          <view class="tag" v-for="(item, idx) in splitLabel(roominfodata.label)" :key="idx">{{ item }}</view>
        </view>
        <view class="price">
          ￥{{ roominfodata.price || 0 }}
          <text class="priceText">/小时</text>
          <text class="priceText" v-if="roominfodata.deposit">，押金￥{{ roominfodata.deposit }}</text>
        </view>
      </view>
      <view class="btns"><view class="btn" @tap="handleExchange">切换</view></view>
    </view>
    <view class="line3">
      <view class="time-line" v-if="timeText">{{ timeText }}已被预定</view>
      <view class="more" @tap="showReserve = true" v-if="orderTimeList.length > 1">更多</view>
    </view>
    <view class="timeSlot">
      <view class="time" v-for="(item, index) in timeHourAllArr" :key="index" :class="{ disabled: item.disable }">{{ item.hour }}</view>
    </view>
  </view>
  <view class="info">
    <view class="btn" @tap="openSubmit(0)">
      <view class="left"><view class="text1">小时开台</view><view class="text2">选择指定消费时长开台</view></view>
      <view class="right">开台</view>
    </view>
    <view class="btn" @tap="openSubmit(1)">
      <view class="left">
        <view class="text1">团购开台</view>
        <view class="text2">使用美团、抖音等团购券开台</view>
        <view class="platform-icons">
          <view class="platform-tag mt">美团</view>
          <view class="platform-tag dp">点评</view>
          <view class="platform-tag dy">抖音</view>
        </view>
      </view>
      <view class="right">开台</view>
    </view>
    <view class="btn" @tap="openSubmit(2)">
      <view class="left"><view class="text1">套餐开台</view><view class="text2">选择优惠套餐开台</view></view>
      <view class="right">开台</view>
    </view>
    <view class="btn" @tap="openSubmit(3)" v-if="roominfodata.pre_pay_amount > 0">
      <view class="left"><view class="text1">押金开台</view><view class="text2">关灯结账后退回剩余金额</view></view>
      <view class="right">开台</view>
    </view>
  </view>
  <view class="content">
    <view class="notice-title">消费须知</view>
    <view class="line">1.订单开始前可以在订单详情更换房间</view>
    <view class="line">2.订单未开始下单5分钟内可取消，超时联系客服处理</view>
    <view class="line">3.当您迟到消费，订单会按原预定时间自动开始计费</view>
    <view class="line">4.深夜消费时，请您放低音量，不要影响邻居休息</view>
    <view class="line">5.有任何问题可以联系门店客服进行处理</view>
  </view>

  <!-- 房间预定时间列表 -->
  <u-popup :show="showReserve" mode="center" round="14" @close="showReserve = false">
    <view class="reserve-box">
      <view class="popup-title">预定时间</view>
      <view class="time-line-item" v-for="(item, index) in orderTimeList" :key="index">
        <view class="dot"></view>
        <view class="time-tag"><text>{{ item }}</text> <text>已预订</text></view>
      </view>
      <button class="reserve-btn" @tap="showReserve = false">知道了</button>
    </view>
  </u-popup>

  <!-- 小时开台弹窗 -->
  <u-popup :show="xiaoshiShow" mode="bottom" round="14" :customStyle="popupStyle" @close="xiaoshiShow = false">
    <view class="popup-content">
      <view class="popup-title">小时开台</view>
      <view class="time-items">
        <view class="time-item" :class="{ active: select_time_index === index }" v-for="(item, index) in hour_options" :key="index" @tap="selectTimeHour(index, item)">{{ item }}<view style="font-size: 20rpx;">小时</view></view>
      </view>
      <!-- 包场选项 -->
      <view class="time-items" v-if="roominfodata.morning_price || roominfodata.afternoon_price || roominfodata.night_price || roominfodata.tx_price">
        <view class="time-item item2" :class="{ active: select_time_index === 9991 }" v-if="roominfodata.morning_price" @tap="selectBaochang(9991)">
          <view>上午场</view><view style="font-size: 20rpx;">{{ roominfodata.morning_start || 9 }}~{{ roominfodata.morning_end || 13 }}时</view><view class="bc-price">￥{{ roominfodata.morning_price }}</view>
        </view>
        <view class="time-item item2" :class="{ active: select_time_index === 9992 }" v-if="roominfodata.afternoon_price" @tap="selectBaochang(9992)">
          <view>下午场</view><view style="font-size: 20rpx;">{{ roominfodata.afternoon_start || 13 }}~{{ roominfodata.afternoon_end || 18 }}时</view><view class="bc-price">￥{{ roominfodata.afternoon_price }}</view>
        </view>
        <view class="time-item item2" :class="{ active: select_time_index === 9993 }" v-if="roominfodata.night_price" @tap="selectBaochang(9993)">
          <view>夜间场</view><view style="font-size: 20rpx;">{{ roominfodata.night_start || 18 }}~{{ roominfodata.night_end || 23 }}时</view><view class="bc-price">￥{{ roominfodata.night_price }}</view>
        </view>
        <view class="time-item item2" :class="{ active: select_time_index === 9994 }" v-if="roominfodata.tx_price" @tap="selectBaochang(9994)">
          <view>通宵场</view><view style="font-size: 20rpx;">{{ roominfodata.tx_start || 23 }}~{{ roominfodata.tx_end || 8 }}时</view><view class="bc-price">￥{{ roominfodata.tx_price }}</view>
        </view>
      </view>
      <view class="timer">
        <text class="timer-title">时间</text>
        <view class="time active" @tap="openTimeSelect"><text>{{ view_begin_time }}</text></view>
        <text class="divide">到</text>
        <view class="time"><text>{{ view_end_time }}</text></view>
      </view>
      <view class="coupon-section">
        <view class="coupon-top" @tap="couponShow = true">
          <view class="c-left"><iconfont name="cuxiaohuodong-youhuiquan" size="28"></iconfont><view>优惠卡券</view></view>
          <view class="c-right" v-if="couponList.length > 0"><view class="couponCount">{{ couponList.length }}张可用</view><text style="font-size:28rpx;color:#999;">›</text></view>
          <view class="c-right" v-else><view class="couponCount" style="color:#999;">暂无可用</view></view>
        </view>
        <view class="couponSelect" v-if="submit_couponName"><view class="couponName">{{ submit_couponName }}</view><view class="clearCoupon" @tap.stop="clearCoupon">清除</view></view>
      </view>
      <!-- 会员卡选择 -->
      <view class="coupon-section" v-if="cardEnabled">
        <view class="coupon-top" @tap="cardList.length > 0 ? cardShow = true : goBuyCard()">
          <view class="c-left"><iconfont name="qianbao" size="28"></iconfont><view>会员卡抵扣</view></view>
          <view class="c-right" v-if="cardList.length > 0"><view class="couponCount">{{ cardList.length }}张可用</view><text style="font-size:28rpx;color:#999;">›</text></view>
          <view class="c-right" v-else><view class="couponCount" :style="{color: themeColor}">购买会员卡</view><text :style="{fontSize:'28rpx',color: themeColor}">›</text></view>
        </view>
        <view class="couponSelect" v-if="submit_cardName"><view class="couponName">{{ submit_cardName }}</view><view class="clearCoupon" @tap.stop="clearCard">清除</view></view>
      </view>
      <view class="order-info">
        <view class="info-line"><view class="bold">订单时长</view><view>{{ order_hour }}小时</view></view>
        <view class="info-line"><view class="bold">结束时间</view><view>{{ submit_end_time }}</view></view>
        <view class="info-line" v-if="balance.vip_discount && balance.vip_discount < 100"><text class="bold">会员折扣</text><text style="color:red"> {{ balance.vip_name }} {{ (balance.vip_discount / 10).toFixed(1) }}折</text></view>
      </view>
      <view class="order-bar">
        <view class="bar-left">
          <view class="total color-attention">￥{{ payPrice }}元</view>
          <view class="total color-attention" v-if="cardDeductAmount > 0" :style="{fontSize:'24rpx',color: themeColor}">（会员卡抵扣￥{{ cardDeductAmount }}）</view>
          <view class="total color-attention" v-if="roominfodata.deposit" style="font-size:24rpx">（含押金{{ roominfodata.deposit }}元）</view>
        </view>
        <view class="bar-right">
          <button class="pay-btn wx" @tap="SubmitOrderInfoData(1)"><iconfont name="weixinzhifu" size="26" color="#fff"></iconfont><view style="margin: 0rpx 15rpx;">微信</view></button>
          <button class="pay-btn yue" @tap="SubmitOrderInfoData(2)"><iconfont name="yue" size="26" color="#fff"></iconfont><view class="desc"><view>余￥{{ balance.balance || 0 }}</view><view>赠￥{{ balance.gift_balance || 0 }}</view></view></button>
        </view>
      </view>
    </view>
  </u-popup>

  <!-- 优惠券选择弹窗 -->
  <u-popup :show="couponShow" mode="bottom" round="14" :customStyle="popupStyle50" @close="couponShow = false">
    <view class="popup-content">
      <view class="popup-title">优惠券选择</view>
      <scroll-view scroll-y class="couponList">
        <view class="couponItem" v-for="(item, index) in couponList" :key="index">
          <view class="coupon-row">
            <view class="c1">
              <view v-if="item.type === 1 || item.type === 3"><view class="cprice">{{ item.amount }}小时</view><view class="ctype">{{ item.type === 1 ? '抵扣券' : '加时券' }}</view></view>
              <view v-if="item.type === 2"><view class="cprice"><text>￥</text>{{ item.amount }}元</view><view class="ctype">满减券</view></view>
            </view>
            <view class="c2"><view class="item-name">{{ item.name }}</view><view class="item-date">{{ item.end_time }} 过期</view></view>
            <view class="c3" @tap="selectCouponInfo(index, item)"><view class="radio" :class="{ selected: index === select_coupon_index }"></view></view>
          </view>
        </view>
      </scroll-view>
    </view>
  </u-popup>

  <!-- 会员卡选择弹窗 -->
  <u-popup :show="cardShow" mode="bottom" round="14" :customStyle="popupStyle50" @close="cardShow = false">
    <view class="popup-content">
      <view class="popup-title">会员卡选择</view>
      <scroll-view scroll-y class="couponList">
        <view class="couponItem" v-for="(item, index) in cardList" :key="index">
          <view class="coupon-row">
            <view class="c1">
              <view v-if="item.type === 1"><view class="cprice">{{ item.remain_value }}次</view><view class="ctype">次卡</view></view>
              <view v-if="item.type === 2"><view class="cprice">{{ item.remain_hours || Math.round(item.remain_value / 60) }}h</view><view class="ctype">时长卡</view></view>
              <view v-if="item.type === 3"><view class="cprice"><text>￥</text>{{ item.remain_value }}</view><view class="ctype">储值卡</view></view>
            </view>
            <view class="c2">
              <view class="item-name">{{ item.name }}</view>
              <view class="item-date">{{ item.expire_text }} 过期</view>
              <view class="item-discount" v-if="item.discount < 1">额外{{ (item.discount * 10).toFixed(1) }}折</view>
            </view>
            <view class="c3" @tap="selectCardInfo(index, item)"><view class="radio" :class="{ selected: index === select_card_index }"></view></view>
          </view>
        </view>
      </scroll-view>
    </view>
  </u-popup>

  <!-- 团购开台弹窗 -->
  <u-popup :show="tuangouShow" mode="bottom" round="14" :customStyle="popupStyle60" @close="tuangouShow = false">
    <view class="popup-content">
      <view class="popup-title">团购开台</view>
      <view class="tuangouBg"><image class="img" src="/static/img/tuangou.png" mode="widthFix"></image></view>
      <view class="tgScan">
        <input class="input" maxlength="35" placeholder="输入/粘贴团购券码" v-model="groupPayNo" @input="groupVerified = false" />
        <iconfont name="scan-qr-code" size="28" @tap="scanCode"></iconfont>
      </view>
      <view class="tgTitle" v-if="groupPayTitle">{{ groupPayTitle }}</view>
      <view class="timer" v-if="groupPayTitle">
        <text class="timer-title">时间</text>
        <view class="time active" @tap="openTimeSelect"><text>{{ view_begin_time }}</text></view>
        <text class="divide">到</text>
        <view class="time"><text>{{ view_end_time }}</text></view>
      </view>
      <view class="order-info">
        <view class="info-line"><view class="bold">订单时长</view><view>{{ order_hour }}小时</view></view>
        <view class="info-line"><view class="bold">结束时间</view><view>{{ submit_end_time }}</view></view>
      </view>
      <view class="order-bar">
        <view class="bar-left"><view class="total color-attention">￥{{ payPrice }}元</view></view>
        <view class="bar-right">
          <button class="pay-btn tg" v-if="groupVerified" @tap="SubmitOrderInfoData(3)"><view style="margin: 0rpx 25rpx;">团购兑换</view></button>
          <button class="pay-btn tg" v-else @tap="checkGroup" style="opacity:0.9;"><view style="margin: 0rpx 25rpx;">验证券码</view></button>
        </view>
      </view>
    </view>
  </u-popup>

  <!-- 套餐开台弹窗 -->
  <u-popup :show="taocanShow" mode="bottom" round="14" :customStyle="popupStyle75" @close="taocanShow = false">
    <view class="popup-content">
      <view class="popup-title">套餐选择</view>
      <view class="timer">
        <text class="timer-title">时间</text>
        <view class="time active" @tap="openTimeSelect"><text>{{ view_begin_time }}</text></view>
        <text class="divide">到</text>
        <view class="time"><text>{{ view_end_time }}</text></view>
      </view>
      <!-- 会员卡选择（套餐开台） -->
      <view class="coupon-section" v-if="cardEnabled" style="margin:20rpx 0;">
        <view class="coupon-top" @tap="cardList.length > 0 ? cardShow = true : goBuyCard()">
          <view class="c-left"><iconfont name="qianbao" size="28"></iconfont><view>会员卡抵扣</view></view>
          <view class="c-right" v-if="cardList.length > 0"><view class="couponCount">{{ cardList.length }}张可用</view><text style="font-size:28rpx;color:#999;">›</text></view>
          <view class="c-right" v-else><view class="couponCount" :style="{color: themeColor}">购买会员卡</view><text :style="{fontSize:'28rpx',color: themeColor}">›</text></view>
        </view>
        <view class="couponSelect" v-if="submit_cardName"><view class="couponName">{{ submit_cardName }}</view><view class="clearCoupon" @tap.stop="clearCard">清除</view></view>
      </view>
      <view class="order-info">
        <view class="info-line"><view class="bold">订单时长</view><view>{{ order_hour }}小时</view></view>
        <view class="info-line"><view class="bold">结束时间</view><view>{{ submit_end_time }}</view></view>
        <view class="info-line" v-if="balance.vip_discount && balance.vip_discount < 100"><text class="bold">会员折扣</text><text style="color:red"> {{ balance.vip_name }} {{ (balance.vip_discount / 10).toFixed(1) }}折</text></view>
      </view>
      <scroll-view scroll-y class="pkgList">
        <view class="pkgItem" v-for="(item, index) in pkgList" :key="index">
          <view class="pkg-row">
            <view class="c1">
              <view class="pkg-price">￥{{ item.price }}</view>
              <view class="pkg-hour">{{ item.hours || item.hour }}小时</view>
              <view class="pkg-original" v-if="item.original_price && item.original_price > item.price">原价￥{{ item.original_price }}</view>
            </view>
            <view class="c2">
              <view class="item-name">{{ item.pkg_name || item.name }}</view>
              <!-- 使用规则提示 -->
              <view class="item-rules">
                <view class="rule-item" v-if="item.enable_week && item.enable_week.length > 0 && item.enable_week.length < 7">
                  <iconfont name="rili" size="20" color="#666"></iconfont>
                  <text>{{ formatEnableWeekShort(item.enable_week) }}</text>
                </view>
                <view class="rule-item" v-if="item.enable_time && item.enable_time.start && item.enable_time.end && (item.enable_time.start !== '00:00' || item.enable_time.end !== '23:59')">
                  <iconfont name="shijian" size="20" color="#666"></iconfont>
                  <text>{{ item.enable_time.start }}-{{ item.enable_time.end }}</text>
                </view>
                <view class="rule-item" v-if="item.balance_buy === 0">
                  <iconfont name="tishi" size="20" color="#ff6b6b"></iconfont>
                  <text style="color:#ff6b6b;">仅限微信支付</text>
                </view>
              </view>
            </view>
            <view class="c3" @tap="selectPkgInfo(index, item)"><view class="radio" :class="{ selected: index === select_pkg_index }"></view></view>
          </view>
        </view>
      </scroll-view>
      <view class="order-bar">
        <view class="bar-left"><view class="total color-attention">￥{{ payPrice }}元</view></view>
        <view class="bar-right">
          <button class="pay-btn wx" @tap="SubmitOrderInfoData(1)"><iconfont name="weixinzhifu" size="26" color="#fff"></iconfont><view style="margin: 0rpx 15rpx;">微信</view></button>
          <button class="pay-btn yue" @tap="SubmitOrderInfoData(2)"><iconfont name="yue" size="26" color="#fff"></iconfont><view class="desc"><view>余￥{{ balance.balance || 0 }}</view><view>赠￥{{ balance.gift_balance || 0 }}</view></view></button>
        </view>
      </view>
    </view>
  </u-popup>

  <!-- 押金开台弹窗 -->
  <u-popup :show="yajinShow" mode="bottom" round="14" :customStyle="popupStyle45" @close="yajinShow = false">
    <view class="popup-content">
      <view class="popup-title">押金开台</view>
      <view class="timer">
        <text class="timer-title">时间</text>
        <view class="time active" @tap="openTimeSelect"><text>{{ view_begin_time }}</text></view>
        <text class="divide">开始</text>
      </view>
      <view class="order-info">
        <view class="info-line"><view class="bold">开始时间</view><view>{{ submit_begin_time }}</view></view>
        <view class="info-line"><text class="bold">预付费用</text><text style="color: red;">￥{{ roominfodata.pre_pay_amount || roominfodata.pre_price || 0 }}元</text></view>
        <view class="info-line" v-if="roominfodata.min_charge"><text class="bold">最低消费</text><text style="color: red;">{{ roominfodata.min_charge }}元</text></view>
        <view class="info-line"><view class="bold">计费价格</view><view>{{ roominfodata.price || 0 }}元/小时</view></view>
      </view>
      <view class="notice-box">
        <view class="bold">押金在您订单结束30分钟内自动原路退还</view>
        <view class="bold">提前结束订单，不要破坏房间物品</view>
      </view>
      <view class="order-bar">
        <view class="bar-left"><view class="total color-attention">￥{{ payPrice }}元</view></view>
        <view class="bar-right">
          <button class="pay-btn wx" @tap="SubmitOrderInfoData(1)"><iconfont name="weixinzhifu" size="26" color="#fff"></iconfont><view style="margin: 0rpx 15rpx;">微信支付</view></button>
        </view>
      </view>
    </view>
  </u-popup>

  <!-- 时间选择弹窗 -->
  <u-popup :show="timeSelectShow" mode="bottom" round="14" @close="timeSelectShow = false">
    <view class="time-select-popup">
      <view class="ts-top">
        <view class="ts-title">选择开始时间</view>
        <view class="time-slot">
          <view v-for="(item, index) in timeSelectDays" :key="index" :class="{ active: dayIndex === index }" @tap="handleDayChange(index)">
            <text>{{ item.name }}</text>
          </view>
        </view>
      </view>
      <view class="picker-area">
        <picker-view :value="pickerValue" @change="onPickerChange" class="time-picker" indicator-style="height: 50px;">
          <picker-view-column>
            <view class="picker-item" v-for="h in hourOptions" :key="h">{{ h < 10 ? '0' + h : h }}时</view>
          </picker-view-column>
          <picker-view-column>
            <view class="picker-item" v-for="m in minuteOptions" :key="m">{{ m < 10 ? '0' + m : m }}分</view>
          </picker-view-column>
        </picker-view>
      </view>
      <view class="ts-conflict" v-if="pickerConflict">
        <text style="color:#ee0a24;">{{ pickerConflict }}</text>
      </view>
      <button class="ts-confirm-btn" @tap="confirmTimePicker">确认选择</button>
    </view>
  </u-popup>
</view>
</template>

<script>
import { legacyRequest } from '@/utils/http.js'
import iconfont from '@/components/iconfont/iconfont.vue'
import config from '@/config/index.js'

export default {
  components: { iconfont },
  data() {
    return {
      statusBarHeight: 0,
      storeId: '',
      roomId: '',
      goPage: '',
      groupPayNo: '',
      groupPayTitle: '',
      groupCouponId: 0,
      timeHourAllArr: [],
      roominfodata: {},
      orderTimeList: [],
      hour_options: [],
      couponList: [],
      showCouponSelect: false,
      submit_couponId: '',
      submit_couponName: '',
      select_coupon_index: -1,
      timeText: '',
      submit_begin_time: '',
      view_begin_time: '',
      submit_end_time: '',
      view_end_time: '',
      showReserve: false,
      xiaoshiShow: false,
      tuangouShow: false,
      taocanShow: false,
      yajinShow: false,
      typeIndex: 0,
      couponShow: false,
      select_time_index: 0,
      select_pkg_index: 0,
      payPrice: 0,
      order_hour: 0,
      orderNo: '',
      timeSelectShow: false,
      dayIndex: 0,
      timeSelectList: [],
      timeSelectDays: [],
      pickerValue: [0, 0],
      pickerConflict: '',
      pkgList: null,
      pkgId: '',
      nightLong: false,
      balance: {},
      // 会员卡相关
      cardEnabled: false,
      cardList: [],
      cardShow: false,
      submit_cardId: '',
      submit_cardName: '',
      select_card_index: -1,
      cardDeductAmount: 0, // 会员卡抵扣金额
      popupStyle: { 'background': 'linear-gradient(180deg, #C9FFD7 2%, #FFFFFF 80%)' },
      popupStyle50: { 'background': '#fff', 'height': '50vh' },
      popupStyle60: { 'background': 'linear-gradient(180deg, #C9FFD7 2%, #FFFFFF 80%)', 'height': '60vh' },
      popupStyle75: { 'background': 'linear-gradient(180deg, #C9FFD7 2%, #FFFFFF 80%)', 'height': '75vh' },
      popupStyle45: { 'background': 'linear-gradient(180deg, #C9FFD7 2%, #FFFFFF 80%)' },
      preOrderData: null, // 保存preOrder返回的价格数据
      groupVerified: false, // 团购券是否验证通过
      // 支付方式开关（后台配置）
      payMethodWechat: { enabled: false, config_ok: false },
      payMethodBalance: { enabled: false },
      payMethodGroup: { enabled: false, platform_ok: false },
    }
  },
  computed: {
    storeName() {
      return this.roominfodata ? (this.roominfodata.store_name || this.roominfodata.storeName || '') : ''
    },
    roomImage() {
      if (!this.roominfodata) return ''
      let urls = this.roominfodata.imageUrls || this.roominfodata.image_urls || ''
      let img = ''
      if (typeof urls === 'string' && urls) {
        img = urls.split(',')[0]
      } else if (Array.isArray(urls) && urls.length > 0) {
        img = urls[0]
      }
      if (img && !img.startsWith('http')) img = config.imageBase + img
      return img
    },
    // 小时选项 0-23
    hourOptions() {
      const arr = []
      for (let i = 0; i < 24; i++) {
        arr.push(i)
      }
      return arr
    },
    // 分钟选项 0-59
    minuteOptions() {
      const arr = []
      for (let i = 0; i < 60; i++) {
        arr.push(i)
      }
      return arr
    }
  },
  onLoad(options) {
    console.log('【调试】onLoad options:', options)
    const sysInfo = uni.getSystemInfoSync()
    this.statusBarHeight = sysInfo.statusBarHeight || 0
    this.storeId = options.storeId || ''
    this.roomId = options.roomId || ''
    this.goPage = options.goPage || ''
    this.groupPayNo = options.groupPayNo || ''
    console.log('【调试】storeId:', this.storeId, 'roomId:', this.roomId)
    const startDate = new Date()
    this.submit_begin_time = this.formatDate(startDate).text
    uni.setStorageSync('global_store_id', this.storeId)
  },
  onShow() {
    const app = getApp()
    console.log('【调试】onShow, isLogin:', app.globalData.isLogin, 'roomId:', this.roomId)
    this.xiaoshiShow = false
    this.tuangouShow = this.groupPayNo ? true : false
    this.taocanShow = false
    this.yajinShow = false

    // 获取可用支付方式
    this.fetchPayMethods()

    if (this.orderNo) {
      this.getOrderInfoData()
    } else {
      if (!this.goPage) {
        uni.showLoading({ title: '加载中...' })
        setTimeout(() => { uni.hideLoading() }, 1000)
      }
      // 修复：检查 roominfodata 是否有 name 属性，而不是检查是否为空对象
      if (!this.roominfodata || !this.roominfodata.name) {
        this.getroomInfodata(this.roomId)
      }
      if (this.groupPayNo) {
        this.tuangouShow = true
        this.typeIndex = 1
        this.checkGroup()
      }
    }
  },

  methods: {
    formatDate(dateTime) {
      const date = new Date(dateTime)
      let year = date.getFullYear()
      let month = date.getMonth() + 1
      let day = date.getDate()
      let hour = date.getHours()
      let minute = date.getMinutes()
      if (minute < 10) minute = `0${minute}`
      if (hour < 10) hour = `0${hour}`
      if (day < 10) day = `0${day}`
      if (month < 10) month = `0${month}`
      return { text: `${year}/${month}/${day} ${hour}:${minute}`, year, month, day, hour, minute }
    },
    formatViewDate(dateTime) {
      const date = new Date(dateTime)
      let hour = date.getHours()
      let minute = date.getMinutes()
      if (minute < 10) minute = `0${minute}`
      if (hour < 10) hour = `0${hour}`
      return { text: `${hour}:${minute}` }
    },
    splitLabel(label) {
      if (!label) return []
      return label.split(',').filter(item => item)
    },
    timeFilter(startTime, endTime) {
      if (startTime && !endTime) {
        const d = new Date(startTime)
        return `${(d.getMonth()+1).toString().padStart(2,'0')}月${d.getDate().toString().padStart(2,'0')}日${d.getHours().toString().padStart(2,'0')}:${d.getMinutes().toString().padStart(2,'0')}`
      } else if (startTime && endTime) {
        const s = new Date(startTime)
        const e = new Date(endTime)
        return `${(s.getMonth()+1).toString().padStart(2,'0')}月${s.getDate().toString().padStart(2,'0')}日${s.getHours().toString().padStart(2,'0')}:${s.getMinutes().toString().padStart(2,'0')}-${e.getHours().toString().padStart(2,'0')}:${e.getMinutes().toString().padStart(2,'0')}`
      }
      return ''
    },
    goHome() {
      uni.switchTab({ url: '/pages/door/index' })
    },
    handleExchange() {
      uni.navigateTo({ url: `/pages/index/index?store_id=${this.storeId}` })
    },
    imgYu() {
      if (this.roomImage) {
        uni.previewImage({ urls: [this.roomImage] })
      }
    },
    // 获取房间信息
    getroomInfodata(roomId) {
      const app = getApp()
      console.log('【调试】获取房间信息，roomId:', roomId)
      if (!roomId) {
        console.error('【错误】roomId 为空')
        uni.showModal({ content: '房间ID不能为空', showCancel: false })
        return
      }
      legacyRequest(
        '/member/index/getRoomInfo/' + roomId, '1', 'post',
        { room_id: roomId },
        app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
        '获取中...',
        (info) => {
          console.log('【调试】获取房间信息响应:', info)
          if (info.code == 0) {
            console.log('【调试】房间数据:', info.data)
            const timeText = this.timeFilter(info.data.startTime || info.data.start_time, info.data.endTime || info.data.end_time)
            let orderTimeList = []
            const otl = info.data.orderTimeList || info.data.order_time_list
            if (otl) {
              orderTimeList = otl.map(item => this.timeFilter(item.startTime || item.start_time, item.endTime || item.end_time))
            }
            const minHour = info.data.minHour || info.data.min_hour || 1
            let hour_options = []
            for (let i = 0; i < 9; i++) {
              hour_options.push(minHour + i)
            }
            this.roominfodata = info.data
            console.log('【调试】设置 roominfodata:', this.roominfodata)
            const ts = info.data.timeSlot || info.data.time_slot || []
            this.timeHourAllArr = ts.slice(0, 24)
            this.timeText = timeText
            this.orderTimeList = orderTimeList
            this.hour_options = hour_options
            const tsl = info.data.timeSelectLists || info.data.time_select_lists || []
            if (tsl.length > 0) {
              this.timeSelectList = tsl[0].selectList || tsl[0].select_list || []
              this.timeSelectDays = tsl.map((item, idx) => ({ name: item.name || item.dayName || `第${idx+1}天`, index: idx }))
            }
          } else {
            console.error('【错误】获取房间信息失败:', info.msg)
            uni.showModal({ content: info.msg || '获取房间信息失败', showCancel: false })
          }
        },
        (err) => {
          console.error('【错误】请求失败:', err)
        }
      )
    },

    // 获取门店余额
    getStoreBalance() {
      const app = getApp()
      if (app.globalData.isLogin) {
        legacyRequest(
          '/member/user/getStoreBalance/' + this.storeId, '1', 'get', {},
          app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
          '',
          (info) => {
            if (info.code == 0) {
              this.balance = info.data
            }
          },
          () => {}
        )
      }
    },
    // 获取可用支付方式
    fetchPayMethods() {
      const app = getApp()
      if (app.globalData.isLogin && this.storeId) {
        legacyRequest(
          '/member/order/getPayMethods', '1', 'get',
          { store_id: this.storeId },
          app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
          '',
          (info) => {
            if (info.code == 0) {
              this.payMethodWechat = info.data.wechat || { enabled: false, config_ok: false }
              this.payMethodBalance = info.data.balance || { enabled: false }
              this.payMethodGroup = info.data.group || { enabled: false, platform_ok: false }
            }
          },
          () => {}
        )
      }
    },
    // 打开开台弹窗
    openSubmit(index) {
      const app = getApp()
      if (!app.globalData.isLogin) {
        uni.navigateTo({ url: '/pages/user/login' })
        return
      }
      this.typeIndex = index
      if (index === 0) {
        this.getStoreBalance()
        this.xiaoshiShow = true
        this.showCouponSelect = true
        this.order_hour = this.roominfodata.minHour || this.roominfodata.min_hour || 1
        this.submit_couponId = ''
        this.submit_couponName = ''
        this.submit_cardId = ''
        this.submit_cardName = ''
        this.select_card_index = -1
        this.select_pkg_index = 0
        this.pkgId = ''
        this.nightLong = false
        this.MathDate()
        this.MathPrice(1, null, false, false)
        this.getCouponListData()
      } else if (index === 1) {
        this.tuangouShow = true
        this.order_hour = 0
        this.payPrice = 0
        this.submit_couponId = ''
        this.submit_couponName = ''
        this.submit_cardId = ''
        this.submit_cardName = ''
        this.select_card_index = -1
        this.select_pkg_index = 0
        this.pkgId = ''
      } else if (index === 2) {
        this.nightLong = false
        this.getStoreBalance()
        this.submit_cardId = ''
        this.submit_cardName = ''
        this.select_card_index = -1
        this.getCardListData() // 获取会员卡列表
        if (!this.pkgList) {
          this.getPkgList().then(() => {
            if (!this.pkgList || this.pkgList.length == 0) {
              uni.showToast({ title: '门店未设置套餐', icon: 'none' })
              return
            }
            this.taocanShow = true
          })
        } else {
          this.taocanShow = true
        }
        if (this.pkgList && this.select_pkg_index >= 0 && this.pkgList[this.select_pkg_index]) {
          this.pkgId = this.pkgList[this.select_pkg_index].pkgId || this.pkgList[this.select_pkg_index].pkg_id
          this.payPrice = this.pkgList[this.select_pkg_index].price
          this.order_hour = this.pkgList[this.select_pkg_index].hours || this.pkgList[this.select_pkg_index].hour
          this.MathDate()
        }
      } else if (index === 3) {
        this.nightLong = false
        this.getStoreBalance()
        const prePrice = this.roominfodata.pre_pay_amount || this.roominfodata.prePrice || this.roominfodata.pre_price
        if (!prePrice) {
          uni.showModal({ title: '温馨提示', content: '管理员未设置押金开台，请选择其他开台方式', showCancel: false })
          return
        }
        this.payPrice = prePrice
        this.yajinShow = true
        this.order_hour = 0
        this.select_pkg_index = 0
        this.pkgId = ''
        this.MathDate()
      }
    },

    // 选择小时
    selectTimeHour(index, hour) {
      this.select_time_index = index
      if (this.select_time_index > 999) {
        this.MathBaochang()
        return
      }
      this.showCouponSelect = true
      this.submit_couponId = ''
      this.submit_couponName = ''
      this.order_hour = hour
      this.MathDate()
      this.MathPrice(1, null, false, false)
    },
    // 选择包场
    selectBaochang(idx) {
      this.select_time_index = idx
      this.MathBaochang()
    },
    // 检查时间是否在某个场次的有效范围内
    isInTimeSlot(hours) {
      switch (this.select_time_index) {
        case 9991: return hours >= 9 && hours < 13
        case 9992: return hours >= 13 && hours < 18
        case 9993: return hours >= 18 && hours < 23
        case 9994: return hours >= 23 || hours < 8
        default: return false
      }
    },
    MathDate() {
      let startDate = new Date()
      if (this.submit_begin_time) {
        startDate = new Date(this.submit_begin_time)
      }
      let order_hour = this.order_hour
      let endDate = new Date(startDate.getTime() + 1000 * 60 * 60 * order_hour)
      this.submit_couponId = ''
      this.submit_couponName = ''
      this.submit_begin_time = this.formatDate(startDate.getTime()).text
      this.submit_end_time = this.formatDate(endDate.getTime()).text
      this.view_begin_time = this.formatViewDate(startDate.getTime()).text
      this.view_end_time = this.formatViewDate(endDate.getTime()).text
    },
    MathPrice(payType, pkgId, preSubmit, wxPay) {
      const app = getApp()
      uni.showLoading({ title: '请稍等...' })
      legacyRequest(
        '/member/order/preOrder', '1', 'post',
        {
          room_id: this.roomId,
          pay_type: payType,
          coupon_id: this.submit_couponId,
          user_card_id: this.submit_cardId, // 会员卡ID
          pkg_id: pkgId,
          start_time: this.submit_begin_time,
          end_time: this.submit_end_time,
          pre_submit: preSubmit,
          wx_pay: wxPay,
          night_long: this.nightLong,
          time_index: this.select_time_index
        },
        app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
        '',
        (info) => {
          uni.hideLoading()
          if (info.code == 0) {
            if (this.typeIndex != 1) {
              // 后端返回 pay_amount，单位是元
              this.payPrice = info.data.pay_amount || info.data.price || 0
              this.cardDeductAmount = (info.data.card_deduct_amount || 0) + (info.data.card_discount_amount || 0)
              // 加时券：更新结束时间显示
              if (info.data.coupon_extend_minutes > 0 && info.data.end_time) {
                this.submit_end_time = info.data.end_time
                this.view_end_time = this.formatViewDate(new Date(info.data.end_time.replace(/\//g, '-')).getTime()).text
                this.order_hour = (info.data.duration || this.order_hour * 60) / 60
              }
            } else {
              this.payPrice = 0
              this.cardDeductAmount = 0
            }
          } else if (info.code == 1004004021) {
            this.submit_begin_time = new Date()
            if (this.select_time_index < 999) {
              this.MathDate()
            }
          } else {
            uni.showToast({ title: info.msg, icon: 'none', duration: 1500, mask: true })
          }
        },
        () => { uni.hideLoading() }
      )
    },

    MathBaochang() {
      this.showCouponSelect = false
      let startDate = new Date()
      let order_hour = 0
      if (this.submit_begin_time) {
        startDate = new Date(this.submit_begin_time)
      }
      let endDate = null
      const now = new Date()
      const nowHours = now.getHours()
      const selectedDay = new Date(startDate)
      selectedDay.setHours(0, 0, 0, 0)
      const isToday = selectedDay.getTime() === new Date(new Date().setHours(0, 0, 0, 0)).getTime()
      
      // 获取动态时段配置，使用默认值兜底
      const morningStart = parseInt(this.roominfodata.morning_start) || 9
      const morningEnd = parseInt(this.roominfodata.morning_end) || 13
      const afternoonStart = parseInt(this.roominfodata.afternoon_start) || 13
      const afternoonEnd = parseInt(this.roominfodata.afternoon_end) || 18
      const nightStart = parseInt(this.roominfodata.night_start) || 18
      const nightEnd = parseInt(this.roominfodata.night_end) || 23
      const txStart = parseInt(this.roominfodata.tx_start) || 23
      const txEnd = parseInt(this.roominfodata.tx_end) || 8

      if (this.select_time_index == 9991) {
        order_hour = morningEnd - morningStart
        if (isToday && nowHours >= morningStart && nowHours < morningEnd) {
          startDate = new Date(now)
          endDate = new Date(selectedDay)
          endDate.setHours(morningEnd, 0, 0, 0)
        } else {
          startDate = new Date(selectedDay); startDate.setHours(morningStart, 0, 0, 0)
          endDate = new Date(selectedDay); endDate.setHours(morningEnd, 0, 0, 0)
          if (isToday && nowHours >= morningEnd) { uni.showToast({ title: '当天上午场已结束', icon: 'none' }); return }
        }
      } else if (this.select_time_index == 9992) {
        order_hour = afternoonEnd - afternoonStart
        if (isToday && nowHours >= afternoonStart && nowHours < afternoonEnd) {
          startDate = new Date(now)
          endDate = new Date(selectedDay); endDate.setHours(afternoonEnd, 0, 0, 0)
        } else {
          startDate = new Date(selectedDay); startDate.setHours(afternoonStart, 0, 0, 0)
          endDate = new Date(selectedDay); endDate.setHours(afternoonEnd, 0, 0, 0)
          if (isToday && nowHours >= afternoonEnd) { uni.showToast({ title: '当天下午场已结束', icon: 'none' }); return }
        }
      } else if (this.select_time_index == 9993) {
        order_hour = nightEnd - nightStart
        if (isToday && nowHours >= nightStart && nowHours < nightEnd) {
          startDate = new Date(now)
          endDate = new Date(selectedDay); endDate.setHours(nightEnd, 0, 0, 0)
        } else {
          startDate = new Date(selectedDay); startDate.setHours(nightStart, 0, 0, 0)
          endDate = new Date(selectedDay); endDate.setHours(nightEnd, 0, 0, 0)
          if (isToday && nowHours >= nightEnd) { uni.showToast({ title: '当天夜间场已结束', icon: 'none' }); return }
        }
      } else if (this.select_time_index == 9994) {
        // 通宵场跨天计算
        order_hour = txEnd < txStart ? (24 - txStart + txEnd) : (txEnd - txStart)
        const selectedDayStart = new Date(startDate); selectedDayStart.setHours(0, 0, 0, 0)
        const endDay = new Date(selectedDayStart)
        if (startDate.getHours() < txStart && startDate.getHours() > txEnd) {
          startDate = new Date(selectedDayStart); startDate.setHours(txStart, 0, 0, 0)
          endDay.setDate(selectedDayStart.getDate() + 1)
          endDate = new Date(endDay); endDate.setHours(txEnd, 0, 0, 0)
        } else {
          if (startDate.getHours() >= txStart) { endDay.setDate(selectedDayStart.getDate() + 1) }
          endDate = new Date(endDay); endDate.setHours(txEnd, 0, 0, 0)
        }
        if (startDate.getHours() >= txEnd && startDate.getHours() < txStart) {
          uni.showToast({ title: '通宵场即将结束，无法下单', icon: 'none' }); return
        }
      }
      this.submit_couponId = ''
      this.submit_couponName = ''
      this.order_hour = order_hour
      this.submit_begin_time = this.formatDate(startDate.getTime()).text
      this.submit_end_time = this.formatDate(endDate.getTime()).text
      this.view_begin_time = this.formatViewDate(startDate.getTime()).text
      this.view_end_time = this.formatViewDate(endDate.getTime()).text
      this.MathPrice(1, null, false, false)
    },

    // 获取优惠券列表
    getCouponListData() {
      const app = getApp()
      if (app.globalData.isLogin) {
        legacyRequest(
          '/member/user/getCouponPage', '1', 'post',
          { pageNo: 1, pageSize: 100, status: 0, store_id: this.storeId, room_id: this.roomId, start_time: this.submit_begin_time, end_time: this.submit_end_time },
          app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
          '',
          (info) => {
            if (info.code == 0) {
              // 只显示 enable=true 的可用优惠券
              var list = info.data.list || []
              this.couponList = list.filter(function(item) { return item.enable })
            }
          },
          () => {}
        )
        // 同时获取会员卡列表
        this.getCardListData()
      }
    },
    // 获取可用会员卡列表
    getCardListData() {
      const app = getApp()
      if (app.globalData.isLogin) {
        // 先检查门店是否开启会员卡功能
        legacyRequest(
          '/member/card/checkEnabled', '1', 'get',
          { store_id: this.storeId },
          app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
          '',
          (info) => {
            if (info.code == 0 && info.data.enabled) {
              this.cardEnabled = true
              // 获取可用会员卡
              legacyRequest(
                '/member/card/available', '1', 'get',
                { store_id: this.storeId, amount: this.payPrice },
                app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
                '',
                (res) => {
                  if (res.code == 0) {
                    this.cardList = res.data || []
                  }
                },
                () => {}
              )
            } else {
              this.cardEnabled = false
              this.cardList = []
            }
          },
          () => {}
        )
      }
    },
    selectCouponInfo(index, item) {
      this.select_coupon_index = index
      this.submit_couponId = item.id
      this.submit_couponName = item.name
      this.couponShow = false
      this.MathPrice(1, null, false, false)
    },
    clearCoupon() {
      this.submit_couponId = ''
      this.submit_couponName = ''
      this.select_coupon_index = -1
    },
    // 选择会员卡
    selectCardInfo(index, item) {
      this.select_card_index = index
      this.submit_cardId = item.id
      const typeText = { 1: '次卡', 2: '时长卡', 3: '储值卡' }
      this.submit_cardName = item.name + '(' + typeText[item.type] + ')'
      this.cardShow = false
      this.MathPrice(1, null, false, false)
    },
    // 清除会员卡选择
    clearCard() {
      this.submit_cardId = ''
      this.submit_cardName = ''
      this.select_card_index = -1
      this.MathPrice(1, null, false, false)
    },
    // 跳转购买会员卡页面
    goBuyCard() {
      uni.navigateTo({
        url: `/pages/card/index?storeId=${this.storeId}`
      })
    },
    // 获取套餐列表
    getPkgList() {
      const app = getApp()
      return new Promise((resolve, reject) => {
        if (app.globalData.isLogin) {
          legacyRequest(
            '/member/pkg/getPkgPage', '1', 'post',
            { store_id: this.storeId, room_id: this.roomId },
            app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
            '',
            (info) => {
              if (info.code == 0) {
                if (info.data.list && info.data.list.length > 0) {
                  const newMeals = info.data.list.map(el => ({
                    ...el,
                    desc: this.convertEnableWeek(el.enableWeek || el.enable_week || []),
                    time_quantum: this.convertTime(el.enableTime || el.enable_time || [])
                  }))
                  this.pkgList = newMeals
                  this.select_pkg_index = 0
                  this.pkgId = info.data.list[0].pkgId || info.data.list[0].pkg_id
                  this.order_hour = info.data.list[0].hours || info.data.list[0].hour
                  this.payPrice = info.data.list[0].price
                  this.MathDate()
                }
                resolve()
              } else {
                uni.showModal({ content: info.msg, showCancel: false })
                reject()
              }
            },
            () => { reject() }
          )
        }
      })
    },
    selectPkgInfo(index, item) {
      this.select_pkg_index = index
      this.pkgId = item.pkgId || item.pkg_id
      this.order_hour = item.hours || item.hour
      this.payPrice = item.price
      this.MathDate()
    },
    convertEnableWeek(enableWeek) {
      if (!enableWeek || !Array.isArray(enableWeek)) return '周一至周日'
      const weekdays = ['一', '二', '三', '四', '五', '六', '日']
      if (enableWeek.length === 7 || enableWeek.length === 0) return '周一至周日'
      return `周${enableWeek.map(d => weekdays[d - 1]).join('、')}可用`
    },
    formatEnableWeekShort(enableWeek) {
      if (!enableWeek || !Array.isArray(enableWeek) || enableWeek.length === 0 || enableWeek.length === 7) {
        return '全周可用'
      }
      const weekMap = { 0: '日', 1: '一', 2: '二', 3: '三', 4: '四', 5: '五', 6: '六' }
      const sorted = [...enableWeek].sort((a, b) => a - b)
      
      // 检查是否是连续的工作日（周一到周五）
      if (sorted.length === 5 && sorted[0] === 1 && sorted[4] === 5) {
        return '工作日可用'
      }
      // 检查是否是周末
      if (sorted.length === 2 && sorted[0] === 0 && sorted[1] === 6) {
        return '周末可用'
      }
      // 其他情况显示具体星期
      return sorted.map(d => '周' + weekMap[d]).join('、')
    },
    convertTime(numbers) {
      if (!numbers || !Array.isArray(numbers) || numbers.length === 0) return ''
      let result = [], start = numbers[0], end = numbers[0]
      for (let i = 1; i < numbers.length; i++) {
        if (numbers[i] === end + 1) { end = numbers[i] }
        else { result.push(`${start}~${end}时`); start = numbers[i]; end = numbers[i] }
      }
      result.push(`${start}~${end}时`)
      return result.join(' ')
    },

    // 提交订单 - 所有支付方式都先走preOrder
    SubmitOrderInfoData(payType) {
      // 支付方式配置校验（前端提前拦截，给出具体提示）
      if (payType == 1) {
        if (!this.payMethodWechat.enabled) {
          uni.showModal({ title: '提示', content: '微信支付未启用，请联系店家', showCancel: false })
          return
        }
      }
      if (payType == 2) {
        if (!this.payMethodBalance.enabled) {
          uni.showModal({ title: '提示', content: '余额支付未启用，请联系店家', showCancel: false })
          return
        }
      }
      if (payType == 3) {
        if (!this.payMethodGroup.enabled) {
          uni.showModal({ title: '提示', content: '团购支付未启用，请联系店家', showCancel: false })
          return
        }
        if (!this.payMethodGroup.platform_ok) {
          uni.showModal({ title: '提示', content: '该门店未授权团购平台，请联系店家', showCancel: false })
          return
        }
      }
      // 团购支付时检查券码是否已验证
      if (payType == 3 && !this.groupVerified) {
        uni.showToast({ title: '请先验证团购券码', icon: 'none' })
        return
      }
      // 余额支付时弹确认框，让用户有机会检查优惠券和价格
      if (payType == 2 && this.typeIndex !== 1) {
        const couponTip = this.submit_couponName ? `\n优惠券：${this.submit_couponName}` : (this.couponList.length > 0 ? '\n您有可用优惠券未使用' : '')
        const cardTip = this.submit_cardName ? `\n会员卡：${this.submit_cardName}` : (this.cardList.length > 0 && this.cardEnabled ? '\n您有可用会员卡未使用' : '')
        uni.showModal({
          title: '确认支付',
          content: `支付金额：￥${this.payPrice}元${couponTip}${cardTip}\n确认使用余额支付？`,
          confirmText: '确认支付',
          cancelText: '返回选券',
          success: (res) => {
            if (res.confirm) {
              this.doSubmitOrder(payType)
            }
          }
        })
        return
      }
      this.doSubmitOrder(payType)
    },
    doSubmitOrder(payType) {
      const app = getApp()
      let preSubmit = this.typeIndex == 3 // 押金开台
      // 只有微信支付或押金开台才需要调微信支付接口
      let wxpay = (payType == 1) || (preSubmit && this.roominfodata.deposit)
      legacyRequest(
        '/member/order/preOrder', '1', 'post',
        {
          room_id: this.roomId,
          pay_type: payType,
          coupon_id: this.submit_couponId,
          user_card_id: this.submit_cardId, // 会员卡ID
          pkg_id: this.pkgId,
          start_time: this.submit_begin_time,
          end_time: this.submit_end_time,
          pre_submit: preSubmit,
          wx_pay: wxpay,
          night_long: this.nightLong,
          time_index: this.select_time_index,
          group_pay_no: this.groupPayNo
        },
        app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
        '提交中...',
        (info) => {
          if (info.code == 0) {
            this.orderNo = info.data.order_no || info.data.orderNo
            this.preOrderData = info.data // 保存preOrder返回的价格数据
            // 如果preOrder返回了更新的end_time（如加时券），同步更新
            if (info.data.end_time && !this.orderNo) {
              this.submit_end_time = info.data.end_time
            }
            // 并行锁定订单
            this.lockWxOrder()
            // 判断逻辑跟原版一致：有押金时强制走微信支付
            const deposit = info.data.deposit || 0
            if (deposit > 0 || payType == 1) {
              // 有押金或微信支付
              const payPrice = info.data.pay_amount || info.data.payPrice || 0
              if (payPrice > 0) {
                  // 微信支付
                  this.payMent(info)
              } else {
                // 金额为0，直接提交订单
                this.submitorder(payType, preSubmit)
              }
            } else {
              // 无押金的余额支付或团购，直接提交订单
              this.submitorder(payType, preSubmit)
            }
          } else {
            uni.showModal({ title: '温馨提示', content: info.msg, showCancel: false, confirmText: '确定' })
          }
        },
        () => {}
      )
    },
    lockWxOrder() {
      const app = getApp()
      if (app.globalData.isLogin) {
        legacyRequest(
          '/member/order/lockWxOrder', '1', 'post',
          { room_id: this.roomId, start_time: this.submit_begin_time, end_time: this.submit_end_time },
          app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
          '',
          (info) => { if (info.code == 0) { console.log('锁定订单') } },
          () => {}
        )
      }
    },
    payMent(pay) {
      uni.requestPayment({
        timeStamp: pay.data.timeStamp,
        nonceStr: pay.data.nonceStr,
        package: pay.data.pkg,
        signType: pay.data.signType,
        paySign: pay.data.paySign,
        success: () => {
          this.goPage = true
          this.getOrderInfoData()
        },
        fail: () => {
          uni.showToast({ title: '支付失败!', icon: 'error' })
        }
      })
    },
    submitorder(payType, preSubmit) {
      const app = getApp()
      if (app.globalData.isLogin) {
        legacyRequest(
          '/member/order/save', '1', 'post',
          {
            room_id: this.roomId,
            coupon_id: this.submit_couponId,
            user_card_id: this.submit_cardId, // 会员卡ID
            pkg_id: this.pkgId,
            start_time: this.submit_begin_time,
            end_time: this.submit_end_time,
            pay_type: payType,
            group_pay_no: this.groupPayNo,
            group_coupon_id: this.groupCouponId || 0,
            group_title: this.groupPayTitle || '',
            group_hours: this.order_hour || 0,
            group_platform: this.preOrderData ? (this.preOrderData.platform || '') : '',
            night_long: this.nightLong,
            order_no: this.orderNo,
            pre_submit: preSubmit,
            // 传递preOrder计算的金额，供create()原子化建单+支付使用
            pay_amount: this.preOrderData ? this.preOrderData.pay_amount : this.payPrice,
            total_amount: this.preOrderData ? this.preOrderData.total_amount : 0,
            discount_amount: this.preOrderData ? this.preOrderData.discount_amount : 0,
            card_deduct_amount: this.preOrderData ? this.preOrderData.card_deduct_amount : 0,
          },
          app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
          '提交中...',
          (info) => {
            if (info.code == 0) {
              // 从返回数据获取订单号
              const orderNo = info.data.order_no || info.data.orderNo || this.orderNo
              this.orderNo = orderNo
              uni.showToast({ title: info.msg || '预定成功！', icon: info.msg && info.msg !== '预定成功' && info.msg !== '支付成功' ? 'none' : 'success' })
              setTimeout(() => {
                // 使用 redirectTo 替换当前页面，避免返回时又回到提交页
                uni.redirectTo({ url: `/pages/order/detail?toPage=true&order_no=${orderNo}` })
              }, 1000)
            } else {
              uni.showModal({ title: '温馨提示', content: info.msg, showCancel: false, confirmText: '确定' })
            }
          },
          () => {}
        )
      }
    },
    getOrderInfoData() {
      const app = getApp()
      legacyRequest(
        '/member/order/getOrderInfoByNo', '1', 'get',
        { orderNo: this.orderNo },
        app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
        '获取中...',
        (info) => {
          if (info.code === 0) {
            // 使用 redirectTo 替换当前页面，避免返回时又回到提交页
            uni.redirectTo({ url: `/pages/order/detail?toPage=true&order_no=${this.orderNo}` })
          }
        },
        () => {}
      )
    },

    // 团购相关
    scanCode() {
      uni.scanCode({
        success: (res) => {
          this.groupPayNo = res.result
          this.tuangouShow = true
          this.typeIndex = 1
          this.checkGroup()
        },
        fail: () => {
          uni.showToast({ title: '扫码失败', icon: 'none' })
        }
      })
    },
    checkGroup() {
      const app = getApp()
      if (!this.groupPayNo || this.groupPayNo.length < 6 || !this.storeId) {
        uni.showToast({ title: '请输入正确的团购券码（至少6位）', icon: 'none' })
        return
      }
      // 前端提前拦截
      if (!this.payMethodGroup.enabled) {
        uni.showModal({ title: '提示', content: '团购支付未启用，请联系店家', showCancel: false })
        return
      }
      if (!this.payMethodGroup.platform_ok) {
        uni.showModal({ title: '提示', content: '该门店未授权团购平台，请联系店家', showCancel: false })
        return
      }
      if (app.globalData.isLogin) {
        legacyRequest(
          '/member/order/preGroupNo', '1', 'post',
          { store_id: this.storeId, code: this.groupPayNo },
          app.globalData.userDatatoken ? app.globalData.userDatatoken.accessToken : '',
          '',
          (info) => {
            if (info.code == 0) {
              let order_hour = info.data.hours
              let nightLong = false
              if (order_hour === 99) {
                nightLong = true
                order_hour = this.roominfodata.txHour || this.roominfodata.tx_hour || 9
              }
              this.groupPayTitle = info.data.title
              this.order_hour = order_hour
              this.payPrice = 0
              this.nightLong = nightLong
              this.groupCouponId = info.data.group_coupon_id || 0
              this.groupVerified = true // 标记验证通过
              this.MathDate()
            } else {
              this.groupPayTitle = ''
              this.order_hour = 0
              this.payPrice = 0
              this.groupCouponId = 0
              this.groupVerified = false // 标记验证失败
              uni.showModal({ title: '验券失败', content: info.msg || '团购券码无效', showCancel: false })
            }
          },
          () => {}
        )
      }
    },
    // 时间选择
    openTimeSelect() {
      // 初始化 picker 到当前开始时间
      const now = this.submit_begin_time ? new Date(this.submit_begin_time) : new Date()
      this.pickerValue = [now.getHours(), now.getMinutes()]
      this.pickerConflict = ''
      this.timeSelectShow = true
      // 初始检查冲突
      this.checkTimeConflict()
    },
    onPickerChange(e) {
      this.pickerValue = e.detail.value
      // 实时检查时间冲突
      this.checkTimeConflict()
    },
    // 检查时间冲突（新增方法）
    checkTimeConflict() {
      const h = this.pickerValue[0]
      const m = this.pickerValue[1]
      
      // 根据选择的天数计算日期
      const baseDate = new Date()
      baseDate.setDate(baseDate.getDate() + this.dayIndex)
      baseDate.setHours(h, m, 0, 0)
      
      // 检查是否是过去的时间
      if (baseDate.getTime() < Date.now() - 60000) {
        this.pickerConflict = '不能选择过去的时间'
        return false
      }
      
      // 计算订单结束时间
      const orderEndTime = new Date(baseDate.getTime() + this.order_hour * 60 * 60 * 1000)
      
      // 检查时间冲突（改进：检查整个订单时长是否与已预订时间重叠）
      const orderTimeList = this.roominfodata.orderTimeList || this.roominfodata.order_time_list || []
      for (let i = 0; i < orderTimeList.length; i++) {
        const ot = orderTimeList[i]
        const otStart = new Date(ot.startTime || ot.start_time)
        const otEnd = new Date(ot.endTime || ot.end_time)
        
        // 检查时间段是否重叠：订单开始时间 < 已预订结束时间 && 订单结束时间 > 已预订开始时间
        if (baseDate < otEnd && orderEndTime > otStart) {
          const conflictStart = this.formatViewDate(otStart.getTime()).text
          const conflictEnd = this.formatViewDate(otEnd.getTime()).text
          this.pickerConflict = `与已预订时间冲突 (${conflictStart}-${conflictEnd})`
          return false
        }
      }
      
      this.pickerConflict = ''
      return true
    },
    confirmTimePicker() {
      const h = this.pickerValue[0]
      const m = this.pickerValue[1]
      
      // 根据选择的天数计算日期
      const baseDate = new Date()
      baseDate.setDate(baseDate.getDate() + this.dayIndex)
      baseDate.setHours(h, m, 0, 0)
      
      // 不能选过去的时间
      if (baseDate.getTime() < Date.now() - 60000) {
        uni.showToast({ title: '不能选择过去的时间', icon: 'none' })
        return
      }
      
      // 计算订单结束时间
      const orderEndTime = new Date(baseDate.getTime() + this.order_hour * 60 * 60 * 1000)
      
      // 检查时间冲突（改进：检查整个订单时长是否与已预订时间重叠）
      const orderTimeList = this.roominfodata.orderTimeList || this.roominfodata.order_time_list || []
      for (let i = 0; i < orderTimeList.length; i++) {
        const ot = orderTimeList[i]
        const otStart = new Date(ot.startTime || ot.start_time)
        const otEnd = new Date(ot.endTime || ot.end_time)
        
        // 检查时间段是否重叠
        if (baseDate < otEnd && orderEndTime > otStart) {
          const conflictStart = this.formatViewDate(otStart.getTime()).text
          const conflictEnd = this.formatViewDate(otEnd.getTime()).text
          uni.showToast({ 
            title: `该时间段与已预订时间冲突 (${conflictStart}-${conflictEnd})`, 
            icon: 'none',
            duration: 2500
          })
          return
        }
      }
      
      this.timeSelectShow = false
      this.submit_begin_time = this.formatDate(baseDate.getTime()).text
      if (this.select_time_index > 999) {
        this.MathBaochang()
      } else {
        this.MathDate()
        if (this.typeIndex == 0) {
          this.MathPrice(1, null, false, false)
        }
      }
    },
    conTimeSelect(index, item) {
      if (item && item.available) {
        this.timeSelectShow = false
        this.submit_begin_time = item.date
        if (this.select_time_index > 999) {
          this.MathBaochang()
        } else {
          this.MathDate()
          if (this.typeIndex == 0) {
            this.MathPrice(1, null, false, false)
          }
        }
      } else {
        uni.showToast({ title: '该时间不可用', icon: 'none' })
      }
    },
    handleDayChange(index) {
      this.dayIndex = index
      this.pickerConflict = ''
      const tsl = this.roominfodata.timeSelectLists || this.roominfodata.time_select_lists || []
      if (tsl[index]) {
        this.timeSelectList = tsl[index].selectList || tsl[index].select_list || []
      }
      // 切换日期后重新检查冲突
      this.checkTimeConflict()
    }
  }
}
</script>

<style lang="scss">
page {
  background: linear-gradient(to bottom, var(--main-color, #5AAB6E) 45%, #F7F7F7 84%, #F7F7F7 100%) no-repeat;
}
</style>

<style lang="scss" scoped>
.container {
  padding: 6rpx 26rpx 100rpx 26rpx;
}
.title-bar {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 100;
  padding: 10rpx 26rpx;
  box-sizing: border-box;
}
.title-bar .storeName {
  color: #fff;
  font-weight: 600;
  font-size: 36rpx;
  margin-left: 100rpx;
  line-height: 80rpx;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.top {
  background-color: #fff;
  padding: 0rpx 10rpx 20rpx 10rpx;
  border-radius: 20rpx;
}
.roomInfo {
  display: flex;
  justify-content: space-around;
  margin-top: 10rpx;
  padding-top: 20rpx;
}
.roomInfo .left {
  margin-left: 10rpx;
  width: 30%;
}
.roomInfo .left .roomImg .img {
  width: 160rpx;
  height: 160rpx;
  border-radius: 10rpx;
}
.roomInfo .right {
  margin-left: 10rpx;
  width: 70%;
}
.roomInfo .right .roomName {
  font-size: 32rpx;
  font-weight: bold;
  display: flex;
}
.roomInfo .right .roomName .roomtype {
  font-size: 24rpx;
  line-height: 50rpx;
}
.roomInfo .right .tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
  margin: 6rpx 0;
}
.roomInfo .right .tags .tag {
  font-size: 20rpx;
  background: #f0f9f2;
  color: var(--main-color, #5AAB6E);
  padding: 2rpx 12rpx;
  border-radius: 6rpx;
}
.roomInfo .right .price {
  font-size: 32rpx;
  font-weight: bold;
  color: var(--main-color, #5AAB6E);
}
.roomInfo .right .price .priceText {
  font-size: 24rpx;
  font-weight: normal;
  color: var(--main-color, #5AAB6E);
}
.roomInfo .btns {
  width: 100rpx;
}
.roomInfo .btns .btn {
  background-color: var(--main-color, #5AAB6E);
  text-align: center;
  font-size: 28rpx;
  line-height: 28rpx;
  padding: 10rpx;
  border-radius: 10rpx;
  margin-top: 20rpx;
  color: #ffffff;
}

.line3 {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #ffb731;
  font-weight: 500;
  font-size: 26rpx;
  padding: 0 10rpx;
}
.line3 .more {
  color: var(--main-color, #5AAB6E);
}
.timeSlot {
  margin-top: 10rpx;
  display: flex;
  justify-content: space-between;
}
.timeSlot .time {
  font-size: 20rpx;
  text-align: center;
  color: #aaa;
  border-top: 10rpx solid #e8e8e8;
  width: 24rpx;
  line-height: 30rpx;
}
.timeSlot .disabled {
  border-color: var(--main-color, #5AAB6E);
}
.info {
  background-color: #fff;
  border-radius: 20rpx;
  margin-top: 20rpx;
  padding-top: 10rpx;
  padding-bottom: 20rpx;
}
.info .btn {
  display: flex;
  justify-content: space-around;
  align-items: center;
  padding: 30rpx 20rpx;
  margin: 20rpx 30rpx;
  border-radius: 20rpx;
  background: linear-gradient(to right, #86f5a24f, #e3ffeaa1);
}
.info .btn .left {
  width: 60%;
  font-size: 40rpx;
  font-weight: bold;
}
.info .btn .left .text1 {
  font-size: 36rpx;
}
.info .btn .left .text2 {
  font-size: 26rpx;
  color: rgb(92, 92, 92);
  font-weight: normal;
}
.platform-icons {
  display: flex;
  flex-direction: row;
  margin-top: 8rpx;
  gap: 12rpx;
}
.platform-tag {
  font-size: 20rpx;
  font-weight: bold;
  color: #fff;
  padding: 2rpx 12rpx;
  border-radius: 6rpx;
}
.platform-tag.mt {
  background-color: #06C167;
}
.platform-tag.dp {
  background-color: #FF6633;
}
.platform-tag.dy {
  background-color: #111;
}
.info .btn .right {
  display: flex;
  justify-content: center;
  background-color: var(--main-color, #5AAB6E);
  color: #fff;
  text-align: center;
  align-items: center;
  width: 140rpx;
  height: 60rpx;
  border-radius: 50rpx;
  font-size: 30rpx;
  font-weight: bold;
  border: 2px solid #000;
}
.content {
  margin-top: 20rpx;
  padding: 30rpx;
  background-color: #fff;
  border-radius: 20rpx;
}
.content .notice-title {
  color: var(--main-color, #5AAB6E);
  font-size: 32rpx;
  font-weight: 800;
  margin-bottom: 5rpx;
}
.content .line {
  font-size: 28rpx;
  margin: 5rpx 0;
}

/* 弹窗公共样式 */
.popup-content {
  padding: 20rpx 30rpx;
}
.popup-title {
  text-align: center;
  font-size: 36rpx;
  font-weight: bold;
  color: #000;
  margin: 20rpx 0;
}
.time-items {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  padding: 0 10rpx;
  gap: 10rpx;
}
.time-items .time-item {
  width: 10%;
  min-width: 80rpx;
  background-color: #fff;
  border-radius: 10rpx;
  padding: 5rpx 10rpx;
  color: rgb(49, 49, 49);
  border: 1rpx solid #C3C1C1;
  text-align: center;
  font-size: 34rpx;
  font-weight: 600;
}
.time-items .time-item.item2 {
  margin-top: 10rpx;
  width: 22%;
}
.time-items .time-item.item2 .bc-price {
  font-size: 22rpx;
  color: red;
}
.time-items .time-item.active {
  background: var(--main-color, #5AAB6E);
  color: #fff;
}
.timer {
  display: flex;
  flex-direction: row;
  margin-top: 20rpx;
  align-items: center;
  padding: 0 10rpx;
}
.timer .timer-title {
  font-weight: bold;
  width: 120rpx;
  font-size: 34rpx;
  color: var(--main-color, #5AAB6E);
}
.timer .time {
  width: 200rpx;
  height: 75rpx;
  background: #FFFFFF;
  border-radius: 10rpx;
  border: 2rpx solid #E0E0E0;
  font-size: 40rpx;
  font-weight: bold;
  color: #BCBCBC;
  display: flex;
  justify-content: center;
  align-items: center;
}
.timer .time.active {
  border: 2rpx solid var(--main-color, #5AAB6E);
  color: var(--main-color, #5AAB6E);
}
.timer .divide {
  margin: 0 26rpx;
  color: #666666;
}
/* 优惠券区域 */
.coupon-section {
  background-color: #fff;
  padding: 15rpx 20rpx;
  margin: 15rpx 10rpx;
  border-radius: 15rpx;
  border: 1rpx solid #eee;
}
.coupon-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.coupon-top .c-left {
  display: flex;
  align-items: center;
  font-size: 30rpx;
  gap: 10rpx;
}
.coupon-top .c-right {
  display: flex;
  align-items: center;
  color: red;
  font-size: 28rpx;
}
.coupon-top .c-right .couponCount {
  margin-right: 10rpx;
}
.couponSelect {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10rpx;
}
.couponSelect .couponName {
  color: #ea4e4e;
  font-size: 26rpx;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.couponSelect .clearCoupon {
  font-size: 22rpx;
  background-color: red;
  color: #fff;
  padding: 5rpx 15rpx;
  border-radius: 5rpx;
  margin-left: 10rpx;
}

/* 订单信息 */
.order-info {
  padding: 10rpx 20rpx;
}
.order-info .info-line {
  display: flex;
  justify-content: space-between;
  margin: 10rpx 0;
  font-size: 26rpx;
  color: rgb(53, 53, 53);
}
.order-info .info-line .bold {
  font-weight: bold;
}
.color-attention {
  color: red;
}
/* 底部支付栏 */
.order-bar {
  width: 100%;
  box-sizing: border-box;
  padding: 15rpx 20rpx 40rpx 20rpx;
  align-items: center;
  background-color: #fff;
  display: flex;
  justify-content: space-between;
  margin-top: 20rpx;
}
.order-bar .bar-left .total {
  font-size: 32rpx;
  font-weight: 600;
}
.order-bar .bar-right {
  display: flex;
}
.pay-btn {
  margin: 0;
  height: 80rpx;
  line-height: 80rpx;
  font-size: 26rpx;
  display: flex;
  align-items: center;
  color: #fff;
  padding: 0 20rpx;
  border: none;
}
.pay-btn::after {
  border: none;
}
.pay-btn.wx {
  background-color: rgb(46, 45, 45);
  border-radius: 15rpx 0 0 15rpx;
}
.pay-btn.yue {
  background-color: rgb(250, 114, 72);
  border-radius: 0 15rpx 15rpx 0;
  color: rgb(228, 226, 226);
}
.pay-btn.tg {
  background-color: rgb(250, 114, 72);
  border-radius: 15rpx;
}
.pay-btn .desc {
  font-size: 24rpx;
  line-height: 24rpx;
  margin-left: 10rpx;
  margin-right: 16rpx;
}
.pay-btn .desc view {
  display: flex;
  align-items: center;
  margin-bottom: 6rpx;
}

/* 优惠券列表弹窗 */
.couponList {
  height: 40vh;
}
.couponItem {
  padding: 20rpx;
  margin: 15rpx 10rpx;
  border-radius: 10rpx;
  background: linear-gradient(to right, #90ffac, #c7f1d1);
}
.coupon-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.coupon-row .c1 {
  text-align: center;
  min-width: 120rpx;
}
.coupon-row .c1 .cprice {
  color: red;
  font-size: 38rpx;
  font-weight: 600;
}
.coupon-row .c1 .ctype {
  font-size: 22rpx;
  background-color: var(--main-color, #5AAB6E);
  color: #fff;
  padding: 2rpx 10rpx;
  border-radius: 20rpx;
}
.coupon-row .c2 {
  flex: 1;
  margin-left: 20rpx;
  padding-left: 20rpx;
  border-left: 1rpx solid #C3C1C1;
}
.coupon-row .c2 .item-name {
  font-size: 34rpx;
  font-weight: bold;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
.coupon-row .c2 .item-date {
  font-size: 22rpx;
  color: #666;
}
.coupon-row .c3 {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0 15rpx;
}
.radio {
  width: 40rpx;
  height: 40rpx;
  border: 2px solid #ccc;
  border-radius: 50%;
  position: relative;
  transition: background-color 0.3s ease;
}
.radio.selected {
  border-color: #1aad19;
}
.radio.selected::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 20rpx;
  height: 20rpx;
  background-color: #1aad19;
  border-radius: 50%;
  transform: translate(-50%, -50%);
}

/* 团购弹窗 */
.tuangouBg {
  width: 100%;
  text-align: center;
  border-radius: 20rpx;
}
.tuangouBg .img {
  width: 95%;
  border-radius: 30rpx;
}
.tgScan {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #fff;
  margin: 20rpx 10rpx;
  padding: 15rpx 20rpx;
  border-radius: 20rpx;
  border: 1rpx solid #eee;
}
.tgScan .input {
  font-size: 30rpx;
  text-align: left;
  color: #f19b1a;
  flex: 1;
}
.tgTitle {
  text-align: left;
  margin-left: 20rpx;
  font-size: 30rpx;
  color: #d88d1b;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
/* 套餐列表弹窗 */
.pkgList {
  height: 40vh;
}
.pkgItem {
  padding: 20rpx;
  margin: 15rpx 10rpx;
  border-radius: 10rpx;
  background: linear-gradient(to right, #90ffac, #c7f1d1);
}
.pkg-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.pkg-row .c1 {
  text-align: center;
  min-width: 100rpx;
}
.pkg-row .c1 .pkg-price {
  color: red;
  font-size: 40rpx;
  font-weight: 600;
}
.pkg-row .c1 .pkg-hour {
  color: #fff;
  font-size: 22rpx;
  background-color: rgb(250, 142, 54);
  padding: 5rpx 10rpx;
  border-radius: 15rpx;
}
.pkg-row .c2 {
  flex: 1;
  margin-left: 20rpx;
  padding-left: 20rpx;
  border-left: 1rpx solid #C3C1C1;
}
.pkg-row .c2 .item-name {
  font-size: 34rpx;
  font-weight: bold;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 8rpx;
}
.pkg-row .c2 .item-room {
  font-size: 22rpx;
  color: #666;
}
.pkg-row .c2 .item-rules {
  display: flex;
  flex-direction: column;
  gap: 6rpx;
  margin-top: 8rpx;
}
.pkg-row .c2 .item-rules .rule-item {
  display: flex;
  align-items: center;
  gap: 6rpx;
  font-size: 22rpx;
  color: #666;
  line-height: 1.4;
}
.pkg-row .c1 .pkg-original {
  font-size: 20rpx;
  color: #999;
  text-decoration: line-through;
  margin-top: 4rpx;
}
.pkg-row .c3 {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0 15rpx;
}

/* 押金弹窗 */
.notice-box {
  background-color: #fff;
  text-align: center;
  font-size: 24rpx;
  border: 1rpx solid var(--main-color, #5AAB6E);
  color: var(--main-color, #5AAB6E);
  margin: 10rpx 20rpx;
  padding: 15rpx;
  border-radius: 20rpx;
}
.notice-box .bold {
  font-weight: bold;
  margin: 5rpx 0;
}
/* 预定时间弹窗 */
.reserve-box {
  width: 500rpx;
  background: linear-gradient(180deg, #FFECC7 0%, #FFFFFF 16%, #FFFFFF 100%);
  border-radius: 17rpx;
  padding: 34rpx 40rpx;
}
.reserve-box .popup-title {
  margin-bottom: 40rpx;
}
.time-line-item {
  display: flex;
  align-items: center;
  margin-bottom: 30rpx;
  gap: 20rpx;
}
.time-line-item .dot {
  width: 12rpx;
  height: 12rpx;
  background: #FFA313;
  border-radius: 50%;
}
.time-line-item .time-tag {
  color: #fff;
  background-color: #FFA313;
  flex: 1;
  height: 46rpx;
  border-radius: 17rpx;
  line-height: 46rpx;
  display: flex;
  justify-content: space-between;
  font-size: 24rpx;
  padding: 9rpx 22rpx;
}
.reserve-btn {
  width: 208rpx;
  height: 59rpx;
  line-height: 59rpx;
  background: var(--main-color, #5AAB6E);
  border-radius: 52rpx;
  font-size: 26rpx;
  color: #FFFFFF;
  margin-top: 22rpx;
  text-align: center;
  border: none;
}
.reserve-btn::after {
  border: none;
}
/* 时间选择弹窗 */
.time-select-popup {
  height: 70vh;
}
.ts-top {
  background: linear-gradient(180deg, #a2f5b8 0%, #ecf7ef 100%);
  padding: 20rpx;
}
.ts-top .ts-title {
  color: var(--main-color, #5AAB6E);
  font-size: 36rpx;
  font-weight: bold;
  text-align: center;
  margin-bottom: 15rpx;
}
.time-slot {
  display: flex;
  justify-content: space-around;
  font-size: 28rpx;
  font-weight: bold;
}
.time-slot view {
  width: 120rpx;
  height: 70rpx;
  color: #000;
  display: flex;
  border-radius: 15rpx;
  justify-content: center;
  align-items: center;
}
.time-slot view.active {
  background: var(--main-color, #5AAB6E);
  color: #fff;
}
.picker-area {
  padding: 20rpx 40rpx;
}
.time-picker {
  height: 400rpx;
}
.picker-item {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36rpx;
  font-weight: bold;
  color: #333;
  height: 50px;
  line-height: 50px;
}
.ts-conflict {
  text-align: center;
  padding: 10rpx 40rpx;
  font-size: 26rpx;
}
.ts-confirm-btn {
  margin: 20rpx 40rpx 40rpx;
  background: var(--main-color, #5AAB6E);
  color: #fff;
  border-radius: 40rpx;
  height: 80rpx;
  line-height: 80rpx;
  font-size: 30rpx;
  border: none;
}
/* 会员卡折扣标签 */
.item-discount {
  font-size: 20rpx;
  color: #ff6b6b;
  margin-top: 6rpx;
}
</style>
