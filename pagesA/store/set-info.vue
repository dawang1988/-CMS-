<template>
  <view class="container" :style="isIpx ? 'padding-bottom:168rpx' : 'padding-bottom:120rpx'">
    <view class="form">
      <!-- 门店名称 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">门店名称</view>
          <view class="cell-value">
            <input v-model="name" placeholder="请输入门店名称" />
          </view>
        </view>
      </view>
      
      <!-- 所在城市 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">所在城市</view>
          <view class="cell-value">
            <input v-model="city" placeholder="请输入所在城市" />
          </view>
        </view>
      </view>
      
      <!-- 详细地址 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">详细地址</view>
          <view class="cell-value">
            <textarea v-model="address" placeholder="请输入详细地址" auto-height />
          </view>
        </view>
      </view>
      
      <!-- 客服电话 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">客服电话</view>
          <view class="cell-value">
            <input v-model="phone" placeholder="请输入" />
          </view>
        </view>
      </view>
      
      <!-- 营业时间 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">营业时间</view>
          <view class="cell-value">
            <input v-model="business_hours" placeholder="例如：00:00-24:00" />
          </view>
        </view>
      </view>
      
      <!-- 经纬度 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">经度</view>
          <view class="cell-value">
            <input v-model="longitude" type="digit" placeholder="例如：116.397428" />
          </view>
        </view>
        <view class="cell">
          <view class="cell-label">纬度</view>
          <view class="cell-value">
            <input v-model="latitude" type="digit" placeholder="例如：39.90923" />
          </view>
        </view>
        <view class="tip">可通过地图工具获取经纬度坐标</view>
      </view>
      
      <!-- 门店描述 -->
      <view class="cell-group">
        <view class="cell" style="flex-direction:column;align-items:flex-start;">
          <view class="cell-label" style="margin-bottom:10rpx;">门店描述</view>
          <view class="cell-value" style="width:100%;">
            <textarea v-model="description" placeholder="请输入门店描述" auto-height style="width:100%;min-height:80rpx;" />
          </view>
        </view>
      </view>
      
      <!-- WIFI名称 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">WIFI名称</view>
          <view class="cell-value">
            <input v-model="wifi_name" placeholder="请输入" />
          </view>
        </view>
      </view>
      
      <!-- WIFI密码 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">WIFI密码</view>
          <view class="cell-value">
            <input v-model="wifi_password" placeholder="请输入" />
          </view>
        </view>
      </view>
      
      <!-- 通宵开始小时 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">通宵开始小时</view>
          <view class="cell-value">
            <input v-model="tx_start_hour" type="number" placeholder="请输入18-23的数字,默认23" />
          </view>
        </view>
      </view>
      
      <!-- 通宵时长 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">通宵时长(时)</view>
          <view class="cell-value">
            <input v-model="tx_hour" type="number" placeholder="请输入8-12的数字,默认8" />
          </view>
        </view>
        <view class="tip">例如通宵23时开始，时长9个小时，那么通宵场即23:00~08:00</view>
      </view>
      
      <!-- 延时5分钟灯光 -->
      <view class="cell-group">
        <view class="cell switch-cell">
          <view class="cell-label">延时5分钟灯光</view>
          <switch :checked="delay_light" @change="onChangeSwitch($event, 'delay_light')" />
        </view>
        <view class="tip">灯具需独立供电才可生效</view>
      </view>
      
      <!-- 清洁时间 -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">清洁时间(分)</view>
          <view class="cell-value">
            <input v-model="clear_time" type="number" placeholder="请输入5-30的数字,默认5" />
          </view>
        </view>
        <view class="tip">上个订单结束后，新订单的间隔下单时间</view>
      </view>
      
      <!-- 待清洁允许预订 -->
      <view class="cell-group">
        <view class="cell switch-cell">
          <view class="cell-label">待清洁允许预订</view>
          <switch :checked="clear_open" @change="onChangeSwitch($event, 'clear_open')" />
        </view>
        <view class="tip">关闭后如果房间未打扫，将不允许下单</view>
      </view>
      
      <!-- 消费中门禁常开 -->
      <view class="cell-group">
        <view class="cell switch-cell">
          <view class="cell-label">消费中门禁常开</view>
          <switch :checked="order_door_open" @change="onChangeSwitch($event, 'order_door_open')" />
        </view>
        <view class="tip">有订单时门随时可打开，密码锁还需要在设备列表去设置</view>
      </view>
      
      <!-- 保洁员任意开门 -->
      <view class="cell-group">
        <view class="cell switch-cell">
          <view class="cell-label">保洁员任意开门</view>
          <switch :checked="clear_open_door" @change="onChangeSwitch($event, 'clear_open_door')" />
        </view>
        <view class="tip">允许保洁员随时开电关电，即使房间无打扫任务</view>
      </view>
      
      <!-- 企业微信webhook -->
      <view class="cell-group">
        <view class="cell">
          <view class="cell-label">企业微信webhook</view>
          <view class="cell-value">
            <textarea v-model="order_webhook" placeholder="请输入" maxlength="200" auto-height />
          </view>
        </view>
        <view class="tip">用来接收下单通知、清洁通知、充值通知等</view>
      </view>
      
      <!-- 门店小程序码 -->
      <view class="line" v-if="qr_code">
        <label>门店小程序码</label>
        <view class="right">
          <image style="width:200rpx;height:200rpx;" :src="qr_code" @click="previewImage(qr_code)" mode="aspectFit" />
        </view>
      </view>
      
      <!-- 店铺门头照片 -->
      <view class="line">
        <label>店铺门头照片</label>
        <view class="right">
          <view class="upload-area">
            <view class="upload-list">
              <view class="upload-item" v-for="(item, index) in fileList1" :key="index">
                <image :src="item.url" mode="aspectFit" @click="previewImage(item.url)" />
                <view class="delete-btn" @click="deleteImage(1, index)">×</view>
              </view>
              <view class="upload-btn" v-if="fileList1.length < 1" @click="chooseImage(1)">
                <text class="upload-icon">+</text>
                <text class="upload-text">尺寸：377 x 508</text>
              </view>
            </view>
          </view>
        </view>
      </view>
      
      <!-- 门店顶部轮播广告 -->
      <view class="line">
        <label>门店顶部轮播广告</label>
        <view class="right">
          <view class="upload-area">
            <view class="upload-list">
              <view class="upload-item" v-for="(item, index) in fileList2" :key="index">
                <image :src="item.url" mode="aspectFit" @click="previewImage(item.url)" />
                <view class="delete-btn" @click="deleteImage(2, index)">×</view>
              </view>
              <view class="upload-btn" v-if="fileList2.length < 9" @click="chooseImage(2)">
                <text class="upload-icon">+</text>
              </view>
            </view>
          </view>
        </view>
      </view>
      
      <!-- 门店位置指引图 -->
      <view class="line">
        <label>门店位置指引图</label>
        <view class="right">
          <view class="upload-area">
            <view class="upload-list">
              <view class="upload-item" v-for="(item, index) in fileList3" :key="index">
                <image :src="item.url" mode="aspectFit" @click="previewImage(item.url)" />
                <view class="delete-btn" @click="deleteImage(3, index)">×</view>
              </view>
              <view class="upload-btn" v-if="fileList3.length < 9" @click="chooseImage(3)">
                <text class="upload-icon">+</text>
              </view>
            </view>
          </view>
        </view>
      </view>
      
      <!-- 门店首页简洁模式 -->
      <view class="cell-group">
        <view class="cell switch-cell">
          <view class="cell-label">门店首页简洁模式</view>
          <switch :checked="simple_model" @change="onChangeSwitch($event, 'simple_model')" />
        </view>
        <view class="tip">关闭简洁模式后，可以自定义上传门店首页模板</view>
      </view>
      
      <!-- 自定义模板图片（非简洁模式时显示） -->
      <view v-if="!simple_model && template_key === 'custom'">
        <view class="line">
          <label>自定义模板</label>
          <view class="right">
            <view class="upload-area">
              <view class="upload-list vertical">
                <!-- 立即预约按钮图 -->
                <view class="upload-row">
                  <view class="upload-item" v-if="btnfileList.length">
                    <image :src="btnfileList[0].url" mode="aspectFit" @click="previewImage(btnfileList[0].url)" />
                    <view class="delete-btn" @click="deleteCustomImage('btn')">×</view>
                  </view>
                  <view class="upload-btn" v-else @click="chooseCustomImage('btn')">
                    <text class="upload-icon">+</text>
                    <text class="upload-text">立即预约按钮图：495 x 600</text>
                  </view>
                </view>
                <!-- 切换门店按钮图 -->
                <view class="upload-row">
                  <view class="upload-item" v-if="qhfileList.length">
                    <image :src="qhfileList[0].url" mode="aspectFit" @click="previewImage(qhfileList[0].url)" />
                    <view class="delete-btn" @click="deleteCustomImage('qh')">×</view>
                  </view>
                  <view class="upload-btn" v-else @click="chooseCustomImage('qh')">
                    <text class="upload-icon">+</text>
                    <text class="upload-text">切换门店按钮图：495 x 282</text>
                  </view>
                </view>
                <!-- 团购兑换按钮图 -->
                <view class="upload-row">
                  <view class="upload-item" v-if="tgfileList.length">
                    <image :src="tgfileList[0].url" mode="aspectFit" @click="previewImage(tgfileList[0].url)" />
                    <view class="delete-btn" @click="deleteCustomImage('tg')">×</view>
                  </view>
                  <view class="upload-btn" v-else @click="chooseCustomImage('tg')">
                    <text class="upload-icon">+</text>
                    <text class="upload-text">团购兑换按钮图：495 x 282</text>
                  </view>
                </view>
                <!-- 商品点单按钮图 -->
                <view class="upload-row">
                  <view class="upload-item" v-if="czfileList.length">
                    <image :src="czfileList[0].url" mode="aspectFit" @click="previewImage(czfileList[0].url)" />
                    <view class="delete-btn" @click="deleteCustomImage('cz')">×</view>
                  </view>
                  <view class="upload-btn" v-else @click="chooseCustomImage('cz')">
                    <text class="upload-icon">+</text>
                    <text class="upload-text">商品点单按钮图：495 x 210</text>
                  </view>
                </view>
                <!-- 一键开门按钮图 -->
                <view class="upload-row">
                  <view class="upload-item" v-if="openfileList.length">
                    <image :src="openfileList[0].url" mode="aspectFit" @click="previewImage(openfileList[0].url)" />
                    <view class="delete-btn" @click="deleteCustomImage('open')">×</view>
                  </view>
                  <view class="upload-btn" v-else @click="chooseCustomImage('open')">
                    <text class="upload-icon">+</text>
                    <text class="upload-text">一键开门按钮图：495 x 210</text>
                  </view>
                </view>
                <!-- WIFI信息按钮图 -->
                <view class="upload-row">
                  <view class="upload-item" v-if="wififileList.length">
                    <image :src="wififileList[0].url" mode="aspectFit" @click="previewImage(wififileList[0].url)" />
                    <view class="delete-btn" @click="deleteCustomImage('wifi')">×</view>
                  </view>
                  <view class="upload-btn" v-else @click="chooseCustomImage('wifi')">
                    <text class="upload-icon">+</text>
                    <text class="upload-text">WIFI信息按钮图：495 x 210</text>
                  </view>
                </view>
                <!-- 联系客服按钮图 -->
                <view class="upload-row">
                  <view class="upload-item" v-if="kffileList.length">
                    <image :src="kffileList[0].url" mode="aspectFit" @click="previewImage(kffileList[0].url)" />
                    <view class="delete-btn" @click="deleteCustomImage('kf')">×</view>
                  </view>
                  <view class="upload-btn" v-else @click="chooseCustomImage('kf')">
                    <text class="upload-icon">+</text>
                    <text class="upload-text">联系客服按钮图：495 x 210</text>
                  </view>
                </view>
              </view>
            </view>
          </view>
        </view>
      </view>
    </view>
    
    <!-- 底部按钮 -->
    <view class="submit" :class="isIpx ? 'fix-iphonex-button' : ''">
      <button class="cancel" @click="cancel">取消</button>
      <button class="primary" @click="submit">保存</button>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      store_id: '',
      name: '',
      city: '',
      address: '',
      wifi_name: '',
      wifi_password: '',
      phone: '',
      business_hours: '',
      description: '',
      longitude: '',
      latitude: '',
      tx_start_hour: '23',
      tx_hour: '8',
      delay_light: false,
      clear_time: '5',
      clear_open: true,
      order_door_open: false,
      clear_open_door: false,
      order_webhook: '',
      qr_code: '',
      simple_model: true,
      template_key: '',
      fileList1: [],
      fileList2: [],
      fileList3: [],
      btnfileList: [],
      qhfileList: [],
      tgfileList: [],
      czfileList: [],
      openfileList: [],
      wififileList: [],
      kffileList: [],
      isIpx: false
    }
  },
  onLoad(options) {
    // 检测是否是iPhone X
    const systemInfo = uni.getSystemInfoSync()
    this.isIpx = systemInfo.model && systemInfo.model.indexOf('iPhone X') !== -1
    
    if (options.store_id) {
      this.store_id = Number(options.store_id)
      this.getData()
    }
  },
  methods: {
    async getData() {
      try {
        const res = await http.get(`/member/store/getDetail/${this.store_id}`)
        if (res.code === 0 && res.data) {
          const data = res.data
          this.name = data.name || ''
          this.city = data.city || ''
          this.address = data.address || ''
          this.wifi_name = data.wifi_name || ''
          this.wifi_password = data.wifi_password || ''
          this.phone = data.phone || ''
          this.business_hours = data.business_hours || ''
          this.description = data.description || ''
          this.longitude = data.longitude || ''
          this.latitude = data.latitude || ''
          this.tx_start_hour = String(data.tx_start_hour || '23')
          this.tx_hour = String(data.tx_hour || '8')
          this.delay_light = !!data.delay_light
          this.clear_time = String(data.clear_time || '5')
          this.clear_open = data.clear_open !== false
          this.order_door_open = !!data.order_door_open
          this.clear_open_door = !!data.clear_open_door
          this.order_webhook = data.order_webhook || ''
          this.qr_code = data.qr_code || ''
          this.simple_model = data.simple_model !== false
          this.template_key = data.template_key || ''
          
          // 处理图片
          if (data.head_img) {
            this.fileList1 = [{ url: data.head_img }]
          }
          if (data.banner_img) {
            this.fileList2 = data.banner_img.split(',').filter(url => url).map(url => ({ url }))
          }
          if (data.env_images) {
            this.fileList3 = data.env_images.split(',').filter(url => url).map(url => ({ url }))
          }
          
          // 自定义模板图片
          if (data.btn_img) this.btnfileList = [{ url: data.btn_img }]
          if (data.qh_img) this.qhfileList = [{ url: data.qh_img }]
          if (data.tg_img) this.tgfileList = [{ url: data.tg_img }]
          if (data.cz_img) this.czfileList = [{ url: data.cz_img }]
          if (data.open_img) this.openfileList = [{ url: data.open_img }]
          if (data.wifi_img) this.wififileList = [{ url: data.wifi_img }]
          if (data.kf_img) this.kffileList = [{ url: data.kf_img }]
        }
      } catch (e) {
        uni.showToast({ title: '获取数据失败', icon: 'none' })
      }
    },
    
    onChangeSwitch(e, field) {
      this[field] = e.detail.value
    },
    
    previewImage(url) {
      uni.previewImage({ urls: [url] })
    },
    
    chooseImage(type) {
      const maxCount = type === 1 ? 1 : 9
      const currentList = type === 1 ? this.fileList1 : (type === 2 ? this.fileList2 : this.fileList3)
      const remaining = maxCount - currentList.length
      
      uni.chooseImage({
        count: remaining,
        success: (res) => {
          res.tempFilePaths.forEach(path => {
            this.uploadImage(path, type)
          })
        }
      })
    },
    
    async uploadImage(filePath, type) {
      try {
        uni.showLoading({ title: '上传中...' })
        const url = await http.upload(filePath)
        uni.hideLoading()
        
        if (type === 1) {
          this.fileList1.push({ url })
        } else if (type === 2) {
          this.fileList2.push({ url })
        } else if (type === 3) {
          this.fileList3.push({ url })
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({ title: '上传失败', icon: 'none' })
      }
    },
    
    deleteImage(type, index) {
      if (type === 1) {
        this.fileList1.splice(index, 1)
      } else if (type === 2) {
        this.fileList2.splice(index, 1)
      } else if (type === 3) {
        this.fileList3.splice(index, 1)
      }
    },
    
    chooseCustomImage(field) {
      uni.chooseImage({
        count: 1,
        success: (res) => {
          this.uploadCustomImage(res.tempFilePaths[0], field)
        }
      })
    },
    
    async uploadCustomImage(filePath, field) {
      try {
        uni.showLoading({ title: '上传中...' })
        const url = await http.upload(filePath)
        uni.hideLoading()
        
        const listName = field + 'fileList'
        this[listName] = [{ url }]
      } catch (e) {
        uni.hideLoading()
        uni.showToast({ title: '上传失败', icon: 'none' })
      }
    },
    
    deleteCustomImage(field) {
      const listName = field + 'fileList'
      this[listName] = []
    },
    
    cancel() {
      uni.navigateBack()
    },
    
    async submit() {
      // 校验必填项
      if (!this.name || !this.city || !this.address || 
          !this.wifi_name || !this.wifi_password || !this.phone ||
          !this.tx_start_hour || !this.tx_hour || !this.clear_time ||
          this.fileList1.length === 0 || this.fileList2.length === 0) {
        uni.showToast({ title: '请填写完整', icon: 'none' })
        return
      }
      
      // 构建参数
      const bannerImgs = this.fileList2.map(it => it.url)
      const envImgs = this.fileList3.map(it => it.url)
      
      let params = {
        id: this.store_id,
        name: this.name,
        city: this.city,
        head_img: this.fileList1[0].url,
        banner_img: bannerImgs.join(','),
        env_images: envImgs.join(','),
        order_webhook: this.order_webhook,
        address: this.address,
        wifi_name: this.wifi_name,
        wifi_password: this.wifi_password,
        simple_model: this.simple_model,
        phone: this.phone,
        business_hours: this.business_hours,
        description: this.description,
        longitude: this.longitude,
        latitude: this.latitude,
        clear_time: this.clear_time,
        clear_open: this.clear_open,
        tx_start_hour: this.tx_start_hour,
        delay_light: this.delay_light,
        tx_hour: this.tx_hour,
        order_door_open: this.order_door_open,
        clear_open_door: this.clear_open_door
      }
      
      // 非简洁模式时添加自定义模板图片
      if (!this.simple_model) {
        params.btn_img = this.btnfileList.length ? this.btnfileList[0].url : ''
        params.qh_img = this.qhfileList.length ? this.qhfileList[0].url : ''
        params.tg_img = this.tgfileList.length ? this.tgfileList[0].url : ''
        params.cz_img = this.czfileList.length ? this.czfileList[0].url : ''
        params.open_img = this.openfileList.length ? this.openfileList[0].url : ''
        params.wifi_img = this.wififileList.length ? this.wififileList[0].url : ''
        params.kf_img = this.kffileList.length ? this.kffileList[0].url : ''
      }
      
      try {
        uni.showLoading({ title: '保存中...' })
        const res = await http.post('/member/store/save', params)
        uni.hideLoading()
        
        if (res.code === 0) {
          uni.showToast({ title: '设置成功', icon: 'success' })
          setTimeout(() => uni.navigateBack(), 1000)
        } else {
          uni.showToast({ title: res.msg || '保存失败', icon: 'none' })
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({ title: e.msg || '保存失败', icon: 'none' })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  padding: 16rpx;
  background: #f5f5f5;
  min-height: 100vh;
}

.form {
  margin-bottom: 30rpx;
}

.cell-group {
  background: #fff;
  margin-bottom: 20rpx;
  border-radius: 8rpx;
}

.cell {
  display: flex;
  align-items: center;
  padding: 24rpx 30rpx;
  border-bottom: 1rpx solid #eee;
  
  &:last-child {
    border-bottom: none;
  }
}

.cell-label {
  width: 220rpx;
  font-size: 28rpx;
  color: #333;
  flex-shrink: 0;
}

.cell-value {
  flex: 1;
  
  input {
    width: 100%;
    height: 60rpx;
    font-size: 28rpx;
    color: #333;
  }
  
  textarea {
    width: 100%;
    min-height: 80rpx;
    font-size: 28rpx;
    color: #333;
  }
}

.switch-cell {
  justify-content: space-between;
}

.tip {
  padding: 12rpx 30rpx 20rpx;
  font-size: 24rpx;
  color: #999;
  background: #fff;
}

.line {
  display: flex;
  padding: 30rpx;
  background: #fff;
  margin-bottom: 20rpx;
  border-radius: 8rpx;
  
  label {
    width: 220rpx;
    font-size: 28rpx;
    color: #333;
    flex-shrink: 0;
  }
  
  .right {
    flex: 1;
  }
}

.upload-area {
  width: 100%;
}

.upload-list {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
  
  &.vertical {
    flex-direction: column;
  }
}

.upload-row {
  margin-bottom: 16rpx;
}

.upload-item {
  position: relative;
  width: 200rpx;
  height: 200rpx;
  
  image {
    width: 100%;
    height: 100%;
    border-radius: 8rpx;
  }
  
  .delete-btn {
    position: absolute;
    top: -10rpx;
    right: -10rpx;
    width: 40rpx;
    height: 40rpx;
    background: rgba(0, 0, 0, 0.5);
    color: #fff;
    border-radius: 50%;
    text-align: center;
    line-height: 36rpx;
    font-size: 32rpx;
  }
}

.upload-btn {
  width: 200rpx;
  height: 200rpx;
  border: 2rpx dashed #ddd;
  border-radius: 8rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  
  .upload-icon {
    font-size: 60rpx;
    color: #999;
  }
  
  .upload-text {
    font-size: 20rpx;
    color: #999;
    margin-top: 10rpx;
    text-align: center;
    padding: 0 10rpx;
  }
}

.submit {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  padding: 20rpx 30rpx;
  background: #f5f5f5;
  z-index: 100;
  
  button {
    flex: 1;
    height: 90rpx;
    line-height: 90rpx;
    font-size: 30rpx;
    border-radius: 8rpx;
    margin: 0 10rpx;
  }
  
  .cancel {
    background: #fff;
    color: #07c160;
    border: 1rpx solid #07c160;
  }
  
  .primary {
    background: #07c160;
    color: #fff;
  }
}

.fix-iphonex-button {
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
}
</style>
