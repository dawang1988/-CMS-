<template>
  <view class="container" :style="themeStyle">
    <view class="top-container">
      <!-- 时间段 -->
      <view class="timeBox">
        <view class="time">
          <view class="hour">
            <image src="/static/icon/time-start.png" />
            {{ splitTime(OrderInfodata.start_time)[0] }}:{{ splitTime(OrderInfodata.start_time)[1] }}
          </view>
          <view class="date">{{ splitKongge(OrderInfodata.start_time)[0] }}</view>
        </view>
        <view class="total border-primary">共{{ OrderInfodata.duration ? (OrderInfodata.duration / 60).toFixed(1) : 0 }}小时</view>
        <view class="time" v-if="OrderInfodata.end_time">
          <view class="hour">
            {{ splitTime(OrderInfodata.end_time)[0] }}:{{ splitTime(OrderInfodata.end_time)[1] }}
            <image src="/static/icon/time-end.png" />
          </view>
          <view class="date">{{ splitKongge(OrderInfodata.end_time)[0] }}</view>
        </view>
        <view class="no-end" style="font-weight: 300;" v-if="!OrderInfodata.end_time">至</view>
        <view class="no-end" v-if="!OrderInfodata.end_time">主动离店</view>
      </view>
      <!-- 订单详情 -->
      <view class="orderInfo">
        <view class="top">
          <view class="tag">{{ ['未知','小包', '中包', '大包', '豪包', '商务包','斯洛克','中式黑八','美式球桌'][OrderInfodata.room_type] }}</view>
          <text>{{ OrderInfodata.room_name }}</text>
        </view>
        <view class="name">{{ OrderInfodata.store_name }}</view>
        <view class="address">
          <view class="left">{{ OrderInfodata.address }}</view>
          <view class="right">
            <view class="item" @tap="onClickShow" data-index="0">
              <image src="/static/icon/navigation.png" />
              <text>导航</text>
            </view>
            <view class="line"></view>
            <view class="item" @tap="onClickShow" data-index="1">
              <image src="/static/icon/phone.png" />
              <text>电话</text>
            </view>
          </view>
        </view>
        <view class="info-line" @tap="copyOrderNo" :data-order="OrderInfodata.order_no">
          <text class="bold">订单编号：</text>
          <text>{{ OrderInfodata.order_no }}</text>
        </view>
        <view class="info-line display-space">
          <view>
            <text class="bold">支付方式：</text>
            <text>{{ OrderInfodata.pay_type===0?'管理员':OrderInfodata.pay_type===1?'微信':OrderInfodata.pay_type===2?'余额':OrderInfodata.pay_type===3?'团购':OrderInfodata.pay_type===4?'套餐':'预订' }}</text>
          </view>
          <text style="text-decoration-line: underline;font-size: 26rpx;" class="right" v-if="OrderInfodata.status === 0" @tap="cancelOrder" :data-info="OrderInfodata.status">申请退款</text>
        </view>
        <view class="info-line">
          <text class="bold">订单状态：</text>
          <text>{{ ['待消费', '消费中', '已完成', '已退款'][OrderInfodata.status] }}</text>
        </view>
        <view class="info-line">
          <text class="bold">订单总价：</text>
          <text>¥ {{ OrderInfodata.pay_amount }}</text>
        </view>
        <view class="info-line" v-if="OrderInfodata.discount_amount > 0">
          <text class="bold">优惠金额：</text>
          <text style="color:#ff6b00;">-¥ {{ OrderInfodata.discount_amount }}</text>
        </view>
        <view class="info-line" v-if="OrderInfodata.coupon_name">
          <text class="bold">优惠券：</text>
          <text style="color:#ff6b00;">{{ OrderInfodata.coupon_name }}（{{ couponTypeText }}{{ OrderInfodata.coupon_type == 2 ? '¥' + OrderInfodata.coupon_amount : OrderInfodata.coupon_amount + '小时' }}）</text>
        </view>
        <view class="info-line" v-if="OrderInfodata.deposit">
          <text class="bold">订单押金：</text>
          <text>¥ {{ OrderInfodata.deposit }}</text>
        </view>
        <view class="info-line" v-if="OrderInfodata.refund_price">
          <text class="bold">订单退款：</text>
          <text>¥ {{ OrderInfodata.refund_price }}</text>
        </view>
        <view class="info-line">
          <text class="bold">下单时间：</text>
          <text>{{ OrderInfodata.create_time }}</text>
        </view>
      </view>
      <block v-if="OrderInfodata.status==0||OrderInfodata.status==1">
        <view class="status-tag" @tap.stop="showOrderQr" :data-no="OrderInfodata.order_no">
          <image src="/static/icon/scan.png" style="width:80rpx;height:80rpx;" />
        </view>
      </block>
    </view>
    <view class="order-container">
      <view class="wifi-container">
        <view class="left">
          <image src="/static/icon/wifi.png" />
          <view class="desc">
            <text class="wifi">名称: {{ OrderInfodata.wifi_name }}</text>
            <text class="wifi">密码: {{ OrderInfodata.wifi_password }}</text>
          </view>
        </view>
        <view class="wifi-btn" @tap="showWifi">一键连接</view>
      </view>
      <view class="doors">
        <view class="door m-b-20" @tap="damenbindchange" v-if="OrderInfodata.status === 0 || OrderInfodata.status === 1">
          <view class="action">
            <image src="/static/icon/main-door.png" style="width:36rpx;height:36rpx;" />
            <view class="text">门店大门</view>
          </view>
        </view>
        <view class="door m-b-20 start" @tap="roomOpen" v-if="OrderInfodata.status === 0 || OrderInfodata.status === 1">
          <view class="action">
            <image src="/static/icon/open.png" style="width:36rpx;height:36rpx;" />
            <view class="text" v-if="OrderInfodata.room_class == 1">通电开台</view>
            <view class="text" v-else>通电开门</view>
          </view>
        </view>
        <view class="door m-b-20" @tap="showModal" v-if="OrderInfodata.status === 0 || OrderInfodata.status === 1">
          <view class="action">
            <image src="/static/icon/setting.png" style="width:36rpx;height:36rpx;" />
            <view class="text">空调控制</view>
          </view>
        </view>
        <view class="door m-b-20" @tap="goChangeDoor" v-if="OrderInfodata.status === 0 || (isManager && OrderInfodata.status === 1)">
          <view class="action">
            <image src="/static/icon/change-room.png" style="width:36rpx;height:36rpx;" />
            <view class="text" v-if="OrderInfodata.room_class == 1">更换球桌</view>
            <view class="text" v-else>更换房间</view>
          </view>
        </view>
        <button hover-class="button-click" class="door share" open-type="share" v-if="OrderInfodata.status === 0 || OrderInfodata.status === 1">
          <view class="action">
            <image src="/static/icon/send.png" style="width:36rpx;height:36rpx;" />
            <view class="text">分享订单</view>
          </view>
        </button>
        <view class="door m-b-20" v-if="OrderInfodata.status === 0 || OrderInfodata.status === 1 || OrderInfodata.status === 2 || OrderInfodata.status === 3">
          <view class="action" @tap="onClickShow" data-index="1">
            <image src="/static/icon/service.png" style="width:36rpx;height:36rpx;" />
            <view class="text">联系客服</view>
          </view>
        </view>
        <view class="door" @tap="renewClick" v-if="OrderInfodata.status === 1">
          <view class="action">
            <image src="/static/icon/time.png" style="width:36rpx;height:36rpx;" />
            <view class="text">订单续费</view>
          </view>
        </view>

        <view class="bar_btns" v-if="OrderInfodata.status === 1">
          <view class="btnSmall left" @tap="stopOrder" :data-id="OrderInfodata.order_id">
            <image src="/static/icon/leave.png" />
            提前离店
          </view>
        </view>
      </view>
    </view>

    <!-- 评价入口 -->
    <view class="review-entry" v-if="OrderInfodata.status === 2 && !OrderInfodata.is_reviewed">
      <view class="review-btn" @tap="goReview">
        <image src="/static/icon/service.png" style="width:36rpx;height:36rpx;" />
        <text>评价本次消费</text>
      </view>
    </view>
    <view class="review-entry reviewed" v-if="OrderInfodata.status === 2 && OrderInfodata.is_reviewed">
      <text class="reviewed-text">已评价，感谢您的反馈</text>
    </view>

    <view class="control-title">
      消费须知
    </view>
    <!-- 温馨提示 -->
    <view class="notes">
      <view class="line">1. 下单5分钟内可以取消订单或更换，超时将无法取消！</view>
      <view class="line">2. 订单无法暂停，迟到消费，按原预订时间开始计费！</view>
      <view class="line">3. 订单可以提前开始消费，提前开始提前结束。</view>
      <view class="line">4. 特殊情况，或需要帮助，请联系客服处理！</view>
      <view class="line">5. 离场时请带好随身物品，以防丢失！</view>
      <view class="line">6. 支付的押金,正常结束订单5分钟后,自动原路退回！</view>
      <view class="line">7. 部分订单有最低消费标准，下单时请留意！</view>
    </view>

    <!-- 取消订单弹窗 -->
    <u-popup :show="cancelOrderShow" mode="center" @close="cancelOrderShow = false">
      <view class="dialog">
        <view class="dialog-title">取消订单</view>
        <view class="item">
          <label>当前位置：</label>
          <text>{{ OrderInfodata.store_name }}（{{ OrderInfodata.room_name }}（{{ ['未知','小包','中包','大包','豪包','商务包','斯洛克','中式黑八','美式球桌'][OrderInfodata.room_type] }}）</text>
        </view>
        <view class="item">
          <label>预约时间：</label>
          <text>{{ OrderInfodata.create_time }}</text>
        </view>
        <view class="item">
          <label>是否确认取消订单？</label>
        </view>
        <view class="item">
          <view class="color-attention note" v-if="OrderInfodata.pay_type == 1">取消订单后，微信支付退款将在1-3个工作日内原路退回！</view>
          <view class="color-attention note" v-else>取消订单后，费用将会退还到您原支付方式账户中！</view>
        </view>
        <view class="dialog-btns">
          <view class="btn" @tap="cancelOrderShow = false">暂不取消</view>
          <view class="btn active" @tap="cancelConfirm">确认取消</view>
        </view>
      </view>
    </u-popup>

    <!-- 取消订单成功弹窗 -->
    <u-popup :show="cancelOrderSuccess" mode="center" @close="cancelOrderSuccess = false">
      <view class="dialog">
        <view class="dialog-title">订单取消成功</view>
        <view class="item">
          <label>已为您成功取消下列订单</label>
        </view>
        <view class="item">
          <label>当前位置：</label>
          <text>{{ OrderInfodata.store_name }} {{ OrderInfodata.room_name }} （{{ ['特价包','小包','中包','大包','豪包','商务包','斯洛克','中式黑八','美式球桌'][OrderInfodata.room_type] }}）</text>
        </view>
        <view class="item">
          <label>预约时间：</label>
          <text>{{ OrderInfodata.create_time }}</text>
        </view>
        <view class="item">
          <view class="color-attention note" v-if="OrderInfodata.pay_type == 1">订单取消成功，微信支付退款将在1-3个工作日内原路退回！</view>
          <view class="color-attention note" v-else>订单取消成功，费用已返还到原支付账户！</view>
        </view>
        <view class="dialog-btns">
          <view class="btn active" @tap="SucessConfirm">好的</view>
        </view>
      </view>
    </u-popup>

    <!-- 导航和客服弹窗 -->
    <view class="overlay-mask" v-if="show" @click="onClickHide">
      <view class="popup navigation" v-if="popupIndex === 0" @tap.stop>
        <view class="title">导航到店</view>
        <view class="sub-title">可选择您所需要的服务</view>
        <view class="btn" @tap="goTencentMap">
          <image src="/static/icon/nav.png" />
          地图导航
        </view>
        <view class="btn" @tap="goGuide">
          <image src="/static/icon/guide.png" />
          位置指引
        </view>
      </view>
      <view class="popup service" v-if="popupIndex === 1" @tap.stop>
        <view class="title">联系客服</view>
        <view class="sub-title">可选择您所需要的服务</view>
        <view class="btn" @tap="call">
          <image src="/static/icon/phone-call.png" />
          {{ OrderInfodata.phone }}
        </view>
      </view>
    </view>

    <!-- 空调控制弹窗 -->
    <view class="ac-control">
      <view class="ac-control__modal" :class="{ 'ac-control__modal--show': kongtiaoShow }" @tap="hideModal">
        <view class="ac-control__modal-content" @tap.stop>
          <view class="ac-control__modal-header">
            <text class="ac-control__modal-title">空调控制</text>
            <text class="ac-control__modal-close" @tap="hideModal">×</text>
          </view>
          <view class="ac-control__modal-body">
            <view class="ac-control__temperature-control">
              <button class="ac-control__control-button" @tap="adjustTemperature" data-delta="-1">-</button>
              <view class="ac-control__temperature-display">
                <text class="ac-control__temperature-text">温度</text>
              </view>
              <button class="ac-control__control-button" @tap="adjustTemperature" data-delta="1">+</button>
            </view>
            <view class="ac-control__fan-control">
              <button class="ac-control__control-button" @tap="adjustFanSpeed" data-delta="-1">-</button>
              <view class="ac-control__fan-display">
                <text class="text">风量</text>
                <view class="ac-control__fan-level">
                  <view v-for="(item, index) in [1,2,3,4,5]" :key="index" class="ac-control__fan-dot" :class="{ 'ac-control__fan-dot--active': fanSpeed >= index + 1 }"></view>
                </view>
              </view>
              <button class="ac-control__control-button" @tap="adjustFanSpeed" data-delta="1">+</button>
            </view>

            <view class="ac-control__mode-grid">
              <view class="ac-control__mode-button" :class="{ 'ac-control__mode-button--active': mode === 'cool' }" @tap="setMode" data-mode="cool">
                <text class="ac-control__mode-icon">❄️</text>
                <text>制冷</text>
              </view>
              <view class="ac-control__mode-button" :class="{ 'ac-control__mode-button--active': mode === 'heat' }" @tap="setMode" data-mode="heat">
                <text class="ac-control__mode-icon">🔆</text>
                <text>制热</text>
              </view>
              <view class="ac-control__mode-button" :class="{ 'ac-control__mode-button--active': mode === 'auto' }" @tap="setMode" data-mode="auto">
                <text class="ac-control__mode-icon">🔄</text>
                <text>自动</text>
              </view>
            </view>

            <view class="ac-control__swing-row">
              <view class="ac-control__swing-button" :class="{ 'ac-control__swing-button--active': verticalSwing }" @tap="toggleVerticalSwing">
                <text class="ac-control__mode-icon">↕️</text>
                <text>上下扫风</text>
              </view>
              <view class="ac-control__swing-button" :class="{ 'ac-control__swing-button--active': horizontalSwing }" @tap="toggleHorizontalSwing">
                <text class="ac-control__mode-icon">↔️</text>
                <text>左右扫风</text>
              </view>
            </view>
            <view class="btn">
              <button class="ac-control__power-button ac-control__power-button--on" @tap="togglePowerOn">
                开机
              </button>
              <button class="ac-control__power-button ac-control__power-button--off" @tap="togglePowerOff">
                关机
              </button>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 续费弹窗 -->
    <u-popup :show="renewShow" mode="bottom" :custom-style="{ height: '82%', borderRadius: '17rpx 17rpx 0rpx 0rpx', background: 'linear-gradient(180deg, #C9FFD7 0%, #FFFFFF 20%, #FFFFFF 100%)' }" @close="renewCancel">
      <view class="renewBox">
        <view class="title">订单续费</view>
        <view class="line">
          <text class="bold">订单原结束时间</text>
          <text>{{ OrderInfodata.end_time }}</text>
        </view>
        <view class="line">
          <text class="bold">续费后结束时间</text>
          <text>{{ newTime }}</text>
        </view>
        <view class="mode-slot" @tap="modeChange">
          <view data-index="0" :class="{ active: modeIndex === 0 }">小时续费</view>
          <view data-index="1" :class="{ active: modeIndex === 1 }">套餐续费</view>
        </view>
        <view v-if="modeIndex === 0">
          <view class="line">
            <text class="bold">续费时长：</text>
            <view class="time">
              <image @tap="onRenewMinus" src="/static/icon/minus.png" />
              <text>{{ addTime || 0 }} 小时</text>
              <image @tap="onRenewAdd" src="/static/icon/add.png" />
            </view>
          </view>
          <view class="line">
            <text class="bold">小时单价：</text>
            <text class="bold">￥{{ OrderInfodata.room_price }}/小时</text>
          </view>
          <view v-if="roominfodata.vipPriceList">
            <view class="vipPrice">
              <view class="priceInfo" v-for="(item, index) in roominfodata.vipPriceList" :key="index">
                <view class="vipName">{{ item.vip_name }}</view>
                <view class="price">
                  ￥{{ item.price }}
                  <text class="priceText">元</text>
                </view>
              </view>
            </view>
          </view>
        </view>
        <view class="line" v-if="modeIndex === 0">
          <label class="bold">优惠卡券：</label>
          <view class="coupon" @tap="goCoupon">
            <block v-if="submit_couponInfo.name">
              <block v-if="submit_couponInfo.type == 1">
                <view class="price-coupon">
                  {{ submit_couponInfo.name }}(抵扣{{ submit_couponInfo.amount }}小时)
                </view>
              </block>
              <block v-if="submit_couponInfo.type == 2">
                <view class="price-coupon">
                  {{ submit_couponInfo.name }}(满减{{ submit_couponInfo.amount }}元)
                </view>
              </block>
              <block v-if="submit_couponInfo.type == 3">
                <view class="price-coupon">
                  {{ submit_couponInfo.name }}(延长{{ submit_couponInfo.amount }}小时)
                </view>
              </block>
            </block>
            <block v-else>
              <block v-if="couponCount>0">
                <view class="price-coupon">{{ couponCount }}张</view>
              </block>
              <block v-else>
                <view class="price-coupon">暂无</view>
              </block>
            </block>
          </view>
        </view>
        <scroll-view v-if="modeIndex === 1 && pkgList.length>0" scroll-x class="mode" @scroll="handleScroll" @scrolltoupper="handleScrollStart">
          <view class="mode-container">
            <view class="item" :class="{ active: select_pkg_index == index }" @tap="selectPkgInfo" :data-id="item.pkg_id" v-for="(item, index) in pkgList" :key="index" :data-index="index" :data-hour="item.hours">
              <view class="top">
                <view class="left">
                  <text class="pkgName">{{ item.pkg_name }}</text>
                </view>
                <text class="price">¥ {{ item.price }}</text>
              </view>
              <view class="line"></view>
              <view class="bottom">{{ item.desc }}</view>
              <view class="bottom">可用时段：{{ item.time_quantum }}</view>
              <view class="pkgInfo">
                <view class="bottom">{{ item.balance_buy?"可余额支付":"不支持余额支付" }}</view>
              </view>
            </view>
          </view>
        </scroll-view>
        <view class="progress" v-if="modeIndex === 1 && pkgList.length>1">
          <view class="progress-marker" :style="{ left: scrollPosition+'%', width: (100 / pkgList.length)+'%' }"></view>
        </view>
        <view class="divide-line"></view>
        <view class="section orderPrice orderPay">
          <view class="line">
            <text class="bold">支付方式：</text>
          </view>
          <radio-group class="line" style="margin-bottom: 20rpx;" @change="radioChange">
            <label class="pay" v-for="(item, index) in payTypes" :key="index">
              <view class="left" data-index="1" v-if="item.value == 1">
                <view class="item">
                  <image src="/static/icon/wepay.png" />
                  <text>微信</text>
                </view>
                <view class="selector" :class="{ active: pay_type == 1 && item.checked }"></view>
              </view>
              <view class="right-item" data-index="2" v-if="item.value == 2">
                <image src="/static/icon/wallet.png" style="width:36rpx;height:36rpx;" />
                <view class="desc">
                  <view>
                    余￥
                    {{ balance }}
                  </view>
                  <view>
                    赠￥
                    {{ giftBalance }}
                  </view>
                </view>
                <view class="selector" :class="{ active: pay_type == 2 && item.checked }"></view>
              </view>
              <radio style="opacity: 0;" :value="item.value" :checked="item.checked" />
            </label>
          </radio-group>
          <view class="line">
            <view class="btn" @tap="renewCancel">取消</view>
            <view class="btn active" @tap="SubmitOrderInfoData">确认</view>
          </view>
        </view>
      </view>
    </u-popup>

    <!-- WiFi信息弹窗 -->
    <u-popup :show="wifiShow" mode="center" @close="wifiShow = false">
      <view class="dialog wifiDialog">
        <view class="dialog-title">WiFi信息</view>
        <view class="item">
          <label>WiFi名称: </label>
          <text>{{ OrderInfodata.wifi_name }}</text>
        </view>
        <view class="item">
          <label>Wifi密码: </label>
          <text>{{ OrderInfodata.wifi_password }}</text>
        </view>
        <view class="btn">
          <button class="copy" @tap="copyWifi" :data-ssid="OrderInfodata.wifi_name" :data-pwd="OrderInfodata.wifi_password">
            复制密码
          </button>
          <button class="connect" @tap="startWifi" :data-ssid="OrderInfodata.wifi_name" :data-pwd="OrderInfodata.wifi_password">
            一键连接
          </button>
        </view>
        <view class="info">
          部分机型不支持一键连接,请复制密码自行连接
        </view>
      </view>
    </u-popup>

    <!-- 订单二维码弹窗 -->
    <view v-if="showQrModal" class="modal-mask" @touchmove.stop.prevent>
      <view class="modal-content" @tap.stop>
        <view class="modal-title">请将二维码对准识别区</view>
        <view class="qrcode-canvas-wrapper">
          <canvas class="qrcode-canvas" canvas-id="myQrcode2"></canvas>
        </view>
        <view class="guide-section">
          <image src="/static/img/zhiyin.jpg" class="guide-image" mode="aspectFit" />
        </view>
        <button class="close-btn" @tap="closeModal">关闭</button>
      </view>
    </view>
  </view>
</template>


<script>
import dayjs from 'dayjs'
import http from '@/utils/http-legacy'
import drawQrcode from '@/utils/weapp.qrcode.min.js'

export default {
  data() {
    return {
      renewShow: false,
      cancelOrderShow: false,
      cancelOrderSuccess: false,
      kongtiaoShow: false,
      temperature: 26,
      mode: '',
      verticalSwing: false,
      horizontalSwing: false,
      fanSpeed: 0,
      fanDelta: 0,
      power: false,
      wifiShow: false,
      order_no: '',
      orderKey: '',
      isLogin: false,
      OrderInfodata: {},
      roominfodata: {},
      RoomImageList: [],
      damenSwitchBool: false,
      roomSwitchBool: false,
      userinfo: {},
      addTime: 0,
      newTime: '',
      totalPay: 0,
      payTypes: [
        { name: "微信支付", value: 1, checked: true },
        { checked: false, name: "钱包余额", value: 2 },
      ],
      pay_type: 1,
      weixinOrderNo: '',
      renewOrderNo: '',
      giftBalance: 0,
      balance: 0,
      submit_couponInfo: {},
      coupon_id: '',
      couponCount: 0,
      show: false,
      popupIndex: 0,
      modeIndex: 0,
      scrollPosition: 0,
      select_pkg_index: -1,
      showQrModal: false,
      pkgList: [],
      pkg_id: '',
      isManager: false
    }
  },

  onLoad(options) {
    const app = getApp()
    this.isLogin = app.globalData.isLogin || false
    var userType = app.globalData.userType || 11
    this.isManager = (userType == 12 || userType == 13)
    
    let orderNo = ''
    let orderKey = ''
    if (options.order_no) {
      orderNo = options.order_no
    }
    if (options.orderNo) {
      orderNo = options.orderNo
    }
    if (options.orderKey) {
      orderKey = options.orderKey
    }
    if (!options.toPage) {
      const query = uni.getEnterOptionsSync().query
      if (query) {
        if (query.order_no) {
          orderNo = query.order_no
        }
        if (query.orderNo) {
          orderNo = query.orderNo
        }
        if (query.orderKey) {
          orderKey = query.orderKey
        }
      }
    }
    this.order_no = orderNo
    this.orderKey = orderKey
  },

  onShow() {
    this.getOrderInfoData()
    this.getCouponListData()
    this.checkUserType()
  },

  onShareAppMessage() {
    return {
      title: '打开订单，一键开门!',
      path: '/pages/order/detail?toPage=true&orderKey=' + this.orderKey,
      success: (res) => {
        if (res.confirm) {
          uni.showToast({
            title: "分享成功",
            icon: 'success',
            duration: 2000
          })
        }
      }
    }
  },

  computed: {
    couponTypeText() {
      const map = { 1: '抵扣', 2: '满减', 3: '加时' }
      return map[this.OrderInfodata.coupon_type] || ''
    }
  },

  methods: {
    checkUserType() {
      var that = this
      var app = getApp()
      if (!app.globalData.isLogin) return
      http.request(
        '/member/user/get',
        '1',
        'get',
        {},
        app.globalData.userDatatoken ? app.globalData.userDatatoken.access_token : '',
        '',
        function success(info) {
          if (info.code == 0 && info.data) {
            var ut = info.data.user_type || 11
            that.isManager = (ut == 12 || ut == 13)
          }
        },
        function fail() {}
      )
    },

    // 辅助函数：分割时间
    splitTime(timeStr) {
      if (!timeStr) return ['', '']
      const parts = timeStr.split(' ')
      if (parts.length < 2) return ['', '']
      const time = parts[1].split(':')
      return [time[0] || '', time[1] || '']
    },
    
    // 辅助函数：分割空格
    splitKongge(timeStr) {
      if (!timeStr) return ['']
      return timeStr.split(' ')
    },

    copyOrderNo(e) {
      uni.setClipboardData({
        data: e.currentTarget.dataset.order,
        success: () => {
          uni.showToast({ title: "订单号已复制" })
        },
      })
    },

    goReview() {
      const d = this.OrderInfodata
      uni.navigateTo({
        url: `/pages/order/review?order_id=${d.order_id || d.id}&store_name=${encodeURIComponent(d.store_name || '')}&room_name=${encodeURIComponent(d.room_name || '')}`
      })
    },

    onClickShow(e) {
      const { index } = e.currentTarget.dataset
      this.show = true
      this.popupIndex = +index
    },

    onClickHide() {
      this.show = false
    },

    goGuide() {
      uni.navigateTo({
        url: `/pagesA/guide/index?storeId=${this.OrderInfodata.store_id}`,
      })
    },

    modeChange(e) {
      const { index } = e.target.dataset
      this.modeIndex = +index
      this.pay_type = 1
      this.select_pkg_index = -1
      
      if (index == 0) {
        this.pkg_id = ''
        this.addTime = 0
        this.timeChange(0)
      }
    },

    selectPkgInfo(event) {
      const pkgIndex = event.currentTarget.dataset.index
      const pkgId = event.currentTarget.dataset.id
      const hour = event.currentTarget.dataset.hour
      const newTime = dayjs(this.OrderInfodata.end_time)
        .add(hour, "hours")
        .format("YYYY/MM/DD HH:mm")
      
      this.select_pkg_index = pkgIndex
      this.pkg_id = pkgId
      this.pay_type = 1
      this.newTime = newTime
      this.totalPay = this.pkgList[pkgIndex].price
      
      const payTypes = this.payTypes
      payTypes[0].checked = true
      payTypes[1].checked = false
      this.payTypes = payTypes
    },

    convertEnableTime(enableTime) {
      if (
        (enableTime.length === 24 &&
          enableTime.every((num, index) => num === index)) ||
        enableTime.length === 0
      ) {
        return "全天可用"
      } else {
        const startTime = enableTime[0].toString().padStart(2, "0")
        const endTime = enableTime[enableTime.length - 1]
          .toString()
          .padStart(2, "0")
        return `${startTime}:00 - ${endTime}:00可用`
      }
    },

    convertEnableWeek(enableWeek) {
      if (!enableWeek || !Array.isArray(enableWeek) || enableWeek.length === 0) {
        return "周一至周日"
      }
      const weekdays = ["一", "二", "三", "四", "五", "六", "周日"]
      const selectedWeekdays = enableWeek.map((day) => weekdays[day - 1])

      if (enableWeek.length === 7) {
        return "周一至周日"
      } else {
        return `周${selectedWeekdays.join("、")}`
      }
    },

    getPkgList() {
      const app = getApp()
      if (app.globalData.isLogin) {
        http.request(
          "/member/pkg/getPkgPage",
          "1",
          "post",
          {
            store_id: this.OrderInfodata.store_id,
            room_id: this.OrderInfodata.room_id,
          },
          app.globalData.userDatatoken.access_token,
          "",
          (info) => {
            if (info.code == 0) {
              const newMeals = info.data.list.map((el) => ({
                ...el,
                desc:
                  this.convertEnableWeek(el.enable_week) +
                  ", " +
                  this.convertEnableTime(el.enable_time),
              }))

              this.pkgList = newMeals
            } else {
              uni.showModal({
                content: info.msg,
                showCancel: false,
              })
            }
          },
          () => {}
        )
      }
    },

    getPrice(startDate) {
      const day = new Date(startDate).getDay()
      switch (day) {
        case 1:
        case 2:
        case 3:
        case 4:
          return this.OrderInfodata.work_price
        case 0:
        case 5:
        case 6:
          return this.OrderInfodata.room_price
      }
    },

    timeChange(addTime) {
      const newTime = dayjs(this.OrderInfodata.end_time)
        .add(addTime, "hours")
        .format("YYYY/MM/DD HH:mm")
      this.addTime = addTime
      this.newTime = newTime
      this.totalPay = (addTime * this.getPrice(newTime)).toFixed(2)
    },

    onRenewAdd() {
      let addTime = this.addTime + 1
      if (addTime > 8) return
      this.timeChange(addTime)
    },

    onRenewMinus() {
      let addTime = this.addTime - 1
      if (addTime < 0) return
      this.timeChange(addTime)
    },

    endOrder() {
      uni.showModal({
        title: '提示',
        content: '由于订单已开始计费，提前结束将无法退款。因设备故障等原因导致无法正常消费的，请联系客服人工处理。请问您是否需要联系客服？',
        cancelText: '取消',
        confirmText: '联系客服',
        complete: (res) => {
          if (res.confirm) {
            this.call()
          }
        }
      })
    },

    beginOrder() {
      const app = getApp()
      if (app.globalData.isLogin) {
        http.request(
          "/member/order/startOrder/" + this.OrderInfodata.order_id,
          "1",
          "post",
          {
            "order_id": this.OrderInfodata.order_id
          },
          app.globalData.userDatatoken.access_token,
          "",
          (info) => {
            if (info.code == 0) {
              uni.showToast({
                title: '确认到店成功',
              })
              this.getOrderInfoData()
            } else {
              uni.showModal({
                content: info.msg,
                showCancel: false,
              })
            }
          },
          () => {}
        )
      }
    },

    goChangeDoor() {
      const orderInfo = {
        "order_id": this.OrderInfodata.order_id,
        "store_id": this.OrderInfodata.store_id,
        "store_name": this.OrderInfodata.store_name,
        "room_name": this.OrderInfodata.room_name,
        "start_time": this.OrderInfodata.start_time,
        "end_time": this.OrderInfodata.end_time,
        "room_type": this.OrderInfodata.room_type,
        "room_id": this.OrderInfodata.room_id,
        "room_class": this.OrderInfodata.room_class,
        "phone": this.OrderInfodata.phone,
      }
      uni.navigateTo({
        url: '/pages/room/change?orderInfo=' + encodeURIComponent(JSON.stringify(orderInfo)),
      })
    },

    getRoomInfodata(roomId) {
      const app = getApp()
      http.request(
        "/member/index/getRoomInfo" + "/" + roomId,
        "1",
        "post",
        {
          room_id: roomId,
        },
        app.globalData.userDatatoken.access_token,
        "获取中...",
        (info) => {
          if (info.code == 0) {
            this.roominfodata = info.data
          } else {
            uni.showModal({
              content: info.msg,
              showCancel: false,
            })
          }
        },
        () => {}
      )
    },

    renewClick() {
      const OrderInfodata = this.OrderInfodata
      if (OrderInfodata.status == 3 || OrderInfodata.status == 2) {
        uni.showToast({
          title: "订单已结束！",
          icon: "error",
        })
      } else {
        this.getRoomInfodata(OrderInfodata.room_id)
        const app = getApp()
        if (app.globalData.isLogin) {
          this.renewShow = true
          this.coupon_id = ''
          this.submit_couponInfo = {}
          this.payTypes = [
            { name: "微信支付", value: 1, checked: true },
            { checked: false, name: "钱包余额", value: 2 },
          ]
          this.getPkgList()
        } else {
          this.renewShow = true
          this.payTypes = [{ name: "微信支付", value: 1, checked: true }]
        }
      }
    },

    radioChange(e) {
      const type = e.detail.value
      const payTypes = this.payTypes
      if (type == 1) {
        payTypes[0].checked = true
        payTypes[1].checked = false
      } else {
        payTypes[0].checked = false
        payTypes[1].checked = true
      }
      this.pay_type = type
      this.payTypes = payTypes
    },

    SubmitOrderInfoData() {
      if (
        (this.modeIndex === 0 && !this.addTime) ||
        (this.modeIndex === 1 && !this.pkg_id)
      ) {
        uni.showToast({
          title: this.modeIndex === 1 ? "请选择套餐" : "请选择增加时间",
          icon: "none",
        })
        return false
      }
      
      const app = getApp()
      if (app.globalData.isLogin) {
        http.request(
          "/member/order/preOrder",
          "1",
          "post",
          {
            room_id: this.OrderInfodata.room_id,
            coupon_id: this.coupon_id,
            start_time: this.OrderInfodata.end_time,
            end_time: this.newTime,
            order_id: this.OrderInfodata.order_id,
            pay_type: this.pay_type,
            pkg_id: this.pkg_id,
          },
          app.globalData.userDatatoken.access_token,
          "提交中...",
          (info) => {
            if (info.code == 0) {
              this.renewOrderNo = info.data.order_no
              if (this.pay_type == 1 && info.data.pay_amount > 0) {
                this.lockWxOrder(info)
              } else {
                this.renewConfirm()
              }
            } else {
              uni.showModal({
                title: "温馨提示",
                content: info.msg,
                showCancel: false,
                confirmText: "确定",
              })
            }
          },
          () => {}
        )
      }
    },

    lockWxOrder(pay) {
      const app = getApp()
      if (app.globalData.isLogin) {
        http.request(
          "/member/order/lockWxOrder",
          "1",
          "post",
          {
            room_id: this.OrderInfodata.room_id,
            coupon_id: this.coupon_id,
            start_time: this.OrderInfodata.end_time,
            end_time: this.newTime,
            order_id: this.OrderInfodata.order_id
          },
          app.globalData.userDatatoken.access_token,
          "提交中...",
          (info) => {
            if (info.code == 0) {
              this.payMent(pay)
            } else {
              uni.showModal({
                title: '温馨提示',
                content: info.msg,
                showCancel: false,
                confirmText: "确定",
              })
            }
          },
          () => {}
        )
      }
    },

    payMent(pay) {
      uni.requestPayment({
        'provider': 'wxpay',
        'timeStamp': pay.data.timeStamp,
        'nonceStr': pay.data.nonceStr,
        'package': pay.data.pkg,
        'signType': pay.data.signType,
        'paySign': pay.data.paySign,
        'success': () => {
          this.getOrderInfoData()
          this.renewCancel()
        },
        'fail': () => {
          uni.showToast({
            title: '支付失败!',
            icon: 'error'
          })
        }
      })
    },

    handleScroll(e) {
      const { scrollLeft, scrollWidth } = e.detail
      let itemLength = 0
      if (this.modeIndex === 1 && this.pkgList.length) {
        itemLength = scrollWidth / this.pkgList.length
      }
      const position = scrollLeft / (scrollWidth - itemLength)
      this.scrollPosition = position * 100
    },

    handleScrollStart() {
      this.scrollPosition = 0
    },

    renewConfirm() {
      if (!this.newTime) {
        uni.showToast({
          title: '请选择增加时间',
          icon: "none"
        })
        return false
      }
      
      // 余额支付时检查余额是否足够
      if (this.pay_type == 2 && this.totalPay > 0) {
        const totalBalance = (this.balance || 0) + (this.giftBalance || 0)
        if (totalBalance < this.totalPay) {
          uni.showToast({
            title: '余额不足，请充值或选择微信支付',
            icon: "none"
          })
          return false
        }
      }
      
      const app = getApp()
      if (app.globalData.isLogin) {
        http.request(
          "/member/order/renew",
          "1",
          "post",
          {
            "order_id": this.OrderInfodata.order_id,
            "coupon_id": this.coupon_id,
            "end_time": this.newTime,
            "pay_type": this.pay_type,
            "order_no": this.renewOrderNo,
            "pkg_id": this.pkg_id,
          },
          app.globalData.userDatatoken.access_token,
          "",
          (info) => {
            if (info.code == 0) {
              uni.showToast({
                title: '续时成功',
              })
              this.getOrderInfoData()
              this.renewCancel()
            } else {
              uni.showModal({
                content: info.msg,
                showCancel: false,
              })
            }
          },
          () => {}
        )
      }
    },

    renewCancel() {
      this.renewShow = false
      this.addTime = 0
      this.newTime = ''
      this.renewOrderNo = ''
      this.totalPay = 0
      this.pay_type = 1
    },

    SucessConfirm() {
      this.getOrderInfoData()
      this.renewCancel()
      this.cancelOrderSuccess = false
    },

    cancelOrder(e) {
      const astatus = e.currentTarget.dataset.info
      if (astatus == 2 || astatus == 3) {
        uni.showToast({
          title: '订单已完成，暂无法取消！',
          icon: 'none'
        })
      } else {
        this.cancelOrderShow = true
      }
    },

    stopOrder(e) {
      const orderId = e.currentTarget.dataset.id
      let text = ''
      if (this.OrderInfodata.prePay) {
        text = '未消费金额及押金在订单结束5分钟后会自动退还，请问是否确认提前离店？'
      } else {
        text = '提前离店不退费，会立即结束订单，已支付的押金5分钟后会自动退还，请问是否确认提前离店？'
      }
      uni.showModal({
        title: '温馨提示',
        content: text,
        showCancel: true,
        success: (res) => {
          if (res.confirm) {
            uni.showLoading({
              title: '关台中...',
            })
            const app = getApp()
            http.request(
              "/member/order/closeOrder/" + orderId,
              "1",
              "post",
              {},
              app.globalData.userDatatoken.access_token,
              "",
              (info) => {
                uni.hideLoading()
                if (info.code == 0) {
                  this.getOrderInfoData()
                } else {
                  uni.showModal({
                    title: '温馨提示',
                    content: info.msg,
                    showCancel: false
                  })
                }
              }
            )
          }
        }
      })
    },

    cancelConfirm() {
      const app = getApp()
      if (app.globalData.isLogin) {
        if (this.OrderInfodata) {
          http.request(
            "/member/order/cancelOrder" + '/' + this.OrderInfodata.order_id,
            "1",
            "post",
            {
              "order_id": this.OrderInfodata.order_id
            },
            app.globalData.userDatatoken.access_token,
            "",
            (info) => {
              if (info.code == 0) {
                this.getOrderInfoData()
                this.renewCancel()
                this.cancelOrderShow = false
                this.cancelOrderSuccess = true
              } else {
                uni.showModal({
                  content: info.msg,
                  showCancel: false,
                })
              }
            },
            () => {}
          )
        }
      }
    },

    getOrderInfoData() {
      const app = getApp()
      http.request(
        "/member/order/getOrderInfoByNo",
        "1",
        "get",
        {
          "order_no": this.order_no,
          "order_key": this.orderKey
        },
        app.globalData.userDatatoken.access_token,
        "获取中...",
        (info) => {
          if (info.code === 0) {
            this.OrderInfodata = info.data
            this.orderKey = info.data.order_key || info.data.orderKey
            this.getStoreBalance()
          } else {
            uni.showModal({
              title: '温馨提示',
              content: info.msg,
              showCancel: false,
              success: (res) => {
                if (res.confirm) {
                  const pages = getCurrentPages()
                  if (pages.length > 1) {
                    uni.navigateBack({
                      delta: 1
                    })
                  }
                  if (pages.length == 1) {
                    uni.reLaunch({
                      url: '/pages/door/list',
                    })
                  }
                }
              }
            })
          }
        },
        () => {}
      )
    },

    call() {
      const phone = this.OrderInfodata.phone || ''
      const phoneLength = phone.length
      if (phoneLength > 0) {
        if (phoneLength == 11) {
          uni.makePhoneCall({
            phoneNumber: phone,
          })
        } else {
          uni.showModal({
            title: '提示',
            content: '客服上班时间10：00~23：00\r\n如您遇到问题，建议先查看"使用帮助"！\r\n本店客服微信号：' + phone,
            confirmText: '复制',
            complete: (res) => {
              if (res.confirm) {
                uni.setClipboardData({
                  data: phone,
                  success: () => {
                    uni.showToast({ title: '微信号已复制到剪贴板！' })
                  }
                })
              }
            }
          })
        }
      }
    },

    copy() {
      // No wechat field in database - method kept for compatibility
    },

    goTencentMap() {
      const store = this.OrderInfodata
      this.goMap(store)
    },

    goMap(store) {
      uni.openLocation({
        latitude: store.latitude,
        longitude: store.longitude,
        name: store.store_name,
        address: store.address,
        scale: 28
      })
    },

    damenbindchange() {
      const app = getApp()
      http.request(
        "/member/order/openStoreDoor?orderKey=" + this.orderKey,
        "1",
        "post",
        {
          "orderKey": this.orderKey
        },
        app.globalData.userDatatoken.access_token,
        "提交中...",
        (info) => {
          if (info.code == 0) {
            uni.showToast({
              title: "操作成功",
              icon: 'success'
            })
          } else {
            uni.showModal({
              title: "提示",
              content: info.msg,
              showCancel: false,
            })
          }
        },
        () => {}
      )
    },

    openRoomDoor() {
      const app = getApp()
      http.request(
        "/member/order/openRoomDoor?orderKey=" + this.orderKey,
        "1",
        "post",
        {},
        app.globalData.userDatatoken.access_token,
        "提交中...",
        (info) => {
          if (info.code == 0) {
            uni.showToast({
              title: "操作成功",
              icon: 'success',
              duration: 1500
            })
            // 延迟刷新订单数据，确保后端数据库更新完成
            setTimeout(() => {
              this.getOrderInfoData()
            }, 500)
          } else {
            uni.showModal({
              title: "提示",
              content: info.msg,
              showCancel: false,
            })
          }
        },
        () => {}
      )
    },

    roomOpen() {
      const startTime = new Date(this.OrderInfodata.start_time)
      if (this.OrderInfodata.status == 0 && startTime > Date.now()) {
        uni.showModal({
          title: '温馨提示',
          content: '当前还未到预约时间，是否提前开始消费？',
          success: (res) => {
            if (res.confirm) {
              this.openRoomDoor()
            }
          }
        })
      } else {
        this.openRoomDoor()
      }
    },

    getStoreBalance() {
      const app = getApp()
      if (app.globalData.isLogin) {
        http.request(
          "/member/user/getStoreBalance/" + this.OrderInfodata.store_id,
          "1",
          "get",
          {
            "store_id": this.OrderInfodata.store_id
          },
          app.globalData.userDatatoken.access_token,
          "",
          (info) => {
            if (info.code == 0) {
              this.giftBalance = info.data.gift_balance || 0
              this.balance = info.data.balance || 0
            }
          },
          () => {}
        )
      }
    },

    showModal() {
      // 只要订单状态正确就可以控制空调（网关设备已包含空调控制）
      if (this.OrderInfodata.status === 0 || this.OrderInfodata.status === 1) {
        this.kongtiaoShow = true
      } else {
        uni.showModal({
          title: '温馨提示',
          content: '订单已结束，无法控制空调',
          showCancel: false,
        })
      }
    },

    hideModal() {
      this.kongtiaoShow = false
    },

    adjustTemperature(e) {
      const delta = parseInt(e.currentTarget.dataset.delta)
      if (delta == 1) {
        this.sendKongtiaoControl(67)
      } else {
        this.sendKongtiaoControl(68)
      }
    },

    setMode(e) {
      const newMode = e.currentTarget.dataset.mode
      this.mode = newMode
      setTimeout(() => {
        this.mode = ''
      }, 300)
      if (newMode == 'cool') {
        this.sendKongtiaoControl(20)
      } else if (newMode == 'heat') {
        this.sendKongtiaoControl(21)
      } else if (newMode == 'auto') {
        this.sendKongtiaoControl(24)
      }
    },

    toggleVerticalSwing() {
      this.verticalSwing = !this.verticalSwing
      if (this.verticalSwing) {
        this.sendKongtiaoControl(63)
      } else {
        this.sendKongtiaoControl(65)
      }
    },

    toggleHorizontalSwing() {
      this.horizontalSwing = !this.horizontalSwing
      if (this.horizontalSwing) {
        this.sendKongtiaoControl(64)
      } else {
        this.sendKongtiaoControl(66)
      }
    },

    adjustFanSpeed(e) {
      const delta = parseInt(e.currentTarget.dataset.delta)
      let newSpeed = this.fanSpeed + delta
      newSpeed = Math.max(1, Math.min(5, newSpeed))
      this.fanSpeed = newSpeed
      this.fanDelta = delta
      
      if (delta == 1) {
        this.sendKongtiaoControl(69)
      } else {
        this.sendKongtiaoControl(70)
      }
    },

    togglePowerOn() {
      this.sendKongtiaoControl(0)
    },

    togglePowerOff() {
      this.sendKongtiaoControl(1)
    },

    sendKongtiaoControl(cmd) {
      const startTime = new Date(this.OrderInfodata.start_time)
      if (this.OrderInfodata.status == 0 && startTime > Date.now()) {
        uni.showModal({
          title: '提示',
          content: '订单将从当前时间开始计费。您确定要提前开始吗？',
          cancelText: '继续等待',
          confirmText: '现在开始',
          complete: (res) => {
            if (res.confirm) {
              this.openRoomDoor()
              this.controlKT(cmd)
            }
          }
        })
      } else {
        this.controlKT(cmd)
      }
    },

    controlKT(cmd) {
      const app = getApp()
      http.request(
        "/member/order/controlKT",
        "1",
        "post",
        {
          "orderKey": this.orderKey,
          "cmd": cmd
        },
        app.globalData.userDatatoken.access_token,
        "提交中...",
        (info) => {
          if (info.code == 0) {
            uni.showToast({
              title: "操作成功",
              icon: 'none'
            })
          } else {
            uni.showModal({
              title: "提示",
              content: info.msg,
              showCancel: false,
            })
          }
        },
        () => {}
      )
    },

    goCoupon() {
      if (!this.newTime) {
        uni.showToast({
          title: '请先选择时间',
          icon: 'none'
        })
        return
      }
      uni.navigateTo({
        url: '/pages/coupon/index?from=1&roomId=' + this.OrderInfodata.room_id + '&nightLong=false' + '&startTime=' + this.OrderInfodata.end_time + '&endTime=' + this.newTime,
        events: {
          pageDataList: (data) => {
            this.submit_couponInfo = data
            this.coupon_id = data.coupon_id
          },
          pageDataList_no: (data) => {
            this.submit_couponInfo = data
            this.coupon_id = ''
          },
        }
      })
    },

    getCouponListData() {
      const app = getApp()
      if (app.globalData.isLogin) {
        http.request(
          "/member/user/getCouponPage",
          "1",
          "post",
          {
            pageNo: 1,
            pageSize: 100,
            status: 0,
            store_id: this.OrderInfodata.store_id,
          },
          app.globalData.userDatatoken.access_token,
          "",
          (info) => {
            if (info.code == 0) {
              this.couponCount = info.data.total
            } else {
              uni.showModal({
                content: info.msg,
                showCancel: false,
              })
            }
          },
          () => {}
        )
      }
    },

    showWifi() {
      this.wifiShow = true
    },

    copyWifi() {
      const pwd = this.OrderInfodata.wifi_password
      uni.setClipboardData({
        data: pwd,
        success: () => {
          uni.showToast({ title: '已复制到剪贴板！' })
        }
      })
      this.wifiShow = false
    },

    startWifi() {
      uni.startWifi({
        success: () => {
          this.wifiConnected()
        },
        fail: () => {
          uni.showToast({
            title: '打开WIFI失败',
            icon: 'error'
          })
        }
      })
    },

    wifiConnected() {
      const ssid = this.OrderInfodata.wifi_name
      const pwd = this.OrderInfodata.wifi_password
      uni.connectWifi({
        SSID: ssid,
        password: pwd,
        success: () => {
          uni.showToast({
            title: 'wifi连接成功'
          })
          setTimeout(() => {
            this.wifiShow = false
          }, 2000)
        },
        fail: (res) => {
          let errMsg = '自动连接失败'
          if (res.errCode == "12004") errMsg = "重复连接Wifi"
          if (res.errCode == "12005") errMsg = "未打开Wifi开关"
          if (res.errCode == "12006") errMsg = "未打开GPS定位开关"
          if (res.errCode == "12007") errMsg = "用户拒绝授权连接"
          uni.showToast({
            title: errMsg
          })
        }
      })
    },

    showOrderQr(e) {
      const orderNo = e.currentTarget.dataset.no
      this.showQrModal = true
      this.$nextTick(() => {
        // 使用 weapp.qrcode 生成二维码
        drawQrcode({
          width: 200,
          height: 200,
          canvasId: 'myQrcode2',
          text: orderNo,
          _this: this
        })
      })
    },

    closeModal() {
      this.showQrModal = false
    },
  }
}
</script>


<style lang="scss">
page {
  padding-bottom: 60rpx;
}
</style>
<style lang="scss" scoped>
.top-container {
  box-sizing: border-box;
  position: relative;
}

.top .share {
  margin-right: 10rpx;
  font-size: 24rpx;
  width: 120rpx;
  height: 45rpx;
  text-align: center;
  line-height: 45rpx;
  border-radius: 30rpx;
  display: flex;
  justify-content: center;
}

.timeBox {
  height: 168rpx;
  padding: 10rpx 48rpx;
  background-color: var(--main-color, #6da773fd);
  display: flex;
  justify-content: space-between;
  color: #fff;
}

.timeBox .time .hour {
  font-size: 38rpx;
  margin-bottom: 10rpx;
  font-weight: 600;
  text-align: center;
  align-items: center;
}

.timeBox .time .hour image {
  width: 38rpx;
  height: 38rpx;
}

.timeBox .no-end {
  font-weight: 500;
  font-size: 37rpx;
  color: #FFFFFF;
  margin-top: -50rpx;
}

.timeBox .time .date {
  font-size: 24rpx;
  align-items: center;
}

.timeBox .total {
  width: 244rpx;
  text-align: center;
  border-width: 0;
  font-weight: 400;
  font-size: 36rpx;
  position: relative;
  height: 82rpx;
}

.timeBox .total::after {
  content: ' ';
  position: absolute;
  bottom: 16rpx;
  border: 1rpx solid #fff;
  left: 0;
  background: #fff;
  width: 244rpx;
}

.order-container {
  padding: 5rpx 30rpx;
  margin: 20rpx 0;
}

.wifi-container {
  display: flex;
  justify-content: space-between;
  background-color: #fff;
  padding: 22rpx 40rpx;
  align-items: center;
  border-radius: 17rpx 17rpx 17rpx 17rpx;
}

.wifi-container .left {
  display: flex;
  align-items: center;
}

.wifi-container .left image {
  width: 108rpx;
  height: 72rpx;
  margin-right: 44rpx;
}

.wifi-container .left .bold {
  font-weight: 500;
  font-size: 31rpx;
  color: #000000;
}

.wifi-container .left .desc {
  font-weight: 300;
  font-size: 22rpx;
  color: #817F7F;
  display: flex;
  flex-direction: column;
}

.wifi-container .wifi-btn {
  width: 183rpx;
  height: 61rpx;
  background: var(--main-color, #5AAB6E);
  border-radius: 52rpx 52rpx 52rpx 52rpx;
  line-height: 61rpx;
  text-align: center;
  font-weight: 500;
  font-size: 30rpx;
  color: #FDFFFE;
}

.orderInfo {
  padding: 26rpx 29rpx;
  border-radius: 16rpx;
  margin: 0 27rpx;
  background: #FFFFFF;
  border-radius: 17rpx 17rpx 17rpx 17rpx;
  margin-top: -60rpx;
}

.orderInfo .top {
  display: flex;
  align-items: center;
  font-weight: 600;
  font-size: 31rpx;
  margin-bottom: 30rpx;
}

.orderInfo .tag {
  padding: 8rpx 16rpx;
  background: var(--main-color, #6da773fd);
  border-radius: 35rpx 35rpx 35rpx 35rpx;
  margin-right: 20rpx;
  font-size: 26rpx;
  color: #fff;
  white-space: nowrap;
}

.orderInfo .name {
  font-weight: 500;
  font-size: 34rpx;
  color: #000000;
  height: 42rpx;
  line-height: 42rpx;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.orderInfo .address {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.orderInfo .address .left {
  font-weight: 300;
  font-size: 22rpx;
  color: #464646;
  width: 422rpx;
}

.orderInfo .address .right {
  display: flex;
  justify-content: space-around;
  align-items: center;
  width: 166rpx;
}

.orderInfo .address .right .line {
  width: 0rpx;
  height: 34rpx;
  border: 0.5rpx solid #BCBCBC;
  margin-top: -34rpx;
}

.orderInfo .address .right image {
  width: 39rpx;
  height: 39rpx;
  margin-bottom: 8rpx;
}

.orderInfo .address .right .item {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-weight: 400;
  font-size: 21rpx;
  color: #3E3E3E;
}

.orderInfo .info-line {
  font-weight: 300;
  font-size: 26rpx;
  color: #000000;
  display: flex;
  align-items: center;
  padding: 10rpx 0;
}

.orderInfo .info-line.display-space {
  display: flex;
  justify-content: space-between;
}

.orderInfo .bold {
  font-weight: 500;
  font-size: 26rpx;
}

.review-entry {
  margin: 20rpx 30rpx;
  background: #fff;
  border-radius: 16rpx;
  padding: 24rpx 30rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.review-entry .review-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #5AAB6E, #4a9d5e);
  color: #fff;
  font-size: 30rpx;
  padding: 18rpx 60rpx;
  border-radius: 40rpx;
  gap: 12rpx;
}

.review-entry.reviewed {
  background: transparent;
}

.review-entry .reviewed-text {
  color: #999;
  font-size: 26rpx;
}

.control-title {
  font-weight: 500;
  font-size: 31rpx;
  color: #3E3E3E;
  position: relative;
  width: 180rpx;
  height: 44rpx;
  text-align: center;
  line-height: 44rpx;
  margin: 0 auto;
}

.control-title::before {
  content: '';
  position: absolute;
  width: 20rpx;
  border: 2rpx solid #515151;
  left: -20rpx;
  top: 22rpx;
  height: 0;
}

.control-title::after {
  content: '';
  position: absolute;
  width: 20rpx;
  border: 2rpx solid #515151;
  right: -20rpx;
  top: 22rpx;
  height: 0;
}

.doors {
  display: flex;
  justify-content: space-around;
  padding: 25rpx 0;
  border-radius: 12rpx;
  margin-top: 20rpx;
  flex-wrap: wrap;
}

.doors .door {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 216rpx;
  height: 82rpx;
  background: #FFFFFF;
  border-radius: 9rpx 9rpx 9rpx 9rpx;
  border: 2rpx solid var(--main-color, #5AAB6E);
  font-weight: 600;
  font-size: 24rpx;
  color: var(--main-color, #5AAB6E);
  box-sizing: border-box;
}

.doors .door.m-b-20 {
  margin-bottom: 26rpx;
}

.doors .door.m-b-20.start {
  background-color: var(--main-color, #6da773fd);
  color: #fff;
}

.doors .door .action {
  display: flex;
  align-items: center;
}

.doors .share {
  margin: 0;
}

.doors .door .text {
  font-weight: 600;
  width: 125rpx;
  text-align: center;
  font-size: 26rpx;
}

.bar_btns {
  display: flex;
  justify-content: center;
  margin-top: 20rpx;
}

.bar_btns image {
  width: 30rpx;
  height: 30rpx;
  margin-right: 12rpx;
}

.bar_btns .btnSmall {
  margin: 0rpx 60rpx;
  border-radius: 10rpx;
  padding: 12rpx 30rpx;
  text-align: center;
  box-sizing: border-box;
  font-size: 30rpx;
  background: #F73F4C;
  color: #fff;
  display: flex;
  align-items: center;
}

.notes {
  padding: 30rpx;
  background-color: #fff;
  margin: 30rpx 30rpx;
  border-radius: 17rpx 17rpx 17rpx 17rpx;
}

.notes .line {
  line-height: 45rpx;
  font-size: 22rpx;
  color: #666;
}

.renewBox {
  padding: 61rpx 64rpx;
}

.renewBox .title {
  font-weight: 500;
  font-size: 37rpx;
  color: #000000;
  text-align: center;
  margin-bottom: 26rpx;
}

.renewBox .line {
  font-weight: 300;
  font-size: 26rpx;
  color: #000000;
  display: flex;
  justify-content: space-between;
  height: 70rpx;
  line-height: 70rpx;
}

.renewBox .line .bold {
  font-weight: 500;
}

.renewBox .line .time {
  font-weight: 500;
  display: flex;
  width: 200rpx;
  justify-content: space-between;
  align-items: center;
}

.renewBox .line image {
  width: 34rpx;
  height: 34rpx;
}

.renewBox .divide-line {
  border: 2rpx solid #EFEFEF;
  margin: 26rpx 0;
}

.renewBox .line .left,
.line .right {
  display: flex;
  align-items: center;
}

.renewBox .line .item {
  display: flex;
  align-items: center;
}

.renewBox .line .left image {
  margin-right: 17rpx;
}

.renewBox .line .left .item image {
  width: 55rpx;
  height: 46rpx;
}

.renewBox .line .left .item text {
  margin-left: 14rpx;
  margin-right: 50rpx;
}

.renewBox .line .right-item {
  display: flex;
  align-items: center;
}

.line .right-item .desc {
  font-size: 22rpx;
  line-height: 22rpx;
  color: #3E3E3E;
  margin-left: 10rpx;
  margin-right: 16rpx;
}

.line .right-item .desc view {
  display: flex;
  align-items: center;
  margin-bottom: 6rpx;
}

.line .btn {
  width: 295rpx;
  height: 84rpx;
  background: #BCBCBC;
  border-radius: 52rpx 52rpx 52rpx 52rpx;
  color: #fff;
  text-align: center;
  line-height: 84rpx;
}

.line .btn.active {
  background: var(--main-color, #6da773fd);
}

.mode-slot {
  width: 408rpx;
  background: #F6F6F4;
  border-radius: 35rpx 35rpx 35rpx 35rpx;
  display: flex;
  align-items: center;
  margin: 30rpx auto;
}

.mode-slot view {
  height: 52rpx;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50%;
  font-weight: bold;
  font-size: 26rpx;
  color: #817F7F;
}

.mode-slot view.active {
  background: var(--main-color, #6da773fd);
  color: #fff;
  border-radius: 35rpx 35rpx 35rpx 35rpx;
  box-shadow: 0rpx 5rpx 10rpx 2rpx rgba(0, 0, 0, 0.16);
}

.selector {
  width: 33rpx;
  height: 33rpx;
  border: 3rpx solid #BCBCBC;
  border-radius: 50%;
}

.selector.active {
  border: 3rpx solid #5AAB6E;
  position: relative;
}

.selector.active::after {
  content: '';
  position: absolute;
  width: 22rpx;
  height: 22rpx;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  background-color: var(--main-color, #6da773fd);
  border-radius: 50%;
}

.mode {
  width: 100%;
  margin-top: 30rpx;
}

.mode-container {
  display: flex;
  flex-direction: row;
  white-space: nowrap;
  min-width: 100%;
}

.mode .item {
  width: 329rpx;
  height: 160rpx;
  border-radius: 17rpx 17rpx 17rpx 17rpx;
  margin-right: 18rpx;
  font-size: 20rpx;
  padding: 10rpx 16rpx;
  border: 2rpx solid var(--main-color, #6da773fd);
  color: var(--main-color, #6da773fd);
  display: flex;
  justify-content: space-between;
  flex-direction: column;
  flex: 0 0 auto;
}

.mode .item.active {
  background: var(--main-color, #5AAB6E);
  color: #fff;
}

.mode .item .top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.mode .item .top .left {
  display: flex;
  align-items: center;
}

.mode .item .top .left .pkg_name {
  font-size: 28rpx;
  font-weight: 600;
  width: 230rpx;
  white-space: nowrap;
  overflow: hidden;
}

.mode .item .line {
  border-top: dashed 1rpx #D6D6D6;
  height: 0rpx;
}

.mode .item .price {
  font-size: 32rpx;
  color: red;
}

.progress {
  width: 30%;
  background-color: #EFEFEF;
  border-radius: 8rpx;
  margin: 30rpx auto;
  height: 12rpx;
  overflow: hidden;
  position: relative;
}

.progress-marker {
  width: 20%;
  background: var(--main-color, #6da773fd);
  height: 12rpx;
  border-radius: 8rpx;
  position: absolute;
  left: 0;
}

.popup {
  width: 539rpx;
  height: 392rpx;
  background: #FFFFFF;
  border-radius: 17rpx 17rpx 17rpx 17rpx;
  background: linear-gradient(180deg, #C9FFD7 0%, #FFFFFF 100%);
  margin: 0 auto;
  margin-top: 65%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.popup .title {
  width: 147rpx;
  height: 51rpx;
  font-weight: 500;
  font-size: 37rpx;
  color: #000000;
  text-align: center;
  margin-top: 33rpx;
}

.popup .sub-title {
  width: 174rpx;
  height: 24rpx;
  font-weight: 400;
  font-size: 17rpx;
  color: #817F7F;
  line-height: 0rpx;
  text-align: center;
  margin-top: 13rpx;
  margin-bottom: 20rpx;
}

.popup .btn {
  width: 399rpx;
  height: 82rpx;
  background: var(--main-color, #6da773fd);
  border-radius: 52rpx 52rpx 52rpx 52rpx;
  font-weight: 400;
  font-size: 31rpx;
  color: #FFFFFF;
  display: flex;
  align-items: center;
  margin-bottom: 27rpx;
}

.popup image {
  width: 44rpx;
  height: 44rpx;
  margin-left: 63rpx;
  margin-right: 34rpx;
}

.dialog {
  padding: 30rpx;
  background: #fff;
  border-radius: 17rpx;
}

.dialog-title {
  font-size: 36rpx;
  font-weight: 600;
  text-align: center;
  margin-bottom: 30rpx;
}

.dialog .item {
  display: flex;
  font-size: 28rpx;
  line-height: 60rpx;
}

.dialog .item label {
  color: #666;
}

.dialog .pay {
  width: 80%;
  display: flex;
  align-items: left;
  justify-content: space-between;
}

.dialog .item .note {
  word-wrap: break-word;
  font-size: 24rpx;
}

.dialog-btns {
  display: flex;
  justify-content: space-around;
  margin-top: 30rpx;
}

.dialog-btns .btn {
  width: 200rpx;
  height: 70rpx;
  line-height: 70rpx;
  text-align: center;
  border-radius: 35rpx;
  background: #BCBCBC;
  color: #fff;
}

.dialog-btns .btn.active {
  background: var(--main-color, #6da773fd);
}

.line .coupon {
  display: flex;
  justify-content: flex-end;
}

.line .coupon .price-coupon {
  color: red;
}

.ac-control__modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease;
}

.ac-control__modal--show {
  opacity: 1;
  visibility: visible;
}

.ac-control__modal-content {
  background-color: #ffffff;
  border-radius: 16rpx;
  box-shadow: 0 4rpx 24rpx rgba(100, 101, 102, 0.12);
  width: 90%;
  max-width: 750rpx;
}

.ac-control__modal-header {
  display: flex;
  justify-content: space-around;
  align-items: center;
  border-bottom: 2rpx solid #f2f3f5;
}

.ac-control__modal-title {
  font-size: 36rpx;
  font-weight: bold;
  flex: 1;
  text-align: center;
}

.ac-control__modal-close {
  font-size: 80rpx;
  color: #999;
  padding: 0 16rpx;
}

.ac-control__modal-body {
  padding: 40rpx;
}

.ac-control__temperature-control {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20rpx;
  background-color: #f2f3f5;
  border-radius: 16rpx;
  padding: 10rpx;
}

.ac-control__temperature-display {
  flex-grow: 1;
  height: 100rpx;
  margin: 10 20rpx;
  background-color: #f2f3f5;
  border-radius: 40rpx;
  overflow: hidden;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.ac-control__temperature-text {
  position: absolute;
  width: 100%;
  text-align: center;
  line-height: 80rpx;
  font-size: 36rpx;
  font-weight: bold;
  color: #108ee9;
}

.ac-control__control-button {
  width: 200rpx;
  height: 100rpx;
  text-align: center;
  align-items: center;
  line-height: 200rpx;
  background-color: #f2f3f5;
  color: #108ee9;
  font-size: 75rpx;
  border: none;
  display: flex;
  justify-content: center;
  padding: 30rpx;
  margin: 0rpx;
}

.ac-control__mode-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20rpx;
  margin-bottom: 40rpx;
}

.ac-control__mode-button {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #f2f3f5;
  border-radius: 16rpx;
  padding: 20rpx;
  font-size: 24rpx;
}

.ac-control__mode-button--active {
  background-color: #108ee9;
  color: white;
}

.ac-control__mode-icon {
  font-size: 48rpx;
  margin-bottom: 10rpx;
}

.ac-control__swing-row {
  display: flex;
  justify-content: space-around;
  margin-bottom: 30rpx;
}

.ac-control__swing-button {
  width: calc(50% - 20rpx);
  height: 100rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #f2f3f5;
  border-radius: 16rpx;
  padding: 20rpx;
  margin: 10rpx;
  font-size: 24rpx;
}

.ac-control__swing-button--active {
  background-color: #108ee9;
  color: white;
}

.ac-control__fan-control {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20rpx;
  background-color: #f2f3f5;
  border-radius: 16rpx;
  padding: 10rpx;
}

.ac-control__fan-display {
  flex-grow: 1;
  text-align: center;
}

.ac-control__fan-display .text {
  width: 100%;
  text-align: center;
  line-height: 80rpx;
  font-size: 36rpx;
  font-weight: bold;
  color: #108ee9;
}

.ac-control__fan-level {
  display: flex;
  justify-content: center;
  margin-top: 10rpx;
}

.ac-control__fan-dot {
  width: 16rpx;
  height: 16rpx;
  border-radius: 50%;
  background-color: #d8d8d8;
  margin: 0 6rpx;
}

.ac-control__fan-dot--active {
  background-color: #108ee9;
}

.ac-control__power-button {
  width: 100%;
  height: 88rpx;
  border: none;
  border-radius: 44rpx;
  font-size: 32rpx;
  font-weight: bold;
  margin: 00rpx 10rpx 0rpx;
}

.ac-control__power-button--on {
  background-color: #ee0a24;
  color: white;
}

.ac-control__power-button--off {
  background-color: #bbb9b9;
  color: white;
}

.ac-control__modal-content .btn {
  display: flex;
  justify-content: center;
  margin-top: 10rpx;
}

.wifiDialog {
  width: 600rpx;
}

.wifiDialog .dialog {
  margin: auto;
  padding: 30rpx 30rpx;
  text-align: center;
}

.wifiDialog .dialog .item {
  font-size: 32rpx;
  line-height: 60rpx;
}

.wifiDialog .dialog .item label {
  color: #666;
}

.wifiDialog .dialog .info {
  margin-top: 10rpx;
  font-size: 22rpx;
}

.wifiDialog .dialog .btn {
  display: flex;
  justify-content: space-around;
  margin-top: 20rpx;
}

.wifiDialog .dialog .btn .copy {
  width: 200rpx;
  height: 60rpx;
  line-height: 60rpx;
  font-size: 26rpx;
  background-color: rgb(204, 204, 204);
}

.wifiDialog .dialog .btn .connect {
  width: 200rpx;
  height: 60rpx;
  line-height: 60rpx;
  font-size: 26rpx;
  background-color: var(--main-color, #6da773fd);
  color: #fff;
}

.vipPrice {
  display: flex;
  justify-content: center;
}

.vipPrice .priceInfo {
  color: var(--main-color, #6da773fd);
  margin: 0rpx 20rpx;
  margin-bottom: 10rpx;
  align-items: center;
  text-align: center;
}

.vipPrice .priceInfo .price {
  color: red;
}

.vipPrice .priceText {
  font-size: 20rpx;
  color: #000;
}

.status-tag {
  position: absolute;
  right: 100rpx;
  bottom: 30rpx;
}

.modal-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 999;
  background: linear-gradient(to bottom right, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.65));
  display: flex;
  justify-content: center;
  align-items: center;
  backdrop-filter: blur(10px);
}

.modal-content {
  width: 92%;
  background: linear-gradient(180deg, #ffffff 0%, #f3f3f5 100%);
  border-radius: 32rpx;
  box-shadow: 0 12rpx 30rpx rgba(0, 0, 0, 0.2);
  padding: 10rpx 10rpx 20rpx;
  text-align: center;
  animation: fadeIn 0.3s ease-in-out;
}

.modal-title {
  font-size: 38rpx;
  font-weight: 600;
  color: #fff;
  background-color: var(--main-color, #6da773fd);
  border-radius: 15rpx;
  line-height: 38rpx;
  margin-bottom: 40rpx;
  padding: 20rpx 0rpx;
  text-align: center;
}

.qrcode-canvas-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  padding: 5rpx;
}

.qrcode-canvas {
  height: 200px;
  width: 200px;
}

.guide-image {
  margin: 0 auto;
  display: block;
}

.close-btn {
  width: 70%;
  height: 88rpx;
  line-height: 88rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: white;
  background-color: var(--main-color, #6da773fd);
  border: none;
  border-radius: 44rpx;
  box-shadow: 0 8rpx 18rpx var(--main-color, #6da773fd);
  margin: 0 auto;
  display: block;
}

.overlay-mask {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  z-index: 999;
}
</style>
