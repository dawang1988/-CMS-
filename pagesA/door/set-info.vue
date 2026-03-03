<template>
  <view class="page" :style="themeStyle">
    <view class="form-card">
      <view class="form-item">
        <text class="label required">房间名称</text>
        <input class="input" placeholder="请输入房间名称" v-model="formData.name" />
      </view>

      <view class="form-item">
        <text class="label required">房间类型</text>
        <picker mode="selector" :range="roomTypes" range-key="label" @change="onTypeChange">
          <view class="picker">{{ getRoomTypeLabel(formData.type) }}</view>
        </picker>
      </view>

      <view class="form-item">
        <text class="label">房间类别</text>
        <picker mode="selector" :range="roomClasses" range-key="label" @change="onClassChange">
          <view class="picker">{{ getRoomClassLabel(formData.room_class) }}</view>
        </picker>
      </view>

      <view class="form-item">
        <text class="label">房间编号</text>
        <input class="input" placeholder="请输入房间编号" v-model="formData.room_no" />
      </view>

      <view class="form-item">
        <text class="label required">小时价格</text>
        <input class="input" type="digit" placeholder="请输入小时价格" v-model="formData.price" />
      </view>

      <view class="form-item">
        <text class="label">工作日价格</text>
        <input class="input" type="digit" placeholder="请输入工作日价格" v-model="formData.work_price" />
      </view>

      <view class="form-item">
        <text class="label">上午场价格</text>
        <input class="input" type="digit" placeholder="请输入上午场价格" v-model="formData.morning_price" />
      </view>

      <view class="form-item">
        <text class="label">下午场价格</text>
        <input class="input" type="digit" placeholder="请输入下午场价格" v-model="formData.afternoon_price" />
      </view>

      <view class="form-item">
        <text class="label">夜间场价格</text>
        <input class="input" type="digit" placeholder="请输入夜间场价格" v-model="formData.night_price" />
      </view>

      <view class="form-item">
        <text class="label">通宵场价格</text>
        <input class="input" type="digit" placeholder="请输入通宵场价格" v-model="formData.tx_price" />
      </view>

      <view class="form-item">
        <text class="label">最低消费(小时)</text>
        <input class="input" type="number" placeholder="请输入最低消费小时" v-model="formData.min_hour" />
      </view>

      <view class="form-item">
        <text class="label">押金</text>
        <input class="input" type="digit" placeholder="请输入押金金额" v-model="formData.deposit" />
      </view>

      <view class="form-item">
        <text class="label">排序</text>
        <input class="input" type="number" placeholder="数字越小越靠前" v-model="formData.sort" />
      </view>

      <view class="form-item">
        <text class="label">房间标签</text>
        <input class="input" placeholder="多个标签用逗号分隔" v-model="formData.label" />
      </view>

      <view class="form-item">
        <text class="label">门锁编号</text>
        <input class="input" placeholder="请输入门锁编号" v-model="formData.lock_no" />
      </view>

      <!-- 电器设备配置 -->
      <view class="form-item column">
        <text class="label">电器设备配置</text>
        <view class="device-tip">开门时自动通电的设备</view>
        <view class="device-list">
          <view class="device-item" @tap="toggleDevice('lock')">
            <view class="device-icon" :class="{active: deviceConfig.lock}">🔒</view>
            <text class="device-name">门锁</text>
            <view class="device-check" :class="{checked: deviceConfig.lock}">
              <text v-if="deviceConfig.lock">✓</text>
            </view>
          </view>
          <view class="device-item" @tap="toggleDevice('light')">
            <view class="device-icon" :class="{active: deviceConfig.light}">💡</view>
            <text class="device-name">灯光</text>
            <view class="device-check" :class="{checked: deviceConfig.light}">
              <text v-if="deviceConfig.light">✓</text>
            </view>
          </view>
          <view class="device-item" @tap="toggleDevice('ac')">
            <view class="device-icon" :class="{active: deviceConfig.ac}">❄️</view>
            <text class="device-name">空调</text>
            <view class="device-check" :class="{checked: deviceConfig.ac}">
              <text v-if="deviceConfig.ac">✓</text>
            </view>
          </view>
          <view class="device-item" @tap="toggleDevice('mahjong')">
            <view class="device-icon" :class="{active: deviceConfig.mahjong}">🀄</view>
            <text class="device-name">麻将机</text>
            <view class="device-check" :class="{checked: deviceConfig.mahjong}">
              <text v-if="deviceConfig.mahjong">✓</text>
            </view>
          </view>
        </view>
      </view>

      <view class="form-item column">
        <text class="label">房间图片</text>
        <view class="image-list">
          <view class="image-item" v-for="(img, index) in imageList" :key="index">
            <image class="img" :src="img" mode="aspectFill"></image>
            <view class="delete-btn" @tap="deleteImage(index)">
              <text style="font-size:24rpx;color:#fff;">✕</text>
            </view>
          </view>
          <view class="upload-btn" @tap="chooseImage" v-if="imageList.length < 9">
            <text style="font-size:50rpx;color:#999;">+</text>
          </view>
        </view>
      </view>

      <view class="form-item">
        <text class="label">状态</text>
        <picker mode="selector" :range="statusList" range-key="label" :value="statusIndex" @change="onStatusChange">
          <view class="picker">{{ getStatusLabel(formData.status) }}</view>
        </picker>
      </view>

      <view class="form-item column">
        <text class="label">房间描述</text>
        <textarea 
          class="textarea" 
          placeholder="请输入房间描述" 
          v-model="formData.description"
          maxlength="500"
        ></textarea>
      </view>
    </view>

    <view class="submit-btn">
      <button class="btn" @tap="submit">保存</button>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http'

export default {
  data() {
    return {
      store_id: 0,
      room_id: 0,
      formData: {
        name: '',
        type: '',
        room_class: 0,
        room_no: '',
        price: '',
        work_price: '',
        morning_price: '',
        afternoon_price: '',
        night_price: '',
        tx_price: '',
        min_hour: 1,
        deposit: '',
        label: '',
        lock_no: '',
        sort: 0,
        images: '',
        description: '',
        status: 1,
        device_config: ''
      },
      deviceConfig: {
        lock: true,
        light: true,
        ac: true,
        mahjong: false
      },
      imageList: [],
      roomTypes: [
        { value: '特价包', label: '特价包' },
        { value: '小包', label: '小包' },
        { value: '中包', label: '中包' },
        { value: '大包', label: '大包' },
        { value: '豪包', label: '豪包' },
        { value: '商务包', label: '商务包' },
        { value: '斯洛克', label: '斯洛克' },
        { value: '中式黑八', label: '中式黑八' },
        { value: '美式球桌', label: '美式球桌' }
      ],
      roomClasses: [
        { value: 0, label: '棋牌' },
        { value: 1, label: '台球' },
        { value: 2, label: 'KTV' }
      ],
      // 数据库定义：0=停用, 1=空闲, 2=使用中, 3=维护中, 4=待清洁/已预约
      statusList: [
        { value: 1, label: '空闲' },
        { value: 2, label: '使用中' },
        { value: 3, label: '维护中' },
        { value: 4, label: '待清洁/已预约' },
        { value: 0, label: '停用' }
      ]
    }
  },

  computed: {
    statusIndex() {
      const idx = this.statusList.findIndex(s => s.value === this.formData.status)
      return idx >= 0 ? idx : 0
    }
  },

  onLoad(options) {
    this.store_id = Number(options.store_id)
    if (options.room_id) {
      this.room_id = Number(options.room_id)
      this.getRoomInfo()
    }
  },

  methods: {
    // 获取房间信息
    async getRoomInfo() {
      try {
        const res = await http.post(`/member/index/getRoomInfo/${this.room_id}`, {
          room_id: this.room_id
        })
        
        if (res.code === 0) {
          const data = res.data
          this.formData.name = data.name || ''
          this.formData.type = data.type || ''
          this.formData.room_class = data.room_class !== undefined ? Number(data.room_class) : 0
          this.formData.room_no = data.room_no || ''
          this.formData.price = data.price || ''
          this.formData.work_price = data.work_price || ''
          this.formData.morning_price = data.morning_price || ''
          this.formData.afternoon_price = data.afternoon_price || ''
          this.formData.night_price = data.night_price || ''
          this.formData.tx_price = data.tx_price || ''
          this.formData.min_hour = data.min_hour || 1
          this.formData.deposit = data.deposit || ''
          this.formData.label = data.label || ''
          this.formData.lock_no = data.lock_no || ''
          this.formData.sort = data.sort || 0
          this.formData.description = data.description || ''
          this.formData.status = data.status !== undefined ? data.status : 1
          
          // 加载电器配置
          if (data.device_config) {
            try {
              const cfg = typeof data.device_config === 'string' ? JSON.parse(data.device_config) : data.device_config
              this.deviceConfig = {
                lock: cfg.lock !== false,
                light: cfg.light !== false,
                ac: cfg.ac !== false,
                mahjong: cfg.mahjong === true
              }
            } catch(e) {}
          }
          
          if (data.images) {
            const imgs = typeof data.images === 'string' ? data.images : JSON.stringify(data.images)
            try {
              const parsed = JSON.parse(imgs)
              this.imageList = Array.isArray(parsed) ? parsed.filter(img => img) : imgs.split(',').filter(img => img)
            } catch(e) {
              this.imageList = imgs.split(',').filter(img => img)
            }
          }
          // images 字段已由后端返回完整URL，如果是相对路径则补全
          const config = require('@/config/index.js').default || require('@/config/index.js')
          this.imageList = this.imageList.map(img => {
            if (img && img.indexOf('http') !== 0) {
              return config.baseUrl.replace('/app-api', '') + img
            }
            return img
          })
        }
      } catch (e) {
      }
    },

    // 选择类型
    onTypeChange(e) {
      this.formData.type = this.roomTypes[e.detail.value].value
    },

    // 获取类型标签
    getRoomTypeLabel(type) {
      const item = this.roomTypes.find(t => t.value === type)
      return item ? item.label : '请选择'
    },

    // 选择类别
    onClassChange(e) {
      this.formData.room_class = this.roomClasses[e.detail.value].value
    },

    // 获取类别标签
    getRoomClassLabel(roomClass) {
      const item = this.roomClasses.find(t => t.value === roomClass)
      return item ? item.label : '请选择'
    },

    // 选择状态
    onStatusChange(e) {
      this.formData.status = this.statusList[e.detail.value].value
    },

    // 获取状态标签
    getStatusLabel(status) {
      const item = this.statusList.find(s => s.value === status)
      return item ? item.label : '空闲'
    },

    // 切换设备开关
    toggleDevice(device) {
      this.deviceConfig[device] = !this.deviceConfig[device]
    },

    // 选择图片
    chooseImage() {
      uni.chooseImage({
        count: 9 - this.imageList.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (res) => {
          this.uploadImages(res.tempFilePaths)
        }
      })
    },

    // 上传单个文件
    uploadFile(filePath) {
      const config = require('@/config/index.js').default || require('@/config/index.js')
      return new Promise((resolve, reject) => {
        uni.uploadFile({
          url: config.baseUrl + '/infra/file/upload',
          filePath: filePath,
          name: 'file',
          header: {
            'tenant-id': config.tenantId,
            'Authorization': 'Bearer ' + (uni.getStorageSync('token') || '')
          },
          success: (res) => {
            try {
              const data = JSON.parse(res.data)
              if (data.code === 0 && data.data && data.data.url) {
                resolve(data.data.url)
              } else {
                reject(data.msg || '上传失败')
              }
            } catch (e) {
              reject('上传失败')
            }
          },
          fail: (err) => {
            reject(err.errMsg || '上传失败')
          }
        })
      })
    },

    // 上传图片
    async uploadImages(filePaths) {
      uni.showLoading({ title: '上传中...' })
      
      for (let filePath of filePaths) {
        try {
          const url = await this.uploadFile(filePath)
          this.imageList.push(url)
        } catch (e) {
          uni.showToast({ title: e || '上传失败', icon: 'none' })
        }
      }
      
      uni.hideLoading()
    },

    // 删除图片
    deleteImage(index) {
      this.imageList.splice(index, 1)
    },

    // 提交
    async submit() {
      // 表单验证
      if (!this.formData.name) {
        uni.showToast({
          title: '请输入房间名称',
          icon: 'none'
        })
        return
      }

      if (!this.formData.price) {
        uni.showToast({
          title: '请输入小时价格',
          icon: 'none'
        })
        return
      }

      try {
        uni.showLoading({ title: '保存中...' })
        
        this.formData.images = JSON.stringify(this.imageList)
        this.formData.device_config = JSON.stringify(this.deviceConfig)
        this.formData.store_id = this.store_id
        if (this.room_id) {
          this.formData.id = this.room_id
        }
        
        const url = this.room_id 
          ? `/member/store/updateRoomInfo`
          : `/member/store/saveRoomInfo`
        
        const res = await http.post(url, this.formData)
        
        uni.hideLoading()
        
        if (res.code === 0) {
          uni.showToast({
            title: '保存成功',
            icon: 'success'
          })
          setTimeout(() => {
            uni.navigateBack()
          }, 1000)
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({
          title: e.msg || '保存失败',
          icon: 'none'
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
  padding-bottom: 120rpx;
}

.form-card {
  background: #fff;
  margin: 20rpx;
  border-radius: 20rpx;
  padding: 30rpx;
}

.form-item {
  display: flex;
  align-items: center;
  padding: 25rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}

.form-item.column {
  flex-direction: column;
  align-items: flex-start;
}

.form-item:last-child {
  border-bottom: none;
}

.label {
  width: 200rpx;
  font-size: 28rpx;
  color: #333;
}

.label.required::before {
  content: '*';
  color: #f5222d;
  margin-right: 4rpx;
}

.input {
  flex: 1;
  height: 70rpx;
  font-size: 28rpx;
}

.picker {
  flex: 1;
  height: 70rpx;
  line-height: 70rpx;
  font-size: 28rpx;
  color: #333;
}

.textarea {
  width: 100%;
  min-height: 200rpx;
  padding: 20rpx;
  background: #f5f5f5;
  border-radius: 10rpx;
  font-size: 28rpx;
  margin-top: 20rpx;
}

.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
  margin-top: 20rpx;
}

.image-item {
  position: relative;
  width: 200rpx;
  height: 200rpx;
}

.img {
  width: 100%;
  height: 100%;
  border-radius: 10rpx;
}

.delete-btn {
  position: absolute;
  top: -10rpx;
  right: -10rpx;
  width: 40rpx;
  height: 40rpx;
  background: #f5222d;
  border-radius: 20rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-btn {
  width: 200rpx;
  height: 200rpx;
  background: #f5f5f5;
  border-radius: 10rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2rpx dashed #ddd;
}

/* 电器设备配置样式 */
.device-tip {
  font-size: 24rpx;
  color: #999;
  margin: 10rpx 0 20rpx;
}

.device-list {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
  width: 100%;
}

.device-item {
  width: calc(50% - 10rpx);
  display: flex;
  align-items: center;
  padding: 20rpx;
  background: #f8f8f8;
  border-radius: 12rpx;
  border: 2rpx solid #eee;
}

.device-icon {
  font-size: 40rpx;
  width: 60rpx;
  height: 60rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-radius: 10rpx;
  opacity: 0.5;
}

.device-icon.active {
  opacity: 1;
}

.device-name {
  flex: 1;
  font-size: 28rpx;
  color: #333;
  margin-left: 16rpx;
}

.device-check {
  width: 40rpx;
  height: 40rpx;
  border-radius: 20rpx;
  border: 2rpx solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24rpx;
  color: #fff;
}

.device-check.checked {
  background: #52c41a;
  border-color: #52c41a;
}

.submit-btn {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 20rpx;
  background: #fff;
  box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.05);
}

.btn {
  width: 100%;
  height: 88rpx;
  line-height: 88rpx;
  background: var(--main-color, #6da773fd);
  color: #fff;
  border-radius: 10rpx;
  font-size: 32rpx;
}
</style>
