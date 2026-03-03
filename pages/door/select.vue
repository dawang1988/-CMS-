<template>
  <view class="page" :style="themeStyle">
    <!-- 门店下拉选择 -->
    <view class="tabs">
      <picker 
        mode="selector" 
        :range="stores" 
        range-key="text" 
        :value="storeIndex"
        @change="storeDropdown"
      >
        <view class="picker-view">
          <text>{{ currentStoreName }}</text>
          <text class="arrow">▼</text>
        </view>
      </picker>
    </view>

    <!-- 房间列表 -->
    <view class="container">
      <view class="lists">
        <view 
          class="item" 
          v-for="(item, index) in doorlistArr" 
          :key="index"
        >
          <!-- 房间顶部信息 -->
          <view class="top">
            <view class="left">
              <image 
                class="img" 
                :src="getFirstImage(item.imageUrls)" 
                mode="aspectFill"
              ></image>
              <!-- 房间状态标签 -->
              <!-- 数据库定义：0=停用, 1=空闲, 2=使用中, 3=维护中, 4=待清洁/已预约 -->
              <view 
                class="flag" 
                :class="{
                  'disabled': item.status === 0 || item.status === 3,
                  'undo': item.status === 1,
                  'doing': item.status === 2,
                  'daiqingjie': item.status === 4 && item.is_cleaning,
                  'bukeyong': item.status === 4 && !item.is_cleaning
                }"
              >
                {{ getStatusText(item.status, item) }}
              </view>
            </view>
            <view class="right">
              <view class="info">
                <view class="name">
                  {{ item.room_name }}
                  <text class="type">{{ getRoomType(item.type) }}</text>
                </view>
                <view class="tags">
                  <view 
                    class="tag" 
                    v-for="(label, idx) in getLabels(item.label)" 
                    :key="idx"
                  >
                    {{ label }}
                  </view>
                </view>
              </view>
              <view class="bottom">
                <view class="price color-attention">￥{{ item.price }}/小时</view>
                <!-- 数据库定义：0=停用, 1=空闲, 2=使用中, 3=维护中, 4=待清洁/已预约 -->
                <button 
                  v-if="item.status === 0 || item.status === 3"
                  class="btn disabled"
                  :disabled="true"
                >
                  预约
                </button>
                <button 
                  v-else-if="item.status === 4 && item.is_cleaning"
                  class="btn bg-primary"
                  @tap.stop="confirmDirtyRoom(item)"
                >
                  预约
                </button>
                <button 
                  v-else-if="item.status === 1"
                  class="btn bg-primary"
                  @tap.stop="goDoorSubmit(item)"
                >
                  预约
                </button>
                <button 
                  v-else
                  class="btn disabled"
                  :disabled="true"
                >
                  预约
                </button>
              </view>
            </view>
          </view>

          <!-- 房间时间轴 -->
          <view class="foot">
            <view class="foot-top">
              <view class="labels">
                <view class="label disabled">不可用</view>
                <view class="label">可预约</view>
              </view>
              <!-- 数据库定义：0=停用, 1=空闲, 2=使用中, 3=维护中, 4=待清洁/已预约 -->
              <view class="tip color-attention" v-if="item.status === 2">
                使用中 | {{ item.end_time }}结束
              </view>
              <view class="tip color-attention" v-else-if="item.status === 4 && !item.is_cleaning">
                已预订 | {{ item.start_time ? item.start_time : '' }}开始
              </view>
              <view class="tip" v-else-if="item.status === 4 && item.is_cleaning" style="color: #58b92b;">
                待清洁
              </view>
            </view>
            <view class="times">
              <view 
                class="time" 
                v-for="(hour, idx) in timeHourAllArr[index]" 
                :key="idx"
                :class="{ 'disabled': hour.useflage }"
              >
                {{ hour.hourname }}
              </view>
            </view>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      store_id: 0,
      storeIndex: 0,
      stores: [],
      storeEnvImg: [],
      doorinfodata: {},
      timeselectindex: 0,
      timeDayArr: [],
      timeWeekArr: [],
      doorlistArr: [],
      timeHourArr: [],
      timeHourAllArr: [],
      doorname: '',
      mainColor: '#5AAB6E',
      mode: '' // game=拼场模式
    }
  },

  computed: {
    currentStoreName() {
      if (this.stores.length > 0 && this.storeIndex >= 0) {
        return this.stores[this.storeIndex].text || '请选择门店'
      }
      return '请选择门店'
    }
  },

  onLoad(options) {
    this.loadingtime()
    this.sethour()
    this.mode = options.mode || ''
    
    if (options.store_id) {
      this.store_id = Number(options.store_id)
      // 如果没传 stores 列表，从接口获取
      if (options.stores) {
        try {
          this.stores = JSON.parse(decodeURIComponent(options.stores))
          this.storeIndex = this.stores.findIndex(item => item.value === this.store_id)
          if (this.storeIndex === -1) this.storeIndex = 0
        } catch (e) {}
      } else {
        // 构造一个临时的 stores 列表
        this.stores = [{ text: '当前门店', value: this.store_id }]
        this.storeIndex = 0
      }
      this.getDoorListdata()
      this.getDoorInfodata()
    } else if (options.storeId) {
      this.store_id = Number(options.storeId)
      if (options.stores) {
        try {
          this.stores = JSON.parse(decodeURIComponent(options.stores))
          this.storeIndex = this.stores.findIndex(item => item.value === this.store_id)
          if (this.storeIndex === -1) this.storeIndex = 0
        } catch (e) {}
      } else {
        this.stores = [{ text: '当前门店', value: this.store_id }]
        this.storeIndex = 0
      }
      this.getDoorListdata()
      this.getDoorInfodata()
    }
  },

  methods: {
    // 初始化日期
    loadingtime() {
      const date = new Date()
      const year = date.getFullYear()
      const month = date.getMonth() + 1
      const day = date.getDate()
      const dateString = `${year}-${this.formatNumber(month)}-${this.formatNumber(day)}`
      
      const timeList = this.getDates(5, dateString)
      const dayArr = []
      const weekArr = []
      
      timeList.forEach(item => {
        dayArr.push(`${item.month}.${item.day}`)
        weekArr.push(item.week)
      })
      
      this.timeDayArr = dayArr
      this.timeWeekArr = weekArr
    },

    // 格式化数字
    formatNumber(n) {
      n = n.toString()
      return n[1] ? n : '0' + n
    },

    // 初始化小时数组
    sethour() {
      const timearr = []
      for (let i = 0; i < 24; i++) {
        timearr.push({
          hourname: i,
          useflage: false
        })
      }
      this.timeHourArr = timearr
    },

    // 获取未来几天的日期
    getDates(days, todate) {
      const dateArry = []
      for (let i = 0; i < days; i++) {
        const dateObj = this.dateLater(todate, i)
        dateArry.push(dateObj)
      }
      return dateArry
    },

    // 计算未来日期
    dateLater(dates, later) {
      const dateObj = {}
      const show_day = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
      const date = new Date(dates)
      date.setDate(date.getDate() + later)
      const day = date.getDay()
      
      dateObj.year = date.getFullYear()
      dateObj.month = (date.getMonth() + 1) < 10 ? `0${date.getMonth() + 1}` : (date.getMonth() + 1)
      dateObj.day = date.getDate() < 10 ? `0${date.getDate()}` : date.getDate()
      dateObj.week = show_day[day]
      
      return dateObj
    },

    // 门店切换
    storeDropdown(e) {
      const index = e.detail.value
      this.storeIndex = index
      this.store_id = this.stores[index].value
      this.getDoorListdata()
      this.getDoorInfodata()
    },

    // 获取房间列表
    getDoorListdata() {
      uni.showLoading({ title: '获取中...' })
      
      http.post(`/member/index/getRoomInfoList/${this.store_id}`, {
        store_id: this.store_id
      }).then(res => {
        uni.hideLoading()
        if (res.code === 0) {
          this.doorlistArr = res.data || []
          this.setroomlistHour(0)
        } else {
          uni.showModal({
            content: res.msg || '获取房间列表失败',
            showCancel: false
          })
        }
      }).catch(err => {
        uni.hideLoading()
        uni.showToast({
          title: '获取房间列表失败',
          icon: 'none'
        })
      })
    },

    // 设置房间时间轴
    setroomlistHour(aindex) {
      const templist = []
      
      this.doorlistArr.forEach(room => {
        const disabledSlot = room.disabledTimeSlot || {}
        const keys = Object.keys(disabledSlot).sort()
        const requestvalueArr = keys.map(key => disabledSlot[key])
        
        const listarr1 = requestvalueArr[aindex] || []
        
        if (listarr1.length > 0) {
          // 计算禁用的小时
          const edittimeHourArr = []
          
          listarr1.forEach(atime1 => {
            const astartTime = atime1.start_time
            const aendTime = atime1.end_time
            
            const ahourend1 = aendTime.split(':')
            const ahourstart1 = astartTime.split(':')
            
            const num1 = Number(ahourend1[0])
            const num2 = Number(ahourstart1[0])
            
            const ahourint = num1 - num2
            
            for (let n = 0; n <= ahourint; n++) {
              const acounttime = num2 + n
              edittimeHourArr.push(acounttime)
            }
          })
          
          // 标记禁用时间
          const anewlist = this.timeHourArr.map(hour => ({
            hourname: hour.hourname,
            useflage: edittimeHourArr.includes(hour.hourname)
          }))
          
          templist.push(anewlist)
        } else {
          templist.push(JSON.parse(JSON.stringify(this.timeHourArr)))
        }
      })
      
      this.timeHourAllArr = templist
    },

    // 获取门店信息
    getDoorInfodata() {
      http.get(`/member/index/getStoreInfo/${this.store_id}`).then(res => {
        if (res.code === 0) {
          this.doorinfodata = res.data || {}
          if (res.data.storeEnvImg && res.data.storeEnvImg.length > 0) {
            this.storeEnvImg = res.data.storeEnvImg.split(',')
          }
        } else {
          uni.showModal({
            content: res.msg || '获取门店信息失败',
            showCancel: false
          })
        }
      }).catch(err => {
      })
    },

    // 确认脏房间预订
    confirmDirtyRoom(item) {
      uni.showModal({
        title: '提示',
        content: '您选择的房间暂未清洁，介意请勿预订！如果您已确定该房间卫生整洁，无需打扫可以下单！',
        confirmText: '继续预定',
        success: (res) => {
          if (res.confirm) {
            this.goDoorSubmit(item)
          }
        }
      })
    },

    // 跳转到提交订单页面
    goDoorSubmit(roomInfo) {
      const token = uni.getStorageSync('token')
      if (!token) {
        uni.navigateTo({
          url: '/pages/user/login'
        })
        return
      }
      
      if (this.mode === 'game') {
        // 拼场模式：跳到创建拼场页面
        uni.navigateTo({
          url: `/pages/door/create-game?storeId=${this.store_id}&roomId=${roomInfo.id || roomInfo.room_id}&roomInfo=${encodeURIComponent(JSON.stringify(roomInfo))}`
        })
      } else {
        // 普通模式：跳到提交订单页面
        const daytime = this.timeselectindex >= 0 ? this.timeDayArr[this.timeselectindex] : ''
        const doorname = this.doorinfodata.store_name || ''
        
        uni.navigateTo({
          url: `/pages/order/submit?roomInfo=${encodeURIComponent(JSON.stringify(roomInfo))}&daytime=${daytime}&doorname=${doorname}`
        })
      }
    },

    // 选择日期
    selectTime(index) {
      this.timeselectindex = index
      this.setroomlistHour(index)
    },

    // 获取第一张图片
    getFirstImage(imageUrls) {
      if (!imageUrls) return '/static/logo.png'
      const images = imageUrls.split(',')
      return images[0] || '/static/logo.png'
    },

    // 获取状态文本
    // 数据库定义：0=停用, 1=空闲, 2=使用中, 3=维护中, 4=待清洁/已预约
    getStatusText(status, item) {
      if (status === 4) {
        // 状态4需要通过is_cleaning字段区分
        return item && item.is_cleaning ? '待清洁' : '已预约'
      }
      const statusMap = {
        0: '禁用',
        1: '空闲中',
        2: '使用中',
        3: '维护中'
      }
      return statusMap[status] || '未知'
    },

    // 获取房间类型
    getRoomType(type) {
      const typeMap = {
        0: '（特价包）',
        1: '（小包）',
        2: '（中包）',
        3: '（大包）',
        4: '（豪包）',
        5: '（商务包）',
        6: '（斯洛克）',
        7: '（中式黑八）',
        8: '（美式球桌）'
      }
      return typeMap[type] || ''
    },

    // 获取标签数组
    getLabels(label) {
      if (!label) return []
      return label.split(',').filter(item => item)
    },

    // 拨打电话
    call() {
      if (this.doorinfodata.phone) {
        uni.makePhoneCall({
          phoneNumber: this.doorinfodata.phone
        })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-top: 90rpx;
}

.tabs {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 999;
  background: #c9ebff;
  border-bottom: 1rpx solid #ddd;
  height: 90rpx;
}

.picker-view {
  height: 90rpx;
  line-height: 90rpx;
  padding: 0 30rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 28rpx;
  color: #333;
}

.arrow {
  font-size: 24rpx;
  color: #999;
}

.container {
  padding: 20rpx;
}

.lists {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.item {
  background: #fff;
  border-radius: 20rpx;
  overflow: hidden;
}

.top {
  display: flex;
  padding: 20rpx;
}

.left {
  position: relative;
  margin-right: 20rpx;
  flex-shrink: 0;
}

.img {
  width: 200rpx;
  height: 200rpx;
  border-radius: 10rpx;
  display: block;
}

.flag {
  position: absolute;
  top: 10rpx;
  left: 10rpx;
  padding: 4rpx 12rpx;
  border-radius: 8rpx;
  font-size: 22rpx;
  color: #fff;
}

.flag.disabled {
  background: #999;
}

.flag.undo {
  background: #07c160;
}

.flag.daiqingjie {
  background: #ff976a;
}

.flag.doing {
  background: #1989fa;
}

.flag.bukeyong {
  background: #ee0a24;
}

.right {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-width: 0;
}

.info .name {
  font-size: 32rpx;
  font-weight: bold;
  margin-bottom: 10rpx;
  word-break: break-all;
}

.info .type {
  font-size: 24rpx;
  color: #666;
  font-weight: normal;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 10rpx;
}

.tag {
  padding: 4rpx 12rpx;
  background: #f0f0f0;
  border-radius: 6rpx;
  font-size: 22rpx;
  color: #666;
}

.bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10rpx;
}

.price {
  font-size: 32rpx;
  font-weight: bold;
}

.color-attention {
  color: #ee0a24;
}

.btn {
  padding: 10rpx 30rpx;
  border-radius: 8rpx;
  font-size: 28rpx;
  color: #fff;
  border: none;
  line-height: 1.5;
}

.bg-primary {
  background: var(--main-color, #5AAB6E);
}

.btn.disabled {
  background: #ccc;
  color: #999;
}

.foot {
  padding: 20rpx;
  border-top: 1rpx solid #f0f0f0;
}

.foot-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20rpx;
}

.labels {
  display: flex;
  gap: 20rpx;
}

.label {
  display: flex;
  align-items: center;
  font-size: 22rpx;
  color: #666;
}

.label::before {
  content: '';
  width: 20rpx;
  height: 20rpx;
  background: #07c160;
  border-radius: 4rpx;
  margin-right: 8rpx;
}

.label.disabled::before {
  background: #ccc;
}

.tip {
  font-size: 22rpx;
}

.times {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
}

.time {
  width: 60rpx;
  height: 50rpx;
  line-height: 50rpx;
  text-align: center;
  background: #f0f0f0;
  border-radius: 6rpx;
  font-size: 22rpx;
  color: #333;
}

.time.disabled {
  background: #ccc;
  color: #999;
}
</style>
