<template>
  <view class="container" :style="themeStyle">
    <view class="form" v-if="!success">
      <!-- 预约场地 -->
      <view class="form-item">
        <view class="label">预约场地</view>
        <input class="input" v-model="roomName" disabled />
      </view>

      <!-- 手机号 -->
      <view class="form-item">
        <view class="label">手机号</view>
        <input 
          class="input" 
          v-model="phone" 
          placeholder="请填写手机号" 
          maxlength="11"
          type="number"
        />
      </view>

      <!-- 开始时间 -->
      <view class="form-item">
        <view class="label">开始时间</view>
        <picker 
          mode="multiSelector" 
          :value="multiIndex" 
          :range="multiArray"
          @change="bindMultiPickerChange"
          @columnchange="bindMultiPickerColumnChange"
        >
          <view class="picker">{{ startTime }}</view>
        </picker>
      </view>

      <!-- 结束时间 -->
      <view class="form-item">
        <view class="label">结束时间</view>
        <picker 
          mode="multiSelector" 
          :value="multiEndIndex" 
          :range="multiArray"
          @change="bindMultiPickerChanges"
          @columnchange="bindMultiPickerColumnChanges"
        >
          <view class="picker">{{ endTime }}</view>
        </picker>
      </view>

      <!-- 记账金额 -->
      <view class="form-item">
        <view class="label">记账金额</view>
        <input 
          class="input" 
          v-model="money" 
          placeholder="单位元，默认0元" 
          maxlength="10"
          type="digit"
        />
      </view>

      <view class="tips">单位/元，添加记账金额后可在数据统计查看</view>
    </view>

    <!-- 提交按钮 -->
    <view class="btns">
      <button class="btn submit bg-primary" @tap="submit">提交申请</button>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http-legacy'

export default {
  data() {
    return {
      start_time: '选择开始时间',
      end_time: '选择结束时间',
      multiArray: [],
      multiIndex: [],
      multiEndIndex: [],
      year: '',
      month: '',
      day: '',
      hour: '',
      minute: '',
      room_id: '',
      roomName: '',
      phone: '',
      money: '',
      success: false
    }
  },

  onLoad(options) {
    if (options.id) {
      this.room_id = options.id
      this.room_name = options.room_name || ''
    }
  },

  onShow() {
    this.initDatePicker()
  },

  methods: {
    // 初始化日期选择器
    initDatePicker() {
      const loadPickerData = this.loadPickerData()
      const getCurrentDate = this.getCurrentDate()
      const GetMultiIndex = this.GetMultiIndex()

      const year = parseInt(getCurrentDate.substring(0, 4))
      const month = parseInt(getCurrentDate.substring(5, 7))
      const day = parseInt(getCurrentDate.substring(8, 10))
      const hour = parseInt(getCurrentDate.substring(11, 13))
      const minute = parseInt(getCurrentDate.substring(14, 16))

      this.multiArray = loadPickerData
      this.multiIndex = GetMultiIndex
      this.multiEndIndex = GetMultiIndex
      this.start_time = getCurrentDate
      this.year = year
      this.month = month
      this.day = day
      this.hour = hour
      this.minute = minute
    },

    // 获取当前日期时间
    getCurrentDate() {
      const date = new Date()
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      const hour = String(date.getHours()).padStart(2, '0')
      const minute = String(date.getMinutes()).padStart(2, '0')
      return `${year}/${month}/${day} ${hour}:${minute}`
    },

    // 获取初始索引
    GetMultiIndex() {
      return [0, 0, 0, 0, 0]
    },

    // 加载选择器数据
    loadPickerData() {
      const date = new Date()
      const currentYear = date.getFullYear()
      const currentMonth = date.getMonth() + 1
      const currentDay = date.getDate()
      const currentHour = date.getHours()
      const currentMinute = date.getMinutes()

      return [
        this.loadYears(currentYear, currentYear + 1),
        this.loadMonths(currentMonth, 12),
        this.loadDays(currentYear, currentMonth, currentDay),
        this.loadHours(currentHour, 23),
        this.loadMinutes(currentMinute, 59)
      ]
    },

    // 加载年份
    loadYears(start, end) {
      const years = []
      for (let i = start; i <= end; i++) {
        years.push(`${i}年`)
      }
      return years
    },

    // 加载月份
    loadMonths(start, end) {
      const months = []
      for (let i = start; i <= end; i++) {
        months.push(`${i}月`)
      }
      return months
    },

    // 加载天数
    loadDays(year, month, start) {
      const days = []
      const daysInMonth = new Date(year, month, 0).getDate()
      for (let i = start; i <= daysInMonth; i++) {
        days.push(`${i}日`)
      }
      return days
    },

    // 加载小时
    loadHours(start, end) {
      const hours = []
      for (let i = start; i <= end; i++) {
        hours.push(`${i}时`)
      }
      return hours
    },

    // 加载分钟
    loadMinutes(start, end) {
      const minutes = []
      for (let i = start; i <= end; i++) {
        minutes.push(`${i}分`)
      }
      return minutes
    },

    // 开始时间选择
    bindMultiPickerChange(e) {
      this.multiIndex = e.detail.value
      const index = this.multiIndex
      const year = this.multiArray[0][index[0]]
      const month = this.multiArray[1][index[1]]
      const day = this.multiArray[2][index[2]]
      const hour = this.multiArray[3][index[3]]
      const minute = this.multiArray[4][index[4]]

      this.start_time = `${year.replace('年', '/')}${month.replace('月', '/')}${day.replace('日', '')} ${hour.replace('时', '')}:${minute.replace('分', '')}`
      this.year = parseInt(year)
      this.month = parseInt(month)
      this.day = parseInt(day)
      this.hour = parseInt(hour)
      this.minute = parseInt(minute)
    },

    // 结束时间选择
    bindMultiPickerChanges(e) {
      this.multiEndIndex = e.detail.value
      const index = this.multiEndIndex
      const year = this.multiArray[0][index[0]]
      const month = this.multiArray[1][index[1]]
      const day = this.multiArray[2][index[2]]
      const hour = this.multiArray[3][index[3]]
      const minute = this.multiArray[4][index[4]]

      this.end_time = `${year.replace('年', '/')}${month.replace('月', '/')}${day.replace('日', '')} ${hour.replace('时', '')}:${minute.replace('分', '')}`
    },

    // 列改变
    bindMultiPickerColumnChange(e) {
      this.handleColumnChange(e, 'multiIndex')
    },

    bindMultiPickerColumnChanges(e) {
      this.handleColumnChange(e, 'multiEndIndex')
    },

    // 处理列改变
    handleColumnChange(e, indexKey) {
      const getCurrentDate = this.getCurrentDate()
      const currentYear = parseInt(getCurrentDate.substring(0, 4))
      const currentMonth = parseInt(getCurrentDate.substring(5, 7))
      const currentDay = parseInt(getCurrentDate.substring(8, 10))
      const currentHour = parseInt(getCurrentDate.substring(11, 13))
      const currentMinute = parseInt(getCurrentDate.substring(14, 16))

      const multiIndex = [...this[indexKey]]
      multiIndex[e.detail.column] = e.detail.value

      switch (e.detail.column) {
        case 0: // 年份
          const yearSelected = parseInt(this.multiArray[0][e.detail.value])
          if (yearSelected === currentYear) {
            this.multiArray = this.loadPickerData()
            this[indexKey] = [0, 0, 0, 0, 0]
          } else {
            this.multiArray[1] = this.loadMonths(1, 12)
            this.multiArray[2] = this.loadDays(yearSelected, 1, 1)
            this.multiArray[3] = this.loadHours(0, 23)
            this.multiArray[4] = this.loadMinutes(0, 59)
            this[indexKey] = [e.detail.value, 0, 0, 0, 0]
          }
          break
        case 1: // 月份
          const mon = parseInt(this.multiArray[1][e.detail.value])
          const yearForMonth = parseInt(this.multiArray[0][multiIndex[0]])
          if (yearForMonth === currentYear && mon === currentMonth) {
            this.multiArray[2] = this.loadDays(currentYear, mon, currentDay)
          } else {
            this.multiArray[2] = this.loadDays(yearForMonth, mon, 1)
          }
          this.multiArray[3] = this.loadHours(0, 23)
          this.multiArray[4] = this.loadMinutes(0, 59)
          this[indexKey] = [multiIndex[0], e.detail.value, 0, 0, 0]
          break
        case 2: // 日
          const dd = parseInt(this.multiArray[2][e.detail.value])
          const yearForDay = parseInt(this.multiArray[0][multiIndex[0]])
          const monthForDay = parseInt(this.multiArray[1][multiIndex[1]])
          if (dd === currentDay && yearForDay === currentYear && monthForDay === currentMonth) {
            this.multiArray[3] = this.loadHours(currentHour, 23)
            this.multiArray[4] = this.loadMinutes(currentMinute, 59)
          } else {
            this.multiArray[3] = this.loadHours(0, 23)
            this.multiArray[4] = this.loadMinutes(0, 59)
          }
          this[indexKey] = [multiIndex[0], multiIndex[1], e.detail.value, 0, 0]
          break
        case 3: // 小时
          const hh = parseInt(this.multiArray[3][e.detail.value])
          const yearForHour = parseInt(this.multiArray[0][multiIndex[0]])
          const monthForHour = parseInt(this.multiArray[1][multiIndex[1]])
          const dayForHour = parseInt(this.multiArray[2][multiIndex[2]])
          if (hh === currentHour && yearForHour === currentYear && monthForHour === currentMonth && dayForHour === currentDay) {
            this.multiArray[4] = this.loadMinutes(currentMinute, 59)
          } else {
            this.multiArray[4] = this.loadMinutes(0, 59)
          }
          this[indexKey] = [multiIndex[0], multiIndex[1], multiIndex[2], e.detail.value, multiIndex[4]]
          break
        case 4: // 分钟
          this[indexKey] = [multiIndex[0], multiIndex[1], multiIndex[2], multiIndex[3], e.detail.value]
          break
      }
    },

    // 提交
    submit() {
      if (this.end_time === '选择结束时间') {
        uni.showModal({
          content: '请选择结束时间',
          showCancel: false
        })
        return
      }

      if (!this.phone) {
        uni.showModal({
          content: '请填写手机号',
          showCancel: false
        })
        return
      }

      const price = this.money ? parseFloat(this.money) : 0

      const that = this
      const app = getApp()

      http.request(
        '/member/manager/submitOrder',
        '1',
        'post',
        {
          room_id: that.room_id,
          start_time: that.start_time,
          end_time: that.end_time,
          mobile: that.phone,
          price: price
        },
        app.globalData.userDatatoken ? app.globalData.userDatatoken.access_token : '',
        '提交中...',
        function success(res) {
          if (res.code === 0) {
            uni.showToast({ title: '操作成功', icon: 'success' })
            setTimeout(() => { uni.navigateBack() }, 1000)
          } else {
            uni.showModal({ content: res.msg || '提交失败', showCancel: false })
          }
        },
        function fail(err) {
          uni.showModal({ content: '提交失败，请重试', showCancel: false })
        }
      )
    }
  }
}
</script>

<style lang="scss">
page {
  background: #f5f5f5;
}
</style>
<style lang="scss" scoped>
.container {
  padding: 30rpx;
  padding-bottom: 150rpx;
}

.form {
  background: #fff;
  border-radius: 30rpx;
  overflow: hidden;
}

.form-item {
  display: flex;
  align-items: center;
  padding: 30rpx 30rpx;
  border-bottom: 1rpx solid #f0f0f0;
}

.form-item:last-child {
  border-bottom: none;
}

.label {
  width: 180rpx;
  font-size: 28rpx;
  color: #333;
}

.input {
  flex: 1;
  font-size: 28rpx;
  color: #333;
}

.picker {
  flex: 1;
  font-size: 28rpx;
  color: #333;
  text-align: right;
}

.tips {
  margin: 20rpx 30rpx;
  font-size: 24rpx;
  color: #999;
}

.btns {
  display: flex;
  justify-content: center;
  margin-top: 50rpx;
}

.btn {
  width: 320rpx;
  height: 90rpx;
  line-height: 90rpx;
  box-sizing: border-box;
  font-size: 28rpx;
  border-radius: 60rpx;
  text-align: center;
  border: none;
}

.btn::after {
  border: none;
}

.bg-primary {
  background: var(--main-color, #5AAB6E);
  color: #fff;
}
</style>
