<template>
  <view class="container" :style="themeStyle">
    <!-- 门店信息卡片 -->
    <view class="info-card">
      <view class="top">
        <view class="left">
          <view class="info">
            <text class="iconfont icon-store"></text>
            <view class="name">{{ doorinfodata.store_name }}</view>
          </view>
        </view>
        <view class="change-btn" @tap="showSelect">
          <text>切换门店</text>
        </view>
      </view>
      <view class="bottom">
        <text class="location">{{ doorinfodata.address }}</text>
        <view class="item" @tap="onClickShow(0)">
          <image src="/static/icon/navigation.png" mode="aspectFit" />
          <text>导航</text>
        </view>
        <view class="line"></view>
        <view class="item" @tap="onClickShow(1)">
          <image src="/static/icon/phone.png" mode="aspectFit" />
          <text>电话</text>
        </view>
      </view>
    </view>

    <!-- Logo -->
    <view class="logo">
      <image src="/static/img/tuangou.png" mode="aspectFit" />
    </view>

    <!-- 团购券输入 -->
    <view class="code-box">
      <view>
        <image src="/static/icon/groupBuy.png" mode="aspectFit" />
        <input 
          placeholder="输入/粘贴团购券码" 
          v-model="groupPayNo" 
          placeholder-style="text-align:center;" 
        />
        <image src="/static/icon/scan.png" mode="aspectFit" @tap="scanCode" />
      </view>
    </view>

    <!-- 券信息 -->
    <view class="voucher-info" v-if="voucherInfo">
      <text>{{ voucherInfo.title }}</text>
    </view>

    <!-- 房间选择 -->
    <view class="control-box">
      <view class="select-card">
        <view 
          v-for="(item, index) in doorlistArr" 
          :key="index"
          :class="['item', { 'active': roomIndex === index }]"
          @tap="selectRoom(index, item)"
        >
          <view class="top">
            <view class="tag">{{ getRoomTypeName(item.type) }}</view>
            <view>{{ item.room_name }}</view>
          </view>
          <view class="bottom">
            <view :class="[item.status === 0 ? 'gray' : item.status === 1 ? 'primary' : item.status === 2 ? 'blue' : item.status === 3 ? 'red' : 'orange']">{{ getStatusText(item.status) }}</view>
            <view 
              class="more" 
              v-if="item.orderTimeList && item.orderTimeList.length" 
              @tap.stop="onShowReserve(item.orderTimeList)"
            >
              更多
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 确认按钮 -->
    <button class="confirm-btn" @tap="SubmitOrderInfoData">确认兑换</button>

    <!-- 导航/电话弹窗 -->
    <view class="overlay" v-if="show" @tap="onClickHide">
      <view class="popup navigation" v-if="popupIndex === 0" @tap.stop>
        <view class="title">导航到店</view>
        <view class="sub-title">可选择您所需要的服务</view>
        <view class="btn" @tap="goTencentMap">
          <image src="/static/icon/nav.png" mode="aspectFit" />
          地图导航
        </view>
        <view class="btn" @tap="goGuide">
          <image src="/static/icon/guide.png" mode="aspectFit" />
          位置指引
        </view>
      </view>
      <view class="popup service" v-if="popupIndex === 1" @tap.stop>
        <view class="title">联系客服</view>
        <view class="sub-title">可选择您所需要的服务</view>
        <view class="btn" @tap="call">
          <image src="/static/icon/phone-call.png" mode="aspectFit" />
          {{ doorinfodata.phone }}
        </view>
      </view>
    </view>

    <!-- 门店选择弹窗 -->
    <uni-popup ref="storePopup" type="bottom">
      <view class="store-select">
        <view class="store-title">选择门店</view>
        <scroll-view scroll-y class="store-list">
          <view 
            v-for="(item, index) in storeList" 
            :key="item.id"
            class="store-item"
            @tap="onSelect(item)"
          >
            <view class="store-name">{{ item.name }}</view>
            <view class="store-address">{{ item.address }}</view>
          </view>
        </scroll-view>
      </view>
    </uni-popup>

    <!-- 预定时间列表弹窗 -->
    <uni-popup ref="reservePopup" type="center">
      <view class="reserve-box">
        <view class="title">预定时间</view>
        <view class="time-line" v-for="(item, index) in orderTimeList" :key="index">
          <view class="dot"></view>
          <view class="time-tag">
            <text>{{ item }} </text>
            <text>已预订</text>
          </view>
        </view>
        <button @tap="onHideReserve">知道了</button>
      </view>
    </uni-popup>
  </view>
</template>

<script>
import http from '@/utils/http'
import dayjs from 'dayjs'

export default {
  data() {
    return {
      store_id: '',
      group_pay_no: '',
      ticketName: '',
      doorinfodata: {},
      voucherInfo: null,
      storeList: [],
      doorlistArr: [],
      roomIndex: -1,
      lat: '',
      lon: '',
      show: false,
      popupIndex: 0,
      orderTimeList: []
    }
  },

  onLoad(options) {
    this.store_id = options.store_id || ''
    this.groupPayNo = options.ticketNo || ''
    this.ticketName = options.ticketName || ''

    const token = uni.getStorageSync('token')
    if (!token) {
      uni.navigateTo({
        url: '/pages/user/login'
      })
      return
    }
  },

  onShow() {
    this.getLocation().then(() => {
      if (this.store_id) {
        this.getDoorInfoData(this.store_id)
      }
    }).catch(() => {
      if (this.store_id) {
        this.getDoorInfoData(this.store_id)
      }
    })
    
    this.getListData()
    if (this.store_id) {
      this.getDoorListdata(this.store_id)
    }
  },

  methods: {
    // 获取位置
    getLocation() {
      return new Promise((resolve, reject) => {
        uni.getLocation({
          type: 'gcj02',
          success: (res) => {
            this.lat = res.latitude
            this.lon = res.longitude
            resolve()
          },
          fail: () => {
            reject()
          }
        })
      })
    },

    // 获取门店信息
    async getDoorInfoData(storeId) {
      try {
        const res = await http.request({
          url: `/member/index/getStoreInfo/${storeId}`,
          method: 'GET',
          data: {
            lat: this.lat,
            lon: this.lon
          }
        })

        if (res.code === 0) {
          this.doorinfodata = res.data
        }
      } catch (e) {
      }
    },

    // 获取门店列表
    async getListData() {
      try {
        const res = await http.request({
          url: '/member/index/getStoreList',
          method: 'POST',
          data: {
            pageNo: 1,
            pageSize: 100,
            name: '',
            lat: this.lat,
            lon: this.lon
          }
        })

        if (res.code === 0) {
          this.storeList = res.data.list.map(el => ({
            name: el.store_name,
            id: el.store_id,
            ...el
          }))

          if (!this.doorinfodata.store_id && this.storeList.length > 0) {
            this.doorinfodata = this.storeList[0]
            this.store_id = this.storeList[0].id
          }
        }
      } catch (e) {
      }
    },

    // 获取房间列表
    async getDoorListdata(storeId) {
      try {
        const res = await http.request({
          url: '/member/index/getRoomInfoList',
          method: 'POST',
          data: {
            store_id: storeId,
            room_class: -1
          }
        })

        if (res.code === 0) {
          this.doorlistArr = res.data.map(el => {
            // 处理预定时间列表
            if (el.orderTimeList) {
              el.orderTimeList = el.orderTimeList.map(item => 
                this.timeFilter(item.start_time, item.end_time)
              )
            }
            return el
          })
        }
      } catch (e) {
      }
    },

    // 扫码
    scanCode() {
      uni.scanCode({
        success: (res) => {
          this.groupPayNo = res.result
          uni.showToast({
            title: '扫码成功',
            icon: 'success'
          })
        },
        fail: () => {
          uni.showToast({
            title: '扫码失败',
            icon: 'none'
          })
        }
      })
    },

    // 选择房间
    selectRoom(index, item) {
      if (item.status === 0) {
        uni.showToast({
          title: '场地禁用',
          icon: 'none'
        })
        return
      }
      this.roomIndex = index
    },

    // 提交订单
    SubmitOrderInfoData() {
      if (this.roomIndex < 0) {
        uni.showToast({
          title: '未选择房间',
          icon: 'none'
        })
        return
      }

      const room = this.doorlistArr[this.roomIndex]
      uni.navigateTo({
        url: `/pages/order/submit?roomId=${room.room_id}&goPage=1&storeId=${this.store_id}&groupPayNo=${this.groupPayNo}`
      })
    },

    // 显示门店选择
    showSelect() {
      this.$refs.storePopup.open()
    },

    // 选择门店
    onSelect(item) {
      this.doorinfodata = item
      this.store_id = item.id
      this.getDoorListdata(item.id)
      this.$refs.storePopup.close()
    },

    // 显示弹窗
    onClickShow(index) {
      this.popupIndex = index
      this.show = true
    },

    // 隐藏弹窗
    onClickHide() {
      this.show = false
    },

    // 地图导航
    goTencentMap() {
      const store = this.doorinfodata
      uni.openLocation({
        latitude: store.latitude,
        longitude: store.longitude,
        name: store.store_name,
        address: store.address,
        scale: 28
      })
    },

    // 位置指引
    goGuide() {
      uni.navigateTo({
        url: `/pagesA/guide/index?storeId=${this.store_id}`
      })
    },

    // 拨打电话
    call() {
      const phone = this.doorinfodata.phone
      if (phone && phone.length === 11) {
        uni.makePhoneCall({
          phoneNumber: phone
        })
      }
    },

    // 复制微信号
    copy() {
      // No wechat field in database - method kept for compatibility
    },

    // 显示预定时间
    onShowReserve(list) {
      this.orderTimeList = list
      this.$refs.reservePopup.open()
    },

    // 隐藏预定时间
    onHideReserve() {
      this.$refs.reservePopup.close()
    },

    // 时间格式化
    timeFilter(startTime, endTime) {
      if (!startTime) return ''
      
      const start = dayjs(startTime)
      if (!endTime) {
        return start.format('MM月DD日HH:mm')
      }
      
      const end = dayjs(endTime)
      return `${start.format('MM月DD日HH:mm')}-${end.format('HH:mm')}`
    },

    // 获取房间类型名称
    getRoomTypeName(type) {
      const typeMap = {
        0: '不限制',
        1: '小包',
        2: '中包',
        3: '大包',
        4: '豪包',
        5: '商务包',
        6: '斯洛克',
        7: '中式黑八',
        8: '美式球桌'
      }
      return typeMap[type] || '不限制'
    },

    // 获取状态文本
    getStatusText(status) {
      const statusMap = {
        0: '禁用',
        1: '空闲',
        2: '使用中',
        3: '维护中',
        4: '待清洁'
      }
      return statusMap[status] || ''
    },

    // 获取状态样式类
    getStatusClass(status) {
      const classMap = {
        0: 'gray',
        1: 'primary',
        2: 'blue',
        3: 'red',
        4: 'orange'
      }
      return classMap[status] || ''
    }
  }
}
</script>

<style lang="scss">
page {
  background: linear-gradient(to bottom, var(--main-color, #5AAB6E) 4%, #F7F7F7 14%);
  padding-bottom: 168rpx;
}
</style>
<style lang="scss" scoped>
.container {
  min-height: 100vh;
}

.info-card {
  background: #fff;
  padding: 27rpx 34rpx;
  width: 629rpx;
  border-radius: 17rpx;
  margin: 26rpx auto;
}

.info-card .top {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20rpx;
}

.info-card .top .left .info {
  display: flex;
  align-items: center;
  padding: 10rpx 0;
}

.info-card .top .left .info .name {
  font-weight: 600;
  font-size: 34rpx;
  color: #000;
  margin-left: 10rpx;
}

.change-btn {
  width: 169rpx;
  height: 47rpx;
  background: var(--main-color, #5AAB6E);
  border-radius: 52rpx;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 22rpx;
  color: #fff;
}

.info-card .bottom {
  display: flex;
  justify-content: space-around;
  align-items: center;
}

.info-card .location {
  flex: 1;
  font-size: 24rpx;
  color: #000;
  line-height: 26rpx;
}

.info-card .bottom .line {
  width: 0rpx;
  height: 34rpx;
  border: 0.5rpx solid #BCBCBC;
}

.info-card .bottom image {
  width: 39rpx;
  height: 39rpx;
}

.info-card .bottom .item {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 21rpx;
  color: #3E3E3E;
  padding: 0 20rpx;
}

.logo {
  display: flex;
  justify-content: center;
  margin-bottom: 46rpx;
}

.logo image {
  width: 260rpx;
  height: 72rpx;
}

.code-box {
  display: flex;
  justify-content: center;
  margin-top: 66rpx;
}

.code-box image {
  width: 32rpx;
  height: 32rpx;
}

.code-box view {
  width: 470rpx;
  border-bottom: 1rpx solid #E0E0E0;
  padding-bottom: 22rpx;
  display: flex;
  justify-content: space-around;
  align-items: center;
}

.code-box input {
  flex: 1;
  text-align: center;
}

.voucher-info {
  width: 650rpx;
  height: 65rpx;
  background: var(--main-color, #5AAB6E);
  border-radius: 17rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14rpx 24rpx;
  margin: 26rpx auto;
  font-weight: bold;
  font-size: 31rpx;
  color: #FFFFFF;
}

.control-box {
  padding: 20rpx;
}

.select-card {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  margin-top: 44rpx;
}

.select-card .item {
  width: 43.2%;
  font-size: 26rpx;
  background: #fff;
  border-radius: 17rpx;
  padding: 16rpx 18rpx;
  margin-bottom: 16rpx;
}

.select-card .item .top {
  display: flex;
  align-items: center;
}

.select-card .item .bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10rpx;
}

.select-card .item .bottom .more {
  font-size: 21rpx;
  color: #817F7F;
}

.select-card .item .bottom .primary {
  color: var(--main-color, #5AAB6E);
}

.select-card .item .bottom .orange {
  color: #ED9823;
}

.select-card .item .bottom .red {
  color: #F73F4C;
}

.select-card .item .bottom .blue {
  color: #2398ED;
}

.select-card .item .bottom .gray {
  color: #807E7E;
}

.select-card .item .tag {
  padding: 6rpx 12rpx;
  background: var(--main-color, #5AAB6E);
  border-radius: 9rpx;
  font-weight: bold;
  font-size: 23rpx;
  color: #FFFFFF;
  margin-right: 22rpx;
}

.select-card .item.active {
  color: #FFFFFF;
  background: var(--main-color, #5AAB6E);
}

.select-card .item.active .bottom view {
  color: #fff !important;
}

.select-card .item.active .tag {
  background: #fff;
  color: var(--main-color, #5AAB6E);
}

.confirm-btn {
  width: 298rpx;
  height: 83rpx;
  line-height: 83rpx;
  background: var(--main-color, #5AAB6E);
  border-radius: 52rpx;
  font-size: 31rpx;
  color: #FFFFFF;
  margin: 0 auto;
  display: block;
}

.overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.popup {
  width: 539rpx;
  background: linear-gradient(180deg, #C9FFD7 0%, #FFFFFF 100%);
  border-radius: 17rpx;
  padding: 33rpx 40rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.popup .title {
  font-weight: 600;
  font-size: 34rpx;
  color: #000;
  margin-bottom: 13rpx;
}

.popup .sub-title {
  font-size: 17rpx;
  color: #817F7F;
  margin-bottom: 20rpx;
}

.popup .btn {
  width: 399rpx;
  height: 100rpx;
  background: var(--main-color, #5AAB6E);
  border-radius: 52rpx;
  font-size: 31rpx;
  color: #FFFFFF;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 27rpx;
}

.popup image {
  width: 44rpx;
  height: 44rpx;
  margin-right: 34rpx;
}

.store-select {
  background: #fff;
  border-radius: 20rpx 20rpx 0 0;
  padding: 40rpx;
  max-height: 60vh;
}

.store-title {
  font-size: 32rpx;
  font-weight: bold;
  margin-bottom: 30rpx;
  text-align: center;
}

.store-list {
  max-height: 50vh;
}

.store-item {
  padding: 30rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}

.store-name {
  font-size: 30rpx;
  color: #333;
  margin-bottom: 10rpx;
}

.store-address {
  font-size: 24rpx;
  color: #999;
}

.reserve-box {
  width: 539rpx;
  background: linear-gradient(180deg, #FFECC7 0%, #FFFFFF 16%, #FFFFFF 100%);
  border-radius: 17rpx;
  padding: 34rpx 40rpx;
}

.reserve-box .title {
  font-size: 37rpx;
  color: #000;
  text-align: center;
  margin-bottom: 58rpx;
  font-weight: bold;
}

.reserve-box .time-line {
  display: flex;
  align-items: center;
  margin-bottom: 34rpx;
}

.reserve-box .time-line .dot {
  width: 12rpx;
  height: 12rpx;
  background: #FFA313;
  border-radius: 50%;
  position: relative;
  z-index: 9;
}

.reserve-box .time-line .time-tag {
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
  margin-left: 20rpx;
}

.reserve-box button {
  width: 208rpx;
  height: 59rpx;
  line-height: 59rpx;
  background: var(--main-color, #5AAB6E);
  border-radius: 52rpx;
  font-size: 26rpx;
  color: #FFFFFF;
  margin: 22rpx auto 0;
  display: block;
}
</style>
