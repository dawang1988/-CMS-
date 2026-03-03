<template>
  <view class="page" :style="themeStyle">
    <!-- 筛选栏 -->
    <view class="filter-bar">
      <view class="filter-item" @click="showRoomFilter = !showRoomFilter">
        <text>{{ currentRoomLabel }}</text>
        <text class="arrow">▼</text>
      </view>
      <view class="filter-item" @click="showTypeFilter = !showTypeFilter">
        <text>{{ currentTypeLabel }}</text>
        <text class="arrow">▼</text>
      </view>
      <view class="filter-item" @click="showBindFilter = !showBindFilter">
        <text>{{ currentBindLabel }}</text>
        <text class="arrow">▼</text>
      </view>
    </view>

    <!-- 房间筛选下拉 -->
    <view class="dropdown-mask" v-if="showRoomFilter" @click="showRoomFilter = false">
      <view class="dropdown-list" @click.stop>
        <view class="dropdown-item" :class="{ active: room_id === item.value }" v-for="item in roomList" :key="item.value" @click="onRoomChange(item.value)">
          {{ item.text }}
        </view>
      </view>
    </view>

    <!-- 类型筛选下拉 -->
    <view class="dropdown-mask" v-if="showTypeFilter" @click="showTypeFilter = false">
      <view class="dropdown-list" @click.stop>
        <view class="dropdown-item" :class="{ active: device_type === item.value }" v-for="item in deviceTypes" :key="item.value" @click="onTypeChange(item.value)">
          {{ item.label }}
        </view>
      </view>
    </view>

    <!-- 绑定状态筛选下拉 -->
    <view class="dropdown-mask" v-if="showBindFilter" @click="showBindFilter = false">
      <view class="dropdown-list" @click.stop>
        <view class="dropdown-item" :class="{ active: bind_status === item.value }" v-for="item in bindStatusList" :key="item.value" @click="onBindChange(item.value)">
          {{ item.label }}
        </view>
      </view>
    </view>

    <!-- 设备列表 -->
    <view class="device-list" v-if="deviceList.length > 0">
      <view class="device-item" v-for="item in deviceList" :key="item.id">
        <view class="device-header">
          <view class="device-name">{{ item.device_name || item.type_name }}</view>
          <view class="device-status" :class="item.online_status == 1 ? 'online' : 'offline'">
            {{ item.online_status == 1 ? '在线' : '离线' }}
          </view>
        </view>
        <view class="device-sn">SN: {{ item.device_sn }}</view>
        <view class="device-info">
          <view class="info-item">
            <text class="label">类型：</text>
            <text class="value">{{ item.type_name }}</text>
          </view>
          <view class="info-item">
            <text class="label">房间：</text>
            <text class="value">{{ item.room_name || '未绑定' }}</text>
          </view>
        </view>
        <view class="device-actions">
          <view class="action-btn edit" @click="showEditDialog(item)">编辑</view>
          <view class="action-btn primary" v-if="item.device_type === 'lock'" @click="showLockOp(item)">智能锁操作</view>
          <view class="action-btn primary" v-if="item.type === 14" @click="checkBall(item.id)">检测归还</view>
          <view class="action-btn primary" v-if="item.type === 11" @click="configQrPrice(item.device_sn)">配置价格</view>
          <view class="action-btn danger" @click="delDevice(item.id)">删除</view>
        </view>
      </view>
    </view>

    <!-- 空状态 -->
    <view class="empty-state" v-else>
      <text>暂无设备</text>
    </view>

    <!-- 加载更多 -->
    <view class="load-more">
      <text v-if="loadStatus === 'loading'">加载中...</text>
      <text v-else-if="loadStatus === 'nomore'">没有更多了</text>
      <text v-else @click="getDeviceList()">加载更多</text>
    </view>

    <!-- 添加设备按钮 -->
    <view class="add-btn">
      <view class="add-btn-inner" @click="showAddDialog">添加设备</view>
    </view>

    <!-- 添加设备弹窗 -->
    <u-popup :show="showAdd" mode="center" @close="showAdd = false">
      <view class="add-dialog">
        <view class="dialog-title">添加设备</view>
        <view class="dialog-content">
          <view class="field-item">
            <view class="field-label">设备编号 *</view>
            <input class="field-input" v-model="deviceNo" placeholder="如：GW-20250214-0001" />
          </view>
          <view class="field-item">
            <view class="field-label">设备密钥 *</view>
            <input class="field-input" v-model="deviceKey" placeholder="如：sk_abc123xyz" />
          </view>
          <view class="field-item" @click="showDeviceTypePicker = true">
            <view class="field-label">设备类型 *</view>
            <view class="field-link">
              <text>{{ selectedDeviceType }}</text>
              <text class="link-arrow">›</text>
            </view>
          </view>
          <view class="field-item" @click="showRoomPicker = true">
            <view class="field-label">绑定房间</view>
            <view class="field-link">
              <text>{{ selectedRoom }}</text>
              <text class="link-arrow">›</text>
            </view>
          </view>
        </view>
        <view class="dialog-footer">
          <view class="dialog-btn" @click="cancelAdd">取消</view>
          <view class="dialog-btn primary" @click="submitAdd">确定</view>
        </view>
      </view>
    </u-popup>

    <!-- 设备类型选择器 -->
    <u-popup :show="showDeviceTypePicker" mode="bottom" @close="showDeviceTypePicker = false">
      <view class="picker-box">
        <view class="picker-header">
          <text @click="showDeviceTypePicker = false">取消</text>
          <text class="picker-title">选择设备类型</text>
          <text class="picker-confirm" @click="showDeviceTypePicker = false">确定</text>
        </view>
        <scroll-view scroll-y class="picker-list">
          <view class="picker-item" :class="{ active: selectedDeviceTypeIndex === index }" v-for="(item, index) in deviceTypes" :key="index" @click="selectedDeviceTypeIndex = index">
            {{ item.label }}
          </view>
        </scroll-view>
      </view>
    </u-popup>

    <!-- 房间选择器 -->
    <u-popup :show="showRoomPicker" mode="bottom" @close="showRoomPicker = false">
      <view class="picker-box">
        <view class="picker-header">
          <text @click="showRoomPicker = false">取消</text>
          <text class="picker-title">选择房间</text>
          <text class="picker-confirm" @click="showRoomPicker = false">确定</text>
        </view>
        <scroll-view scroll-y class="picker-list">
          <view class="picker-item" :class="{ active: selectedRoomIndex === index }" v-for="(item, index) in roomList" :key="index" @click="selectedRoomIndex = index">
            {{ item.text }}
          </view>
        </scroll-view>
      </view>
    </u-popup>

    <!-- 智能锁操作弹窗 -->
    <u-popup :show="showLockOpDialog" mode="center" @close="showLockOpDialog = false">
      <view class="lock-dialog">
        <view class="dialog-title">智能锁操作</view>
        <view class="lock-actions">
          <view class="lock-btn" @click="unlock">开锁</view>
          <view class="lock-btn" @click="oplock">关锁</view>
          <view class="lock-btn" @click="queryLockPwd">查询密码</view>
          <view class="lock-btn" @click="setLockPwdShow">设置密码</view>
          <view class="lock-btn" @click="addLockCard">添加卡片</view>
          <view class="lock-btn" @click="updateLockTime">同步时间</view>
          <view class="lock-btn" @click="lockConfigWifi">配置WiFi</view>
          <view class="lock-btn" @click="lockAotuOpen">设置常开</view>
          <view class="lock-btn" @click="lockAotuClose">设置自动关</view>
          <view class="lock-btn warn" @click="initLock">初始化</view>
          <view class="lock-btn danger" @click="resetLock">恢复出厂</view>
        </view>
        <view class="dialog-footer">
          <view class="dialog-btn" @click="closeLockOp">关闭</view>
        </view>
      </view>
    </u-popup>

    <!-- 二维码识别器配置弹窗 -->
    <u-popup :show="showQrConfig" mode="center" @close="showQrConfig = false">
      <view class="qr-dialog">
        <view class="dialog-title">配置二维码识别器</view>
        <view class="dialog-content">
          <view class="field-item">
            <view class="field-label">时长(小时)</view>
            <input class="field-input" v-model="qrHour" type="number" placeholder="请输入时长" />
          </view>
          <view class="field-item">
            <view class="field-label">价格(元)</view>
            <input class="field-input" v-model="qrPrice" type="number" placeholder="请输入价格" />
          </view>
        </view>
        <view class="dialog-footer">
          <view class="dialog-btn" @click="closeQrConfig">取消</view>
          <view class="dialog-btn primary" @click="saveQrConfig">保存</view>
        </view>
      </view>
    </u-popup>

    <!-- 编辑设备弹窗 -->
    <u-popup :show="showEdit" mode="center" @close="showEdit = false">
      <view class="add-dialog">
        <view class="dialog-title">编辑设备</view>
        <view class="dialog-content">
          <view class="field-item">
            <view class="field-label">设备编号</view>
            <view class="field-value">{{ editDevice.device_no }}</view>
          </view>
          <view class="field-item">
            <view class="field-label">设备名称</view>
            <input class="field-input" v-model="editDevice.device_name" placeholder="请输入设备名称" />
          </view>
          <view class="field-item" @click="showEditRoomPicker = true">
            <view class="field-label">绑定房间</view>
            <view class="field-link">
              <text>{{ editSelectedRoom }}</text>
              <text class="link-arrow">›</text>
            </view>
          </view>
        </view>
        <view class="dialog-footer">
          <view class="dialog-btn" @click="cancelEdit">取消</view>
          <view class="dialog-btn primary" @click="submitEdit">保存</view>
        </view>
      </view>
    </u-popup>

    <!-- 编辑房间选择器 -->
    <u-popup :show="showEditRoomPicker" mode="bottom" @close="showEditRoomPicker = false">
      <view class="picker-box">
        <view class="picker-header">
          <text @click="showEditRoomPicker = false">取消</text>
          <text class="picker-title">选择房间</text>
          <text class="picker-confirm" @click="showEditRoomPicker = false">确定</text>
        </view>
        <scroll-view scroll-y class="picker-list">
          <view class="picker-item" :class="{ active: editSelectedRoomIndex === index }" v-for="(item, index) in roomList" :key="index" @click="editSelectedRoomIndex = index">
            {{ item.text }}
          </view>
        </scroll-view>
      </view>
    </u-popup>
  </view>
</template>

<script>
import http from '@/utils/http.js'
import lock from '@/utils/lock.js'

export default {
  data() {
    return {
      store_id: '',
      storeName: '',
      room_id: '',
      device_type: '',
      bind_status: '',
      showRoomFilter: false,
      showTypeFilter: false,
      showBindFilter: false,
      roomList: [],
      deviceTypes: [
        { label: '全部类型', value: '' },
        { label: '房间网关', value: 'gateway' },
        { label: '智能门锁', value: 'lock' },
        { label: '空调控制器', value: 'kt' },
        { label: '灯光控制器', value: 'light' },
        { label: '麻将机控制器', value: 'mahjong' }
      ],
      bindStatusList: [
        { label: '全部状态', value: '' },
        { label: '已绑定房间', value: '1' },
        { label: '未绑定房间', value: '0' }
      ],
      deviceList: [],
      pageNo: 1,
      pageSize: 10,
      canLoadMore: true,
      loadStatus: 'loadmore',
      showAdd: false,
      deviceNo: '',
      deviceKey: '',
      selectedDeviceTypeIndex: 0,
      selectedRoomIndex: 0,
      showDeviceTypePicker: false,
      showRoomPicker: false,
      showLockOpDialog: false,
      lock_data: '',
      lockSn: '',
      showQrConfig: false,
      qrSn: '',
      qrHour: '',
      qrPrice: '',
      showEdit: false,
      editDevice: {},
      editSelectedRoomIndex: 0,
      showEditRoomPicker: false
    }
  },
  computed: {
    currentRoomLabel() {
      const found = this.roomList.find(r => r.value === this.room_id)
      return found ? found.text : '房间筛选'
    },
    currentTypeLabel() {
      const found = this.deviceTypes.find(t => t.value === this.device_type)
      return found ? found.label : '设备类型'
    },
    currentBindLabel() {
      const found = this.bindStatusList.find(t => t.value === this.bind_status)
      return found ? found.label : '绑定状态'
    },
    selectedDeviceType() {
      return this.deviceTypes[this.selectedDeviceTypeIndex]?.label || '请选择'
    },
    selectedRoom() {
      return this.roomList[this.selectedRoomIndex]?.text || '请选择'
    },
    editSelectedRoom() {
      return this.roomList[this.editSelectedRoomIndex]?.text || '请选择'
    }
  },
  async onLoad(options) {
    this.store_id = options.store_id || ''
    this.store_name = options.store_name || ''
    
    // 如果没有store_id，获取第一个门店
    if (!this.store_id || this.store_id === '0') {
      await this.getFirstStore()
    }
    
    this.getRoomList()
    this.getDeviceList('refresh')
  },
  onPullDownRefresh() {
    this.pageNo = 1
    this.deviceList = []
    this.canLoadMore = true
    this.getDeviceList('refresh')
    setTimeout(() => { uni.stopPullDownRefresh() }, 500)
  },
  onReachBottom() {
    if (this.canLoadMore) this.getDeviceList()
  },
  methods: {
    async getFirstStore() {
      try {
        const res = await http.get('/member/store/getStoreListByAdmin')
        const data = res.data || res || []
        if (data.length > 0) {
          this.store_id = data[0].value || data[0].id
          this.store_name = data[0].key || data[0].name
        }
      } catch (e) { console.log('getFirstStore error', e) }
    },
    async getRoomList() {
      console.log('getRoomList store_id:', this.store_id)
      if (!this.store_id) {
        this.roomList = [{ text: '请选择房间', value: '' }]
        return
      }
      try {
        const res = await http.get(`/member/store/getRoomList/${this.store_id}`)
        console.log('getRoomList res:', res)
        const data = res.data || res || []
        console.log('getRoomList data:', data)
        this.roomList = [
          { text: '请选择房间', value: '' },
          ...data.map(item => ({ text: item.name, value: item.id }))
        ]
        console.log('roomList:', this.roomList)
      } catch (e) { console.log('getRoomList error', e) }
    },
    async getDeviceList(type) {
      if (type === 'refresh') {
        this.pageNo = 1
        this.deviceList = []
        this.loadStatus = 'loading'
      }
      try {
        const res = await http.post('/member/device/getDevicePage', {
          pageNo: this.pageNo, pageSize: this.pageSize,
          device_type: this.device_type, store_id: this.store_id, room_id: this.room_id,
          bindStatus: this.bind_status
        })
        const data = res.data || res
        if (data.list && data.list.length > 0) {
          data.list.forEach(item => {
            // 适配新字段
            item.device_sn = item.device_no
            item.type_name = this.getTypeName(item.device_type)
          })
          this.deviceList = [...this.deviceList, ...data.list]
          this.pageNo++
          this.canLoadMore = this.deviceList.length < data.total
          this.loadStatus = this.canLoadMore ? 'loadmore' : 'nomore'
        } else {
          this.canLoadMore = false
          this.loadStatus = 'nomore'
        }
      } catch (e) { this.loadStatus = 'loadmore' }
    },
    getTypeName(deviceType) {
      const typeMap = {
        'gateway': '房间网关',
        'lock': '智能门锁',
        'kt': '空调控制器',
        'light': '灯光控制器',
        'mahjong': '麻将机控制器'
      }
      return typeMap[deviceType] || deviceType || '未知设备'
    },
    onRoomChange(val) {
      this.room_id = val
      this.showRoomFilter = false
      this.getDeviceList('refresh')
    },
    onTypeChange(val) {
      this.device_type = val
      this.showTypeFilter = false
      this.getDeviceList('refresh')
    },
    onBindChange(val) {
      this.bind_status = val
      this.showBindFilter = false
      this.getDeviceList('refresh')
    },
    showAddDialog() { this.showAdd = true },
    async submitAdd() {
      if (!this.deviceNo) { uni.showToast({ title: '请输入设备编号', icon: 'none' }); return }
      if (!this.deviceKey) { uni.showToast({ title: '请输入设备密钥', icon: 'none' }); return }
      if (this.selectedDeviceTypeIndex === 0) { uni.showToast({ title: '请选择设备类型', icon: 'none' }); return }
      try {
        await http.post('/member/device/register', {
          device_no: this.deviceNo,
          device_key: this.deviceKey,
          device_type: this.deviceTypes[this.selectedDeviceTypeIndex].value,
          store_id: this.store_id,
          room_id: this.roomList[this.selectedRoomIndex]?.value || 0
        })
        uni.showToast({ title: '添加成功', icon: 'success' })
        this.cancelAdd()
        this.getDeviceList('refresh')
      } catch (e) { uni.showToast({ title: e.msg || '添加失败', icon: 'none' }) }
    },
    cancelAdd() {
      this.showAdd = false
      this.deviceNo = ''
      this.deviceKey = ''
      this.selectedDeviceTypeIndex = 0
      this.selectedRoomIndex = 0
    },
    delDevice(deviceId) {
      uni.showModal({
        title: '提示', content: '确定删除此设备吗？',
        success: async (res) => {
          if (res.confirm) {
            try {
              await http.post('/member/device/delete', { id: deviceId })
              uni.showToast({ title: '删除成功', icon: 'success' })
              this.getDeviceList('refresh')
            } catch (e) { uni.showToast({ title: e.msg || '删除失败', icon: 'none' }) }
          }
        }
      })
    },
    checkBall(deviceId) {
      uni.showModal({
        title: '提示', content: '请将台球全部放入锁球器/锁球柜中，不要堆叠摆放台球！',
        success: async (res) => {
          if (res.confirm) {
            uni.showLoading({ title: '检测中...' })
            try {
              const result = await http.post(`/member/store/checkBall/${deviceId}`)
              uni.hideLoading()
              uni.showModal({ title: '提示', content: result ? '全部球已正常归还' : '归还检测不通过！请检查球是否归还完毕，不要堆叠摆放台球！', showCancel: false })
            } catch (e) { uni.hideLoading(); uni.showToast({ title: '检测失败', icon: 'none' }) }
          }
        }
      })
    },
    showLockOp(item) { this.lock_data = item.lock_data; this.lockSn = item.device_sn; this.showLockOpDialog = true },
    closeLockOp() { this.showLockOpDialog = false; this.lock_data = ''; this.lockSn = '' },
    unlock() { if (this.lock_data) lock.blueDoorOpen(this.lock_data) },
    oplock() { if (this.lock_data) lock.blueCloseOpen(this.lock_data) },
    queryLockPwd() { if (this.lock_data) lock.queryLockPwd(this.lock_data) },
    setLockPwdShow() {
      uni.showModal({
        title: '设置门锁密码', editable: true, placeholderText: '请输入6-8位纯数字密码',
        success: (res) => {
          if (res.confirm && res.content) {
            const pwd = res.content
            if (pwd.length >= 6 && pwd.length <= 8 && /^\d+$/.test(pwd)) { lock.setLockPwd(this.lock_data, pwd) }
            else { uni.showToast({ title: '密码格式不正确', icon: 'none' }) }
          }
        }
      })
    },
    addLockCard() { if (this.lock_data) lock.addCard(this.lock_data) },
    updateLockTime() { if (this.lock_data) { uni.showLoading({ title: '请靠近门锁' }); lock.updateLockTime(this.lock_data, this.lockSn) } },
    lockConfigWifi() {
      uni.showModal({ title: '提示', content: '仅带Wifi功能的智能锁支持，您是否确认配置？',
        success: (res) => { if (res.confirm) uni.navigateTo({ url: `/pagesA/device/config-lock-wifi?lockData=${this.lock_data}` }) }
      })
    },
    lockAotuOpen() {
      uni.showModal({ title: '提示', content: '请打开手机蓝牙，靠近门锁操作！锁常开=开锁后不会自动关锁，除非收到关锁指令。您确认设置锁常开吗？',
        success: (res) => { if (res.confirm) { uni.showLoading({ title: '请靠近门锁' }); lock.setAotuLockTime(this.lock_data, 0) } }
      })
    },
    lockAotuClose() {
      uni.showModal({ title: '提示', content: '请打开手机蓝牙，靠近门锁操作！锁自动关=每次开锁10秒后，锁会自动关闭。您确认设置锁自动关吗？',
        success: (res) => { if (res.confirm) { uni.showLoading({ title: '请靠近门锁' }); lock.setAotuLockTime(this.lock_data, 10) } }
      })
    },
    initLock() {
      uni.showModal({ title: '提示', content: '仅当门锁发出"请添加管理员"时，门锁才可以被初始化！一般用于第一次安装锁使用，正常使用的门锁不需要初始化！',
        success: (res) => { if (res.confirm) uni.showModal({ title: '警告', content: '您是否确认初始化此智能锁？', success: (res2) => { if (res2.confirm) lock.initLock(this.lock_data) } }) }
      })
    },
    resetLock() {
      uni.showModal({ title: '重要提示', content: '恢复出厂将还原锁未初始状态! 系统将不能控制,可再次通过初始化门锁重新恢复。',
        success: (res) => { if (res.confirm) uni.showModal({ title: '提示', content: '您是否确认将此智能锁恢复出厂设置?', success: (res2) => { if (res2.confirm) { uni.showLoading({ title: '请靠近门锁' }); lock.handleResetLock(this.lock_data) } } }) }
      })
    },
    async configQrPrice(sn) {
      try {
        const res = await http.post('/member/store/getQrConfig', { sn })
        this.qrSn = sn; this.qrHour = res.qr_hour || ''; this.qrPrice = res.qr_price || ''; this.showQrConfig = true
      } catch (e) { uni.showToast({ title: e.msg || '获取配置失败', icon: 'none' }) }
    },
    closeQrConfig() { this.showQrConfig = false; this.qrSn = ''; this.qrHour = ''; this.qrPrice = '' },
    async saveQrConfig() {
      if (!this.qrHour || !this.qrPrice) { uni.showToast({ title: '请输入价格和时长', icon: 'none' }); return }
      try {
        await http.post('/member/store/setQrConfig', { sn: this.qrSn, qr_hour: this.qrHour, qr_price: this.qrPrice })
        uni.showToast({ title: '操作成功', icon: 'success' }); this.closeQrConfig()
      } catch (e) { uni.showToast({ title: e.msg || '保存失败', icon: 'none' }) }
    },
    showEditDialog(item) {
      this.editDevice = { ...item }
      // 找到当前绑定的房间索引
      const idx = this.roomList.findIndex(r => r.value === item.room_id)
      this.editSelectedRoomIndex = idx >= 0 ? idx : 0
      this.showEdit = true
    },
    cancelEdit() {
      this.showEdit = false
      this.editDevice = {}
      this.editSelectedRoomIndex = 0
    },
    async submitEdit() {
      try {
        await http.post('/member/device/save', {
          id: this.editDevice.id,
          device_name: this.editDevice.device_name,
          room_id: this.roomList[this.editSelectedRoomIndex]?.value || 0
        })
        uni.showToast({ title: '保存成功', icon: 'success' })
        this.cancelEdit()
        this.getDeviceList('refresh')
      } catch (e) { uni.showToast({ title: e.msg || '保存失败', icon: 'none' }) }
    }
  }
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; padding-bottom: 120rpx; }
.filter-bar { display: flex; background: #fff; margin-bottom: 20rpx; }
.filter-item { flex: 1; display: flex; align-items: center; justify-content: center; padding: 24rpx 0; font-size: 28rpx; color: #333; }
.filter-item .arrow { font-size: 20rpx; margin-left: 8rpx; color: #999; }
.dropdown-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); z-index: 999; }
.dropdown-list { background: #fff; max-height: 60vh; overflow-y: auto; margin-top: 88rpx; }
.dropdown-item { padding: 24rpx 32rpx; font-size: 28rpx; color: #333; border-bottom: 1rpx solid #f5f5f5; }
.dropdown-item.active { color: var(--main-color, #5AAB6E); font-weight: bold; }
.device-list { padding: 20rpx; }
.device-item { background: #fff; border-radius: 16rpx; padding: 24rpx; margin-bottom: 20rpx; }
.device-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8rpx; }
.device-header .device-name { font-size: 32rpx; font-weight: bold; color: #333; }
.device-header .device-status { font-size: 22rpx; padding: 4rpx 16rpx; border-radius: 20rpx; }
.device-header .device-status.online { background: #e6f7ed; color: #52c41a; }
.device-header .device-status.offline { background: #f5f5f5; color: #999; }
.device-sn { font-size: 24rpx; color: #999; margin-bottom: 16rpx; }
.device-info { margin-bottom: 16rpx; }
.device-info .info-item { font-size: 26rpx; color: #666; margin-bottom: 8rpx; }
.device-info .label { color: #999; }
.device-info .tag { display: inline-block; padding: 4rpx 12rpx; background: #fff7e6; color: #fa8c16; border-radius: 6rpx; font-size: 22rpx; }
.device-actions { display: flex; gap: 12rpx; flex-wrap: wrap; }
.action-btn { padding: 12rpx 24rpx; border-radius: 8rpx; font-size: 24rpx; text-align: center; }
.action-btn.edit { background: #e6f7ff; color: #1890ff; border: 1rpx solid #1890ff; }
.action-btn.primary { background: #e6f7ed; color: var(--main-color, #5AAB6E); border: 1rpx solid var(--main-color, #5AAB6E); }
.action-btn.danger { background: #fff1f0; color: #ff4d4f; border: 1rpx solid #ff4d4f; }
.empty-state { text-align: center; padding: 120rpx 0; color: #999; font-size: 28rpx; }
.load-more { text-align: center; padding: 20rpx; font-size: 26rpx; color: #999; }
.add-btn { position: fixed; bottom: 20rpx; left: 20rpx; right: 20rpx; z-index: 100; }
.add-btn-inner { background: var(--main-color, #5AAB6E); color: #fff; text-align: center; padding: 24rpx; border-radius: 12rpx; font-size: 30rpx; }
.add-dialog, .lock-dialog, .qr-dialog { width: 600rpx; background: #fff; border-radius: 20rpx; overflow: hidden; }
.dialog-title { padding: 32rpx; text-align: center; font-size: 32rpx; font-weight: bold; color: #333; border-bottom: 1rpx solid #f5f5f5; }
.dialog-content { padding: 32rpx; }
.field-item { padding: 20rpx 0; border-bottom: 1rpx solid #f5f5f5; display: flex; justify-content: space-between; align-items: center; }
.field-label { font-size: 28rpx; color: #333; }
.field-input { flex: 1; margin-left: 20rpx; font-size: 28rpx; text-align: right; }
.field-value { flex: 1; margin-left: 20rpx; font-size: 28rpx; text-align: right; color: #999; }
.field-link { display: flex; align-items: center; }
.field-link text { font-size: 28rpx; color: #999; }
.link-arrow { font-size: 36rpx; margin-left: 8rpx; color: #ccc; }
.lock-actions { padding: 32rpx; display: grid; grid-template-columns: repeat(2, 1fr); gap: 16rpx; }
.lock-btn { background: var(--main-color, #5AAB6E); color: #fff; text-align: center; padding: 20rpx; border-radius: 10rpx; font-size: 26rpx; }
.lock-btn.warn { background: #ff9800; }
.lock-btn.danger { background: #ff4d4f; }
.dialog-footer { display: flex; gap: 20rpx; padding: 20rpx 32rpx 32rpx; }
.dialog-btn { flex: 1; text-align: center; padding: 20rpx; border-radius: 10rpx; font-size: 28rpx; background: #f5f5f5; color: #666; }
.dialog-btn.primary { background: var(--main-color, #5AAB6E); color: #fff; }
.picker-box { background: #fff; border-radius: 20rpx 20rpx 0 0; }
.picker-header { display: flex; justify-content: space-between; align-items: center; padding: 24rpx 32rpx; border-bottom: 1rpx solid #f5f5f5; font-size: 28rpx; color: #999; }
.picker-title { font-size: 30rpx; color: #333; font-weight: bold; }
.picker-confirm { color: var(--main-color, #5AAB6E); }
.picker-list { max-height: 500rpx; }
.picker-item { padding: 24rpx 32rpx; font-size: 28rpx; color: #333; border-bottom: 1rpx solid #f5f5f5; }
.picker-item.active { color: var(--main-color, #5AAB6E); font-weight: bold; }
</style>
