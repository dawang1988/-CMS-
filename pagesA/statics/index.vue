<template>
  <view class="container" :style="themeStyle">
    <!-- 门店筛选（从门店管理进来时隐藏） -->
    <view class="search" v-if="!fixedStore">
      <picker :value="storeIndex" :range="stores" range-key="text" @change="storeDropdown">
        <view class="picker-value">{{ stores[storeIndex] ? stores[storeIndex].text : '全部门店' }} ▼</view>
      </picker>
    </view>
    <view class="search" v-else>
      <view class="picker-value">{{ fixedStoreName }}</view>
    </view>

    <!-- 顶部总览 -->
    <view class="section top">
      <view class="box">
        <view class="item">
          <view class="color-primary">总收入(元)</view>
          <view class="price">{{ topinfo.total_income || 0 }}</view>
        </view>
        <view class="item">
          <view class="color-primary">总订单数</view>
          <view class="price">{{ topinfo.total_orders || 0 }}</view>
        </view>
      </view>
    </view>

    <!-- 经营数据 -->
    <view class="section total">
      <view class="box">
        <view class="line">
          <view class="data-item"><view class="label color-primary">总收入</view><view class="price2">{{ businfo.total_income || 0 }}</view></view>
          <view class="data-item"><view class="label color-primary">订单数</view><view class="price2">{{ businfo.total_orders || 0 }}</view></view>
          <view class="data-item"><view class="label color-primary">均价</view><view class="price2">{{ businfo.avg_price || 0 }}</view></view>
        </view>
      </view>
      <!-- 日期快捷选择 - 经营数据 -->
      <view class="times">
        <view :class="!isDefine && sdt === date ? 'time-item active' : 'time-item'" @tap="changeDate(1, 'bus')">
          <view class="time">今日</view><view class="tip">{{ date_short }}</view>
        </view>
        <view :class="!isDefine && sdt === date_last ? 'time-item active' : 'time-item'" @tap="changeDate(2, 'bus')">
          <view class="time">昨日</view><view class="tip">{{ date_last_short }}</view>
        </view>
        <view :class="!isDefine && sdt === date7 ? 'time-item active' : 'time-item'" @tap="changeDate(7, 'bus')">
          <view class="time">7日</view><view class="tip">{{ date7_short }}至{{ date_short }}</view>
        </view>
        <view :class="!isDefine && sdt === date30 ? 'time-item active' : 'time-item'" @tap="changeDate(30, 'bus')">
          <view class="time">30日</view><view class="tip">{{ date30_short }}至{{ date_short }}</view>
        </view>
        <view :class="isDefine && type === 'bus' ? 'time-item active' : 'time-item'" @tap="openDefine('bus')">
          <view class="time">自定义</view><view class="tip"></view>
        </view>
      </view>
    </view>

    <!-- 经营概况 -->
    <view class="section">
      <view class="title">经营概况</view>
      <view class="tab-bar">
        <view :class="active1 === 0 ? 'tab active' : 'tab'" @tap="active1 = 0">收益统计</view>
        <view :class="active1 === 1 ? 'tab active' : 'tab'" @tap="active1 = 1">订单统计</view>
        <view :class="active1 === 2 ? 'tab active' : 'tab'" @tap="active1 = 2">人数统计</view>
      </view>
      <!-- 收益统计 -->
      <view v-if="active1 === 0">
        <view class="charts">
          <canvas v-if="ininfo_y.length > 0" canvas-id="canvasIninfo" id="canvasIninfo" class="charts-canvas" @touchstart="touchIninfo"></canvas>
          <view v-else class="nodata">暂无数据</view>
        </view>
      </view>
      <!-- 订单统计 -->
      <view v-if="active1 === 1">
        <view class="charts">
          <canvas v-if="inorder.length > 0" canvas-id="canvasInorder" id="canvasInorder" class="charts-canvas" @touchstart="touchInorder"></canvas>
          <view v-else class="nodata">暂无数据</view>
        </view>
      </view>
      <!-- 人数统计 -->
      <view v-if="active1 === 2">
        <view class="charts">
          <canvas v-if="inpeople_y.length > 0" canvas-id="canvasInpeople" id="canvasInpeople" class="charts-canvas" @touchstart="touchInpeople"></canvas>
          <view v-else class="nodata">暂无数据</view>
        </view>
      </view>
      <!-- 日期快捷选择 - 经营概况 -->
      <view class="times">
        <view :class="!isDefine1 && sdt1 === date ? 'time-item active' : 'time-item'" @tap="changeDate(1, 'in')">
          <view class="time">今日</view><view class="tip">{{ date_short }}</view>
        </view>
        <view :class="!isDefine1 && sdt1 === date_last ? 'time-item active' : 'time-item'" @tap="changeDate(2, 'in')">
          <view class="time">昨日</view><view class="tip">{{ date_last_short }}</view>
        </view>
        <view :class="!isDefine1 && sdt1 === date7 ? 'time-item active' : 'time-item'" @tap="changeDate(7, 'in')">
          <view class="time">7日</view><view class="tip">{{ date7_short }}至{{ date_short }}</view>
        </view>
        <view :class="!isDefine1 && sdt1 === date30 ? 'time-item active' : 'time-item'" @tap="changeDate(30, 'in')">
          <view class="time">30日</view><view class="tip">{{ date30_short }}至{{ date_short }}</view>
        </view>
        <view :class="isDefine1 && type === 'in' ? 'time-item active' : 'time-item'" @tap="openDefine('in')">
          <view class="time">自定义</view><view class="tip"></view>
        </view>
      </view>
    </view>

    <!-- 收入统计 -->
    <view class="section">
      <view class="title">收入统计</view>
      <view class="tab-bar">
        <view :class="active3 === 0 ? 'tab active' : 'tab'" @tap="active3 = 0">收入明细</view>
        <view :class="active3 === 1 ? 'tab active' : 'tab'" @tap="active3 = 1">充值明细</view>
      </view>
      <!-- 收入明细 -->
      <view v-if="active3 === 0">
        <view class="charts" v-if="income.length > 0">
          <view class="roomtime">
            <view class="table-header">
              <view class="table1">门店名称</view>
              <view class="table2">金额(元)</view>
              <view class="table3">类型</view>
              <view class="table4">时间</view>
            </view>
            <scroll-view scroll-y class="table-scroll">
              <view v-for="(item, idx) in income" :key="idx" :class="idx % 2 === 0 ? 'table-line' : 'table-line odd'">
                <view class="table1 storeName">{{ item.storeName }}</view>
                <view class="table2">{{ item.price }}</view>
                <view class="table3">{{ item.payType }}</view>
                <view class="table4">{{ item.createTime }}</view>
              </view>
            </scroll-view>
          </view>
        </view>
        <view class="charts" v-else><view class="nodata">暂无数据</view></view>
      </view>
      <!-- 充值明细 -->
      <view v-if="active3 === 1">
        <view class="charts" v-if="recharge.length > 0">
          <view class="roomtime">
            <view class="table-header">
              <view class="table1">门店名称</view>
              <view class="table2">金额(元)</view>
              <view class="table4">时间</view>
            </view>
            <scroll-view scroll-y class="table-scroll">
              <view v-for="(item, idx) in recharge" :key="idx" :class="idx % 2 === 0 ? 'table-line' : 'table-line odd'">
                <view class="table1 storeName">{{ item.storeName }}</view>
                <view class="table2">{{ item.price }}</view>
                <view class="table4">{{ item.createTime }}</view>
              </view>
            </scroll-view>
          </view>
        </view>
        <view class="charts" v-else><view class="nodata">暂无数据</view></view>
      </view>
      <!-- 日期快捷选择 - 收入统计 -->
      <view class="times">
        <view :class="!isDefine3 && sdt3 === date ? 'time-item active' : 'time-item'" @tap="changeDate(1, 'price')">
          <view class="time">今日</view><view class="tip">{{ date_short }}</view>
        </view>
        <view :class="!isDefine3 && sdt3 === date_last ? 'time-item active' : 'time-item'" @tap="changeDate(2, 'price')">
          <view class="time">昨日</view><view class="tip">{{ date_last_short }}</view>
        </view>
        <view :class="!isDefine3 && sdt3 === date7 ? 'time-item active' : 'time-item'" @tap="changeDate(7, 'price')">
          <view class="time">7日</view><view class="tip">{{ date7_short }}至{{ date_short }}</view>
        </view>
        <view :class="!isDefine3 && sdt3 === date30 ? 'time-item active' : 'time-item'" @tap="changeDate(30, 'price')">
          <view class="time">30日</view><view class="tip">{{ date30_short }}至{{ date_short }}</view>
        </view>
        <view :class="isDefine3 && type === 'price' ? 'time-item active' : 'time-item'" @tap="openDefine('price')">
          <view class="time">自定义</view><view class="tip"></view>
        </view>
      </view>
    </view>

    <!-- 房间使用情况 -->
    <view class="section">
      <view class="title">房间使用情况</view>
      <view class="tab-bar">
        <view :class="active2 === 0 ? 'tab active' : 'tab'" @tap="active2 = 0">使用率</view>
        <view :class="active2 === 1 ? 'tab active' : 'tab'" @tap="active2 = 1">使用时长</view>
      </view>
      <!-- 使用率 -->
      <view v-if="active2 === 0">
        <view class="charts">
          <canvas v-if="roominfo_y.length > 0" canvas-id="canvasRoom" id="canvasRoom" class="charts-canvas" @touchstart="touchRoom"></canvas>
          <view v-else class="nodata">暂无数据</view>
        </view>
      </view>
      <!-- 使用时长 -->
      <view v-if="active2 === 1">
        <view class="charts" v-if="roomtime.length > 0">
          <view class="roomtime">
            <view class="table-header">
              <view class="stable1">房间</view>
              <view class="stable2">小时</view>
            </view>
            <scroll-view scroll-y class="table-scroll">
              <view v-for="(item, idx) in roomtime" :key="idx" :class="idx % 2 === 0 ? 'table-line' : 'table-line odd'">
                <view class="stable1">{{ item.roomName }}</view>
                <view class="stable2">{{ item.hours }}</view>
              </view>
            </scroll-view>
          </view>
        </view>
        <view class="charts" v-else><view class="nodata">暂无数据</view></view>
      </view>
      <!-- 日期快捷选择 - 房间 -->
      <view class="times">
        <view :class="!isDefine2 && sdt2 === date ? 'time-item active' : 'time-item'" @tap="changeDate(1, 'room')">
          <view class="time">今日</view><view class="tip">{{ date_short }}</view>
        </view>
        <view :class="!isDefine2 && sdt2 === date_last ? 'time-item active' : 'time-item'" @tap="changeDate(2, 'room')">
          <view class="time">昨日</view><view class="tip">{{ date_last_short }}</view>
        </view>
        <view :class="!isDefine2 && sdt2 === date7 ? 'time-item active' : 'time-item'" @tap="changeDate(7, 'room')">
          <view class="time">7日</view><view class="tip">{{ date7_short }}至{{ date_short }}</view>
        </view>
        <view :class="!isDefine2 && sdt2 === date30 ? 'time-item active' : 'time-item'" @tap="changeDate(30, 'room')">
          <view class="time">30日</view><view class="tip">{{ date30_short }}至{{ date_short }}</view>
        </view>
        <view :class="isDefine2 && type === 'room' ? 'time-item active' : 'time-item'" @tap="openDefine('room')">
          <view class="time">自定义</view><view class="tip"></view>
        </view>
      </view>
    </view>

    <!-- 自定义日期弹窗 -->
    <view class="mask" v-if="showDefine" @tap="showDefine = false">
      <view class="define-dialog" @tap.stop>
        <view class="define-title">自定义日期</view>
        <view class="define-row">
          <picker mode="date" @change="onStartChange">
            <view class="define-input">{{ defineStart || '开始时间' }}</view>
          </picker>
          <text class="define-sep">至</text>
          <picker mode="date" @change="onEndChange">
            <view class="define-input">{{ defineEnd || '结束时间' }}</view>
          </picker>
        </view>
        <view class="define-btns">
          <view class="define-btn cancel" @tap="showDefine = false">取消</view>
          <view class="define-btn confirm" @tap="confirmDefine">确定</view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http'
import uCharts from '@/components/u-charts/u-charts.js'

let chartIninfo = null
let chartInorder = null
let chartInpeople = null
let chartRoom = null
// 响应式图表尺寸，根据屏幕宽度动态计算
let cWidth = 700
let cHeight = 400

function formatDate(d) {
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}
function shortDate(d) {
  return `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

export default {
  data() {
    const now = new Date()
    const yesterday = new Date(now.getTime() - 86400000)
    const day7 = new Date(now.getTime() - 6 * 86400000)
    const day30 = new Date(now.getTime() - 30 * 86400000)
    return {
      store_id: '',
      fixedStore: false,
      fixedStoreName: '',
      storeIndex: 0,
      stores: [{ text: '全部门店', value: '' }],
      topinfo: {},
      businfo: {},
      // 日期
      date: formatDate(now),
      date_short: shortDate(now),
      date_last: formatDate(yesterday),
      date_last_short: shortDate(yesterday),
      date7: formatDate(day7),
      date7_short: shortDate(day7),
      date30: formatDate(day30),
      date30_short: shortDate(day30),
      // 经营数据日期
      sdt: formatDate(now),
      edt: formatDate(now),
      isDefine: false,
      // 经营概况日期
      sdt1: formatDate(now),
      edt1: formatDate(now),
      isDefine1: false,
      // 房间日期
      sdt2: formatDate(now),
      edt2: formatDate(now),
      isDefine2: false,
      // 收入统计日期
      sdt3: formatDate(now),
      edt3: formatDate(now),
      isDefine3: false,
      type: '',
      // Tab
      active1: 0,
      active2: 0,
      active3: 0,
      // 图表数据
      ininfo_x: [],
      ininfo_y: [],
      inorder: [],
      totalOrder: 0,
      inpeople_x: [],
      inpeople_y: [],
      roominfo_x: [],
      roominfo_y: [],
      roomtime: [],
      income: [],
      recharge: [],
      // 自定义弹窗
      showDefine: false,
      defineStart: '',
      defineEnd: '',
      // canvas尺寸
      pixelRatio: 1,
      canvasWidth: 700,
      canvasHeight: 400
    }
  },
  onLoad(options) {
    if (options.store_id) {
      this.store_id = options.store_id
      this.fixedStore = true
    }
    // 获取系统信息，计算响应式尺寸
    const sysInfo = uni.getSystemInfoSync()
    this.pixelRatio = sysInfo.pixelRatio || 1
    // 根据屏幕宽度动态计算图表尺寸（留出边距）
    const screenWidth = sysInfo.windowWidth || 375
    this.canvasWidth = Math.floor((screenWidth - 60) * (750 / screenWidth))
    this.canvasHeight = Math.floor(this.canvasWidth * 0.57) // 保持宽高比约 7:4
    // 更新全局变量
    cWidth = this.canvasWidth
    cHeight = this.canvasHeight
    this.getStoreList()
    this.getTop()
    this.getBus()
    this.getIn()
    this.getInorder()
    this.getInpeople()
    this.getRoom()
    this.getRoomtime()
    this.getIncome()
    this.getRecharge()
  },
  watch: {
    active1(val) {
      this.$nextTick(() => {
        if (val === 0 && this.ininfo_y.length > 0) this.drawIninfo()
        if (val === 1 && this.inorder.length > 0) this.drawInorder()
        if (val === 2 && this.inpeople_y.length > 0) this.drawInpeople()
      })
    },
    active2(val) {
      this.$nextTick(() => {
        if (val === 0 && this.roominfo_y.length > 0) this.drawRoom()
      })
    }
  },
  methods: {
    // 获取门店下拉列表
    getStoreList() {
      http.get('/member/store/getStoreListByAdmin').then(res => {
        if (res.code === 0) {
          let stores = [{ text: '全部门店', value: '' }]
          if (res.data && res.data.length) {
            res.data.forEach(it => {
              stores.push({ text: it.key, value: it.value })
            })
          }
          this.stores = stores
          // 如果是从门店管理进来的，显示当前门店名称
          if (this.fixedStore && this.store_id) {
            const found = stores.find(s => String(s.value) === String(this.store_id))
            this.fixedStoreName = found ? found.text : '当前门店'
          }
        }
      }).catch(() => {})
    },
    storeDropdown(e) {
      this.storeIndex = e.detail.value
      this.store_id = this.stores[e.detail.value].value
      this.getTop()
      this.getBus()
      this.getIn()
      this.getInorder()
      this.getInpeople()
      this.getRoom()
      this.getRoomtime()
      this.getIncome()
      this.getRecharge()
    },
    // 日期切换
    changeDate(info, type) {
      let sdt = ''
      let edt = this.date
      if (info === 1) sdt = this.date
      else if (info === 2) { sdt = this.date_last; edt = this.date_last }
      else if (info === 7) sdt = this.date7
      else if (info === 30) sdt = this.date30

      if (type === 'bus') {
        this.sdt = sdt; this.edt = edt; this.isDefine = false
        this.getBus()
      } else if (type === 'in') {
        this.sdt1 = sdt; this.edt1 = edt; this.isDefine1 = false
        this.getIn(); this.getInorder(); this.getInpeople()
      } else if (type === 'room') {
        this.sdt2 = sdt; this.edt2 = edt; this.isDefine2 = false
        this.getRoom(); this.getRoomtime()
      } else if (type === 'price') {
        this.sdt3 = sdt; this.edt3 = edt; this.isDefine3 = false
        this.getIncome(); this.getRecharge()
      }
    },
    openDefine(type) {
      this.type = type
      this.defineStart = ''
      this.defineEnd = ''
      this.showDefine = true
      if (type === 'bus') this.isDefine = true
      else if (type === 'in') this.isDefine1 = true
      else if (type === 'room') this.isDefine2 = true
      else if (type === 'price') this.isDefine3 = true
    },
    onStartChange(e) { this.defineStart = e.detail.value },
    onEndChange(e) { this.defineEnd = e.detail.value },
    confirmDefine() {
      if (!this.defineStart || !this.defineEnd) {
        this.showDefine = false
        return
      }
      this.showDefine = false
      const type = this.type
      if (type === 'bus') {
        this.sdt = this.defineStart; this.edt = this.defineEnd
        this.getBus()
      } else if (type === 'in') {
        this.sdt1 = this.defineStart; this.edt1 = this.defineEnd
        this.getIn(); this.getInorder(); this.getInpeople()
      } else if (type === 'room') {
        this.sdt2 = this.defineStart; this.edt2 = this.defineEnd
        this.getRoom(); this.getRoomtime()
      } else if (type === 'price') {
        this.sdt3 = this.defineStart; this.edt3 = this.defineEnd
        this.getIncome(); this.getRecharge()
      }
    },
    // ========== API 调用 ==========
    getTop() {
      http.post('/member/chart/getBusinessStatistics', { store_id: this.store_id }).then(res => {
        if (res.code === 0) this.topinfo = res.data || {}
      }).catch(() => {})
    },
    getBus() {
      http.post('/member/chart/getBusinessStatistics', {
        store_id: this.store_id, start_date: this.sdt, end_date: this.edt
      }).then(res => {
        if (res.code === 0) this.businfo = res.data || {}
      }).catch(() => {})
    },
    getIn() {
      http.post('/member/chart/getRevenueStatistics', {
        store_id: this.store_id, start_date: this.sdt1, end_date: this.edt1
      }).then(res => {
        if (res.code === 0 && res.data) {
          const arr = Array.isArray(res.data) ? res.data : (res.data.chart || [])
          this.ininfo_x = arr.map(it => it.date || it.key)
          this.ininfo_y = arr.map(it => it.amount || it.value)
          this.$nextTick(() => { if (this.active1 === 0) this.drawIninfo() })
        } else {
          this.ininfo_x = []; this.ininfo_y = []
        }
      }).catch(() => { this.ininfo_x = []; this.ininfo_y = [] })
    },
    getInorder() {
      http.post('/member/chart/getOrderStatistics', {
        store_id: this.store_id, start_date: this.sdt1, end_date: this.edt1
      }).then(res => {
        if (res.code === 0 && res.data) {
          const arr = Array.isArray(res.data) ? res.data : []
          this.totalOrder = 0
          this.inorder = arr.map(it => {
            const val = it.count || it.value || 0
            this.totalOrder += val
            return { name: it.date || it.key, value: val }
          })
          this.$nextTick(() => { if (this.active1 === 1) this.drawInorder() })
        } else {
          this.inorder = []; this.totalOrder = 0
        }
      }).catch(() => { this.inorder = []; this.totalOrder = 0 })
    },
    getInpeople() {
      http.post('/member/chart/getMemberStatistics', {
        store_id: this.store_id, start_date: this.sdt1, end_date: this.edt1
      }).then(res => {
        if (res.code === 0 && res.data) {
          const arr = Array.isArray(res.data) ? res.data : (res.data.chart || [])
          this.inpeople_x = arr.map(it => it.date || it.key)
          this.inpeople_y = arr.map(it => it.count || it.value)
          this.$nextTick(() => { if (this.active1 === 2) this.drawInpeople() })
        } else {
          this.inpeople_x = []; this.inpeople_y = []
        }
      }).catch(() => { this.inpeople_x = []; this.inpeople_y = [] })
    },
    getRoom() {
      http.post('/member/chart/getRoomUseStatistics', {
        store_id: this.store_id, start_date: this.sdt2, end_date: this.edt2
      }).then(res => {
        if (res.code === 0 && res.data) {
          const arr = Array.isArray(res.data) ? res.data : []
          this.roominfo_x = arr.map(it => it.date || it.key)
          this.roominfo_y = arr.map(it => it.count || it.value)
          this.$nextTick(() => { if (this.active2 === 0) this.drawRoom() })
        } else {
          this.roominfo_x = []; this.roominfo_y = []
        }
      }).catch(() => { this.roominfo_x = []; this.roominfo_y = [] })
    },
    getRoomtime() {
      http.post('/member/chart/getRoomUseHour', {
        store_id: this.store_id, start_date: this.sdt2, end_date: this.edt2
      }).then(res => {
        if (res.code === 0) this.roomtime = res.data || []
      }).catch(() => { this.roomtime = [] })
    },
    getIncome() {
      http.post('/member/chart/getIncomeStatistics', {
        store_id: this.store_id, start_date: this.sdt3, end_date: this.edt3
      }).then(res => {
        if (res.code === 0) this.income = res.data || []
      }).catch(() => { this.income = [] })
    },
    getRecharge() {
      http.post('/member/chart/getRechargeStatistics', {
        store_id: this.store_id, start_date: this.sdt3, end_date: this.edt3
      }).then(res => {
        if (res.code === 0) this.recharge = res.data || []
      }).catch(() => { this.recharge = [] })
    },
    // ========== 图表绘制 ==========
    drawIninfo() {
      chartIninfo = new uCharts({
        $bindthis: this,
        type: 'line',
        canvasId: 'canvasIninfo',
        context: uni.createCanvasContext('canvasIninfo', this),
        width: cWidth,
        height: cHeight,
        pixelRatio: this.pixelRatio,
        categories: this.ininfo_x,
        series: [{ name: '营业收益', data: this.ininfo_y, color: this.themeColor }],
        animation: true,
        background: '#FFFFFF',
        padding: [15, 10, 0, 15],
        enableScroll: false,
        legend: { show: true, position: 'top', float: 'right' },
        xAxis: { disableGrid: true, rotateLabel: this.ininfo_x.length > 7 },
        yAxis: { gridType: 'dash', dashLength: 2, data: [{ min: 0 }] },
        dataLabel: this.ininfo_y.length <= 15,
        extra: { line: { type: 'curve' } }
      })
    },
    touchIninfo(e) { if (chartIninfo) chartIninfo.showToolTip(e, { format: (item) => item.name + ': ' + item.data + '元' }) },
    drawInorder() {
      chartInorder = new uCharts({
        $bindthis: this,
        type: 'ring',
        canvasId: 'canvasInorder',
        context: uni.createCanvasContext('canvasInorder', this),
        width: cWidth,
        height: cHeight,
        pixelRatio: this.pixelRatio,
        series: this.inorder.map((it, i) => ({
          name: it.name, data: it.value,
          color: ['#1890ff', '#2fc25b', '#facc14', '#f04864'][i % 4]
        })),
        animation: true,
        background: '#FFFFFF',
        padding: [5, 5, 5, 5],
        legend: { show: true, position: 'bottom' },
        subtitle: { name: String(this.totalOrder), color: '#333', fontSize: 20 },
        title: { name: '订单总数', color: '#666', fontSize: 12 },
        dataLabel: true,
        extra: { ring: { ringWidth: 40, offsetAngle: 0 } }
      })
    },
    touchInorder(e) { if (chartInorder) chartInorder.showToolTip(e) },
    drawInpeople() {
      chartInpeople = new uCharts({
        $bindthis: this,
        type: 'line',
        canvasId: 'canvasInpeople',
        context: uni.createCanvasContext('canvasInpeople', this),
        width: cWidth,
        height: cHeight,
        pixelRatio: this.pixelRatio,
        categories: this.inpeople_x,
        series: [{ name: '顾客数量', data: this.inpeople_y, color: '#1890ff' }],
        animation: true,
        background: '#FFFFFF',
        padding: [15, 10, 0, 15],
        enableScroll: false,
        legend: { show: true, position: 'top', float: 'right' },
        xAxis: { disableGrid: true, rotateLabel: this.inpeople_x.length > 7 },
        yAxis: { gridType: 'dash', dashLength: 2, data: [{ min: 0 }] },
        dataLabel: this.inpeople_y.length <= 15,
        extra: { line: { type: 'curve' } }
      })
    },
    touchInpeople(e) { if (chartInpeople) chartInpeople.showToolTip(e, { format: (item) => item.name + ': ' + item.data + '人' }) },
    drawRoom() {
      chartRoom = new uCharts({
        $bindthis: this,
        type: 'column',
        canvasId: 'canvasRoom',
        context: uni.createCanvasContext('canvasRoom', this),
        width: cWidth,
        height: cHeight,
        pixelRatio: this.pixelRatio,
        categories: this.roominfo_x,
        series: [{ name: '房间使用率', data: this.roominfo_y, color: this.themeColor }],
        animation: true,
        background: '#FFFFFF',
        padding: [15, 10, 0, 15],
        enableScroll: false,
        legend: { show: true, position: 'top', float: 'right' },
        xAxis: { disableGrid: true },
        yAxis: { gridType: 'dash', dashLength: 2, data: [{ min: 0, format: (val) => val + '%' }] },
        dataLabel: this.roominfo_y.length <= 7,
        extra: { column: { width: this.roominfo_y.length > 7 ? 15 : 25 } }
      })
    },
    touchRoom(e) { if (chartRoom) chartRoom.showToolTip(e, { format: (item) => item.name + ': ' + item.data + '%' }) }
  }
}
</script>

<style lang="scss">
page { background: #f5f5f5; }
</style>
<style lang="scss" scoped>
.container { background: linear-gradient(180deg, var(--main-color, #5AAB6E) 0%, var(--main-color, #5AAB6E) 20%, #f5f5f5 20%); min-height: 100vh; }
.search { display: flex; justify-content: center; padding: 20rpx 0; }
.picker-value { font-size: 32rpx; color: #fff; text-align: center; padding: 10rpx 30rpx; }
.section { padding: 20rpx 30rpx; background: #fff; margin: 10rpx; border-radius: 30rpx; }
.title { font-size: 32rpx; color: #000; font-weight: bold; margin-bottom: 10rpx; }
.color-primary { color: var(--main-color, #5AAB6E); font-size: 26rpx; }
.top .box { display: flex; justify-content: space-between; }
.top .box .item { width: 50%; text-align: center; }
.top .box .price { font-size: 40rpx; font-weight: 700; color: #000; }
.total .box .line { display: flex; justify-content: space-between; margin-bottom: 20rpx; }
.total .box .data-item { width: 33.33%; text-align: center; }
.total .box .label { font-size: 26rpx; }
.total .box .price2 { font-size: 32rpx; font-weight: 700; color: #000; margin-top: 10rpx; }
/* 日期选择 */
.times { display: flex; justify-content: space-between; margin-top: 30rpx; }
.time-item { text-align: center; }
.time-item .time { font-size: 26rpx; color: #666; width: 120rpx; height: 50rpx; line-height: 50rpx; text-align: center; margin: 0 auto 10rpx; }
.time-item.active .time { background: #080808; color: #fff; border-radius: 30rpx; }
.time-item .tip { font-size: 24rpx; visibility: hidden; min-height: 30rpx; }
.time-item.active .tip { visibility: visible; color: #999; }
/* Tab */
.tab-bar { display: flex; border-bottom: 1rpx solid #eee; margin-bottom: 10rpx; }
.tab { flex: 1; text-align: center; font-size: 28rpx; padding: 16rpx 0; color: #333; }
.tab.active { color: var(--main-color, #5AAB6E); font-weight: bold; border-bottom: 4rpx solid var(--main-color, #5AAB6E); }
/* 图表 */
.charts { margin-top: 10rpx; width: 100%; min-height: 400rpx; position: relative; }
.charts-canvas { width: 100%; height: 400rpx; }
.nodata { text-align: center; line-height: 400rpx; color: #999; font-size: 28rpx; }
/* 表格 */
.roomtime { width: 100%; height: 400rpx; position: relative; font-size: 26rpx; }
.table-header { display: flex; background: #dedede; line-height: 60rpx; }
.table-header view { flex: 1; text-align: center; }
.table-header .table1, .table-header .stable1 { flex: 2; }
.table-scroll { height: 340rpx; }
.table-line { display: flex; line-height: 60rpx; }
.table-line.odd { background: #ededed; }
.table-line view { flex: 1; text-align: center; font-size: 24rpx; }
.table-line .table1, .table-line .stable1 { flex: 2; }
.storeName { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
/* 自定义弹窗 */
.mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; display: flex; align-items: center; justify-content: center; }
.define-dialog { width: 600rpx; background: #fff; border-radius: 20rpx; padding: 40rpx; }
.define-title { font-size: 32rpx; font-weight: bold; text-align: center; margin-bottom: 30rpx; }
.define-row { display: flex; align-items: center; justify-content: center; margin-bottom: 30rpx; }
.define-input { border: 1rpx solid #ddd; border-radius: 10rpx; padding: 16rpx 24rpx; font-size: 28rpx; min-width: 200rpx; text-align: center; color: #333; }
.define-sep { margin: 0 20rpx; font-size: 28rpx; }
.define-btns { display: flex; gap: 20rpx; }
.define-btn { flex: 1; height: 80rpx; line-height: 80rpx; text-align: center; border-radius: 40rpx; font-size: 30rpx; }
.define-btn.cancel { background: #f5f5f5; color: #666; }
.define-btn.confirm { background: var(--main-color, #5AAB6E); color: #fff; }
</style>
