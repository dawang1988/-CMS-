<template>
  <view class="container" :style="themeStyle">
    <!-- 规则说明 -->
    <view class="rule-section">
      <view class="rule-item">
        <text class="rule-icon">🪙</text>
        <view class="rule-text">
          <text class="rule-title">积分获取</text>
          <text class="rule-desc">用户每消费1元自动获得1积分（包间订单、商品订单、续费均计入）</text>
        </view>
      </view>
      <view class="rule-item">
        <text class="rule-icon">⬆️</text>
        <view class="rule-text">
          <text class="rule-title">自动升级</text>
          <text class="rule-desc">累计积分达到对应等级门槛后自动升级VIP，享受折扣优惠（只升不降）</text>
        </view>
      </view>
      <view class="rule-item">
        <text class="rule-icon">💰</text>
        <view class="rule-text">
          <text class="rule-title">折扣说明</text>
          <text class="rule-desc">折扣值95表示9.5折，90表示9折，以此类推。团购下单不享受折扣</text>
        </view>
      </view>
    </view>

    <!-- VIP等级列表 -->
    <view class="section-header">
      <text class="section-title">👑 VIP等级配置</text>
      <view class="add-btn" @click="edit(null)">+ 添加等级</view>
    </view>

    <view class="list" v-if="list.length">
      <view class="item" v-for="(item, index) in list" :key="index">
        <view class="item-header">
          <view class="level-badge">Lv.{{ item.vip_level }}</view>
          <text class="vip-name">{{ item.vip_name }}</text>
          <view class="status-tag" :class="item.status == 1 ? 'on' : 'off'">
            {{ item.status == 1 ? '启用' : '禁用' }}
          </view>
        </view>
        <view class="item-body">
          <view class="info-row">
            <text class="label">积分门槛</text>
            <text class="value">{{ item.score }} 积分（≈消费{{ item.score }}元）</text>
          </view>
          <view class="info-row">
            <text class="label">会员折扣</text>
            <text class="value">{{ item.vip_discount == 100 ? '无折扣' : (item.vip_discount / 10).toFixed(1) + '折' }}</text>
          </view>
        </view>
        <view class="item-footer">
          <view class="btn-edit" @click="edit(item)">编辑</view>
          <view class="btn-del" @click="deleteConfig(item.id, item.vip_name)">删除</view>
        </view>
      </view>
    </view>
    <view class="empty" v-else>暂无VIP等级配置</view>

    <!-- 弹窗 -->
    <view class="dialog-mask" v-if="showDialog" @click="showDialog = false"></view>
    <view class="dialog" v-if="showDialog">
      <view class="dialog-title">{{ editId ? '编辑VIP等级' : '添加VIP等级' }}</view>
      <view class="dialog-content">
        <view class="field">
          <text class="field-label">VIP名称</text>
          <input v-model="form.vip_name" placeholder="如：银卡会员" />
        </view>
        <view class="field">
          <text class="field-label">VIP等级</text>
          <input v-model="form.vip_level" type="number" placeholder="数字越大等级越高" />
          <text class="field-tip">等级数字，如 1=银卡 2=金卡 3=钻石</text>
        </view>
        <view class="field">
          <text class="field-label">积分门槛</text>
          <input v-model="form.score" type="number" placeholder="累计消费多少元可达到" />
          <text class="field-tip">消费1元=1积分，填500表示累计消费500元可升级</text>
        </view>
        <view class="field">
          <text class="field-label">折扣</text>
          <input v-model="form.vip_discount" type="number" placeholder="如95表示9.5折" />
          <text class="field-tip">100=无折扣，95=9.5折，90=9折，85=8.5折</text>
        </view>
        <view class="field">
          <text class="field-label">状态</text>
          <switch :checked="form.status == 1" @change="form.status = $event.detail.value ? 1 : 0" />
        </view>
      </view>
      <view class="dialog-btns">
        <view class="btn cancel" @click="showDialog = false">取消</view>
        <view class="btn confirm" @click="submit">保存</view>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      store_id: '',
      list: [],
      showDialog: false,
      editId: '',
      form: {
        vip_name: '',
        vip_level: '',
        vip_discount: '',
        score: '',
        status: 1
      }
    }
  },
  onLoad(options) {
    this.store_id = options.store_id || uni.getStorageSync('admin_store_id') || uni.getStorageSync('global_store_id') || ''
    if (this.store_id) {
      this.loadList()
    } else {
      uni.showToast({ title: '请先选择门店', icon: 'none' })
    }
  },
  methods: {
    async loadList() {
      try {
        const res = await http.post(`/member/store/getVipConfig/${this.store_id}`)
        if (res.code === 0) {
          this.list = res.data || []
        }
      } catch (e) {}
    },
    edit(item) {
      if (item) {
        this.editId = item.id
        this.form = {
          vip_name: item.vip_name,
          vip_level: String(item.vip_level || ''),
          vip_discount: String(item.vip_discount),
          score: String(item.score),
          status: item.status != null ? item.status : 1
        }
      } else {
        this.editId = ''
        this.form = { vip_name: '', vip_level: '', vip_discount: '', score: '', status: 1 }
      }
      this.showDialog = true
    },
    async submit() {
      const { vip_name, vip_level, vip_discount, score } = this.form
      if (!vip_name || !vip_level || !vip_discount || !score) {
        uni.showToast({ title: '请填写完整', icon: 'none' })
        return
      }
      try {
        uni.showLoading({ title: '保存中...' })
        const res = await http.post('/member/store/saveVipConfig', {
          id: this.editId,
          store_id: this.store_id,
          vip_name,
          vip_level: Number(vip_level),
          vip_discount: Number(vip_discount),
          score: Number(score),
          status: this.form.status
        })
        uni.hideLoading()
        if (res.code === 0) {
          uni.showToast({ title: '保存成功' })
          this.showDialog = false
          this.loadList()
        } else {
          uni.showToast({ title: res.msg || '保存失败', icon: 'none' })
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({ title: e.msg || '保存失败', icon: 'none' })
      }
    },
    deleteConfig(id, name) {
      uni.showModal({
        title: '温馨提示',
        content: `确定删除「${name}」？删除后已有该等级的用户不会自动降级。`,
        success: async (res) => {
          if (res.confirm) {
            try {
              const result = await http.post(`/member/store/deleteVipConfig/${id}`)
              if (result.code === 0) {
                uni.showToast({ title: '删除成功' })
                this.loadList()
              } else {
                uni.showToast({ title: result.msg || '删除失败', icon: 'none' })
              }
            } catch (e) {
              uni.showToast({ title: '删除失败', icon: 'none' })
            }
          }
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container { min-height: 100vh; background: #f5f5f5; padding: 20rpx; padding-bottom: 40rpx; }

.rule-section { background: #fff; border-radius: 12rpx; padding: 24rpx; margin-bottom: 20rpx; }
.rule-item { display: flex; align-items: flex-start; margin-bottom: 20rpx; &:last-child { margin-bottom: 0; } }
.rule-icon { font-size: 36rpx; margin-right: 16rpx; margin-top: 4rpx; }
.rule-text { flex: 1; }
.rule-title { font-size: 28rpx; font-weight: bold; color: #333; display: block; }
.rule-desc { font-size: 24rpx; color: #666; line-height: 1.5; margin-top: 4rpx; display: block; }

.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16rpx; }
.section-title { font-size: 30rpx; font-weight: bold; color: #333; }
.add-btn { font-size: 26rpx; color: #fff; background: var(--main-color, #5AAB6E); padding: 10rpx 24rpx; border-radius: 8rpx; }

.list .item { background: #fff; border-radius: 12rpx; padding: 24rpx; margin-bottom: 16rpx; }
.item-header { display: flex; align-items: center; margin-bottom: 16rpx; }
.level-badge { background: #f0ad4e; color: #fff; font-size: 24rpx; padding: 4rpx 16rpx; border-radius: 6rpx; margin-right: 12rpx; font-weight: bold; }
.vip-name { font-size: 30rpx; font-weight: bold; color: #333; flex: 1; }
.status-tag { font-size: 22rpx; padding: 4rpx 14rpx; border-radius: 6rpx; }
.status-tag.on { background: #e6f7ee; color: #52c41a; }
.status-tag.off { background: #fff1f0; color: #ff4d4f; }

.item-body { padding-bottom: 16rpx; border-bottom: 1rpx solid #f0f0f0; }
.info-row { display: flex; justify-content: space-between; margin-bottom: 10rpx; }
.info-row .label { font-size: 26rpx; color: #999; }
.info-row .value { font-size: 26rpx; color: #333; }

.item-footer { display: flex; justify-content: flex-end; gap: 30rpx; padding-top: 16rpx; }
.btn-edit { font-size: 26rpx; color: var(--main-color, #5AAB6E); }
.btn-del { font-size: 26rpx; color: #ff4d4f; }

.empty { text-align: center; color: #999; padding: 80rpx 0; font-size: 28rpx; }

.dialog-mask { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100; }
.dialog { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 620rpx; background: #fff; border-radius: 16rpx; z-index: 101; overflow: hidden; }
.dialog-title { text-align: center; padding: 30rpx; font-size: 32rpx; font-weight: bold; border-bottom: 1rpx solid #eee; }
.dialog-content { padding: 30rpx; }
.field { margin-bottom: 24rpx; }
.field-label { font-size: 28rpx; color: #333; margin-bottom: 10rpx; display: block; }
.field input { width: 100%; height: 72rpx; padding: 0 20rpx; border: 1rpx solid #ddd; border-radius: 8rpx; font-size: 28rpx; box-sizing: border-box; }
.field-tip { font-size: 22rpx; color: #999; margin-top: 6rpx; display: block; }
.dialog-btns { display: flex; border-top: 1rpx solid #eee; }
.dialog-btns .btn { flex: 1; height: 90rpx; line-height: 90rpx; text-align: center; font-size: 30rpx; }
.dialog-btns .cancel { color: #666; border-right: 1rpx solid #eee; }
.dialog-btns .confirm { color: var(--main-color, #5AAB6E); }
</style>
