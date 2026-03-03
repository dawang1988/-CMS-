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
        <view v-if="OrderInfodata.end_time" class="total border-primary">共{{ OrderInfodata.duration ? (OrderInfodata.duration / 60).toFixed(1) : 0 }}小时</view>
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
        <view class="info-line">
          <text class="bold">下单时间：</text>
          <text>{{ OrderInfodata.create_time }}</text>
        </view>
        <view class="info-line" @tap="copyOrderNo" :data-order="OrderInfodata.order_no">
          <text class="bold">订单编号：</text>
          <text>{{ OrderInfodata.order_no }}</text>
          <view class="copy">复制</view>
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
          <text>¥ {{ OrderInfodata.price }}</text>
        </view>
        <view class="info-line">
          <text class="bold">订单押金：</text>
          <text>¥ {{ OrderInfodata.deposit }}</text>
        </view>
      </view>
    </view>
    
    <view class="btns">
      <button class="bg-primary1" @tap="backHome">返回首页</button>
      <button class="bg-primary" @tap="renewClick">订单续费</button>
    </view>

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

    <!-- 续费弹窗 -->
    <u-popup :show="renewShow" mode="bottom" :custom-style="{ height: '64%', borderRadius: '17rpx 17rpx 0rpx 0rpx', background: 'linear-gradient(180deg, #C9FFD7 0%, #FFFFFF 20%, #FFFFFF 100%)' }" @close="renewCancel">
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
                    余额：
                    {{ balance }}元
                  </view>
                  <view>
                    赠送：
                    {{ giftBalance }}元
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
  </view>
</template>

<script>
import http from '@/utils/http-legacy'
import dayjs from 'dayjs'

export default {
  data() {
    return {
      isLogin: false,
      store_id: '',
      room_id: '',
      OrderInfodata: {},
      newTime: '',
      addTime: 0,
      totalPay: 0,
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
      pay_type: 1,
      payTypes: [],
      renewShow: false,
      pkgList: [],
      pkg_id: '',
      renewOrderNo: ''
    }
  },

  onLoad(options) {
    const app = getApp()
    this.isLogin = app.globalData.isLogin || false
    
    let storeId = ''
    let roomId = ''
    if (options.store_id) {
      storeId = options.store_id
    }
    if (options.storeId) {
      storeId = options.storeId
    }
    if (options.room_id) {
      roomId = options.room_id
    }
    if (options.roomId) {
      roomId = options.roomId
    }
    
    const query = uni.getEnterOptionsSync().query
    if (query) {
      if (query.store_id) {
        storeId = query.store_id
      }
      if (query.storeId) {
        storeId = query.storeId
      }
      if (query.room_id) {
        roomId = query.room_id
      }
      if (query.roomId) {
        roomId = query.roomId
      }
    }
    
    this.store_id = storeId
    this.room_id = roomId
  },

  onShow() {
    const app = getApp()
    this.isLogin = app.globalData.isLogin || false
    
    if (!this.isLogin) {
      uni.navigateTo({
        url: '/pages/user/login',
      })
    } else {
      this.getOrderInfo()
      this.getCouponListData()
    }
  },

  methods: {
    splitTime(timeStr) {
      if (!timeStr) return ['', '']
      const parts = timeStr.split(' ')
      if (parts.length < 2) return ['', '']
      const time = parts[1].split(':')
      return [time[0] || '', time[1] || '']
    },
    
    splitKongge(timeStr) {
      if (!timeStr) return ['']
      return timeStr.split(' ')
    },

    getOrderInfo() {
      const app = getApp()
      http.request(
        "/member/order/getOrderByRoomId/" + this.room_id,
        "1",
        "post",
        {},
        app.globalData.userDatatoken.access_token,
        "获取中...",
        (info) => {
          if (info.code === 0) {
            this.OrderInfodata = info.data
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
                    uni.navigateBack({ delta: 1 })
                  } else {
                    uni.reLaunch({ url: '/pages/door/index' })
                  }
                }
              }
            })
          }
        },
        () => {}
      )
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

    backHome() {
      uni.reLaunch({
        url: '/pages/door/index',
      })
    },

    renewClick() {
      const OrderInfodata = this.OrderInfodata
      if (OrderInfodata.status == 3 || OrderInfodata.status == 2) {
        uni.showToast({
          title: "订单已结束！",
          icon: "error",
        })
      } else {
        const app = getApp()
        if (app.globalData.isLogin) {
          this.renewShow = true
          this.coupon_id = ''
          this.submit_couponInfo = {}
          this.payTypes = [
            { name: "微信", value: 1, checked: true },
            { checked: false, name: "余额", value: 2 },
          ]
          this.getPkgList()
        } else {
          this.renewShow = true
          this.payTypes = [{ name: "微信", value: 1, checked: true }]
        }
      }
    },

    onClickShow(e) {
      const { index } = e.currentTarget.dataset
      this.show = true
      this.popupIndex = +index
    },

    onClickHide() {
      this.show = false
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

    copyOrderNo(e) {
      uni.setClipboardData({
        data: e.currentTarget.dataset.order,
        success: () => {
          uni.showToast({ title: "订单号已复制" })
        },
      })
    },

    goTencentMap() {
      const store = this.OrderInfodata
      uni.openLocation({
        latitude: store.latitude,
        longitude: store.longitude,
        name: store.store_name,
        address: store.address,
        scale: 28
      })
    },

    goGuide() {
      uni.navigateTo({
        url: `/pagesA/guide/index?storeId=${this.store_id}`,
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
              this.giftBalance = info.data.gift_balance
              this.balance = info.data.balance
            }
          },
          () => {}
        )
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
      let addTime = parseInt(this.addTime) + 1
      if (addTime > 8) return
      this.timeChange(addTime)
    },

    onRenewMinus() {
      let addTime = parseInt(this.addTime) - 1
      if (addTime < 0) return
      this.timeChange(addTime)
    },

    renewCancel() {
      this.renewShow = false
      this.addTime = 0
      this.newTime = ''
      this.renewOrderNo = ''
      this.totalPay = 0
      this.pay_type = 1
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
            store_id: this.store_id,
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
              })
            }
          },
          () => {}
        )
      }
    },

    payMent(pay) {
      uni.requestPayment({
        'timeStamp': pay.data.timeStamp,
        'nonceStr': pay.data.nonceStr,
        'package': pay.data.pkg,
        'signType': pay.data.signType,
        'paySign': pay.data.paySign,
        'success': (res) => {
          // 支付成功
        },
        'fail': (res) => {
          uni.showToast({
            title: '支付失败!',
            icon: 'error'
          })
        },
        'complete': (res) => {
          // 支付完成
        }
      })
    },

    renewConfirm() {
      if (!this.newTime) {
        uni.showToast({
          title: '请选择增加时间',
          icon: "none"
        })
        return false
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
            "pkg_id": this.pkg_id
          },
          app.globalData.userDatatoken.access_token,
          "",
          (info) => {
            if (info.code == 0) {
              uni.showToast({
                title: '续时成功',
              })
              this.getOrderInfo()
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

    cancelOrder() {
      uni.showToast({
        title: '请在订单详情页申请退款',
        icon: 'none'
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #F7F7F7;
  padding-bottom: 120rpx;
}

.top-container {
  background: #fff;
  margin: 20rpx;
  border-radius: 17rpx;
  padding: 30rpx;
}

.timeBox {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 0;
  border-bottom: 2rpx solid #E3E3E3;
}

.timeBox .time {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.timeBox .hour {
  display: flex;
  align-items: center;
  font-size: 40rpx;
  font-weight: 600;
  color: #333;
}

.timeBox .hour image {
  width: 40rpx;
  height: 40rpx;
  margin: 0 10rpx;
}

.timeBox .date {
  font-size: 24rpx;
  color: #999;
  margin-top: 10rpx;
}

.timeBox .total {
  padding: 10rpx 20rpx;
  background: var(--main-color, #6da773fd);
  color: #fff;
  border-radius: 10rpx;
  font-size: 28rpx;
}

.timeBox .no-end {
  font-size: 32rpx;
  color: #666;
  text-align: center;
}

.orderInfo {
  padding: 20rpx 0;
}

.orderInfo .top {
  display: flex;
  align-items: center;
  margin-bottom: 20rpx;
}

.orderInfo .tag {
  background: var(--main-color, #6da773fd);
  color: #fff;
  padding: 5rpx 15rpx;
  border-radius: 8rpx;
  font-size: 24rpx;
  margin-right: 15rpx;
}

.orderInfo .name {
  font-size: 32rpx;
  font-weight: 600;
  margin: 15rpx 0;
}

.orderInfo .address {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}

.orderInfo .address .left {
  flex: 1;
  font-size: 26rpx;
  color: #666;
}

.orderInfo .address .right {
  display: flex;
  align-items: center;
}

.orderInfo .address .item {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 24rpx;
  color: var(--main-color, #6da773fd);
  padding: 0 15rpx;
}

.orderInfo .address .item image {
  width: 40rpx;
  height: 40rpx;
  margin-bottom: 5rpx;
}

.orderInfo .address .line {
  width: 1rpx;
  height: 40rpx;
  background: #ddd;
}

.orderInfo .info-line {
  display: flex;
  align-items: center;
  padding: 10rpx 0;
  font-size: 28rpx;
  color: #333;
}

.orderInfo .info-line.display-space {
  justify-content: space-between;
}

.orderInfo .bold {
  font-weight: 600;
}

.orderInfo .copy {
  margin-left: 20rpx;
  padding: 5rpx 15rpx;
  background: var(--main-color, #6da773fd);
  color: #fff;
  border-radius: 8rpx;
  font-size: 24rpx;
}

.btns {
  display: flex;
  justify-content: space-between;
  padding: 30rpx 20rpx;
}

.btns button {
  flex: 1;
  height: 80rpx;
  line-height: 80rpx;
  border-radius: 10rpx;
  font-size: 30rpx;
  margin: 0 10rpx;
}

.bg-primary1 {
  background: #fff;
  color: var(--main-color, #6da773fd);
  border: 2rpx solid var(--main-color, #6da773fd);
}

.bg-primary {
  background: var(--main-color, #6da773fd);
  color: #fff;
}

.popup {
  background: #fff;
  border-radius: 20rpx;
  padding: 40rpx;
  margin: 200rpx 40rpx;
}

.popup .title {
  font-size: 36rpx;
  font-weight: 600;
  text-align: center;
  margin-bottom: 20rpx;
}

.popup .sub-title {
  font-size: 26rpx;
  color: #999;
  text-align: center;
  margin-bottom: 30rpx;
}

.popup .btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 25rpx;
  background: #f5f5f5;
  border-radius: 10rpx;
  margin: 15rpx 0;
  font-size: 28rpx;
}

.popup .btn image {
  width: 40rpx;
  height: 40rpx;
  margin-right: 15rpx;
}

.overlay-mask {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  z-index: 999;
}

.renewBox {
  padding: 30rpx;
}

.renewBox .title {
  font-size: 36rpx;
  font-weight: 600;
  text-align: center;
  margin-bottom: 30rpx;
}

.renewBox .line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 0;
  font-size: 28rpx;
}

.renewBox .bold {
  font-weight: 600;
}

.renewBox .time {
  display: flex;
  align-items: center;
}

.renewBox .time image {
  width: 50rpx;
  height: 50rpx;
  margin: 0 20rpx;
}

.mode-slot {
  display: flex;
  justify-content: space-around;
  margin: 30rpx 0;
}

.mode-slot view {
  flex: 1;
  text-align: center;
  padding: 20rpx;
  background: #f5f5f5;
  margin: 0 10rpx;
  border-radius: 10rpx;
  font-size: 28rpx;
}

.mode-slot view.active {
  background: var(--main-color, #6da773fd);
  color: #fff;
}

.coupon {
  padding: 15rpx 20rpx;
  background: #fff5e6;
  border-radius: 10rpx;
  color: #ff9500;
  font-size: 26rpx;
}

.mode {
  white-space: nowrap;
  margin: 20rpx 0;
}

.mode-container {
  display: inline-flex;
}

.mode-container .item {
  display: inline-block;
  width: 300rpx;
  padding: 20rpx;
  background: #f5f5f5;
  border-radius: 10rpx;
  margin-right: 20rpx;
}

.mode-container .item.active {
  background: #e6f7ff;
  border: 2rpx solid var(--main-color, #6da773fd);
}

.mode-container .top {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15rpx;
}

.mode-container .pkg_name {
  font-size: 28rpx;
  font-weight: 600;
}

.mode-container .price {
  font-size: 32rpx;
  color: #ff4757;
  font-weight: 600;
}

.mode-container .line {
  height: 1rpx;
  background: #ddd;
  margin: 15rpx 0;
}

.mode-container .bottom {
  font-size: 24rpx;
  color: #666;
  margin: 5rpx 0;
}

.progress {
  height: 6rpx;
  background: #f0f0f0;
  border-radius: 3rpx;
  margin: 20rpx 0;
  position: relative;
}

.progress-marker {
  position: absolute;
  height: 100%;
  background: var(--main-color, #6da773fd);
  border-radius: 3rpx;
  transition: left 0.3s;
}

.divide-line {
  height: 1rpx;
  background: #ddd;
  margin: 30rpx 0;
}

.orderPay .pay {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx;
  background: #f5f5f5;
  border-radius: 10rpx;
  margin: 15rpx 0;
}

.orderPay .left,
.orderPay .right-item {
  display: flex;
  align-items: center;
  flex: 1;
}

.orderPay .item {
  display: flex;
  align-items: center;
}

.orderPay .item image {
  width: 40rpx;
  height: 40rpx;
  margin-right: 15rpx;
}

.orderPay .desc {
  margin-left: 15rpx;
  font-size: 24rpx;
  color: #666;
}

.orderPay .selector {
  width: 40rpx;
  height: 40rpx;
  border: 2rpx solid #ddd;
  border-radius: 50%;
  margin-left: auto;
}

.orderPay .selector.active {
  border-color: var(--main-color, #6da773fd);
  background: var(--main-color, #6da773fd);
  position: relative;
}

.orderPay .selector.active::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 20rpx;
  height: 20rpx;
  background: #fff;
  border-radius: 50%;
}

.orderPay .line:last-child {
  display: flex;
  justify-content: space-around;
  margin-top: 30rpx;
}

.orderPay .btn {
  flex: 1;
  height: 80rpx;
  line-height: 80rpx;
  text-align: center;
  border-radius: 10rpx;
  background: #f5f5f5;
  margin: 0 10rpx;
  font-size: 30rpx;
}

.orderPay .btn.active {
  background: var(--main-color, #6da773fd);
  color: #fff;
}
</style>
