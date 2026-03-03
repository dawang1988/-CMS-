<template>
  <view class="page" :style="themeStyle">
    <!-- 顶部积分卡片 -->
    <view class="score-header">
      <view class="score-num">{{ info.score || 0 }}</view>
      <view class="score-label">我的积分</view>
      <view class="vip-badge" v-if="info.vip_name">
        <text class="vip-icon">♛</text>
        <text>{{ info.vip_name }}</text>
        <text class="vip-discount">{{ info.vip_discount / 10 }}折</text>
      </view>
      <view class="vip-badge vip-none" v-else>
        <text>暂无VIP等级</text>
      </view>
      <!-- 进度条 -->
      <view class="progress-box" v-if="info.next_vip">
        <view class="progress-text">
          距 {{ info.next_vip.vip_name }} 还需 {{ info.next_vip.need_score }} 积分
        </view>
        <view class="progress-bar-bg">
          <view class="progress-bar" :style="{ width: progressPct + '%' }"></view>
        </view>
        <view class="progress-ends">
          <text>{{ info.score || 0 }}</text>
          <text>{{ info.next_vip.score }}</text>
        </view>
      </view>
      <view class="progress-box" v-else-if="info.vip_name">
        <view class="progress-text top-text">已达最高等级</view>
      </view>
    </view>

    <!-- 积分规则 -->
    <view class="section">
      <view class="section-title">积分规则</view>
      <view class="rule-item">
        <view class="rule-dot"></view>
        <text>每消费 1 元自动获得 1 积分</text>
      </view>
      <view class="rule-item">
        <view class="rule-dot"></view>
        <text>包间订单、商品订单、续费均可获得积分</text>
      </view>
      <view class="rule-item">
        <view class="rule-dot"></view>
        <text>积分达到对应门槛自动升级VIP等级</text>
      </view>
      <view class="rule-item">
        <view class="rule-dot"></view>
        <text>VIP等级只升不降，积分永久有效</text>
      </view>
    </view>

    <!-- VIP等级列表 -->
    <view class="section">
      <view class="section-title">VIP等级</view>
      <view class="vip-list">
        <view v-for="(item, idx) in info.vip_list" :key="idx"
          :class="['vip-card', { 'vip-card-active': info.vip_level >= item.vip_level, 'vip-card-current': info.vip_level == item.vip_level }]">
          <view class="vip-card-left">
            <view class="vip-card-level">Lv.{{ item.vip_level }}</view>
            <view class="vip-card-name">{{ item.vip_name }}</view>
          </view>
          <view class="vip-card-center">
            <view class="vip-card-score">{{ item.score }} 积分</view>
            <view class="vip-card-desc">消费满{{ item.score }}元</view>
          </view>
          <view class="vip-card-right">
            <view class="vip-card-discount">{{ item.vip_discount / 10 }}折</view>
            <view class="vip-card-tag" v-if="info.vip_level == item.vip_level">当前</view>
            <view class="vip-card-tag vip-tag-done" v-else-if="info.vip_level > item.vip_level">已达成</view>
            <view class="vip-card-tag vip-tag-lock" v-else>未达成</view>
          </view>
        </view>
      </view>
      <view class="empty" v-if="!info.vip_list || info.vip_list.length === 0">
        <text>暂无VIP等级配置</text>
      </view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http'
export default {
  data() {
    return {
      info: {
        score: 0,
        vip_level: 0,
        vip_name: '',
        vip_discount: 100,
        next_vip: null,
        vip_list: []
      }
    }
  },
  computed: {
    progressPct() {
      if (!this.info.next_vip) return 100
      var target = this.info.next_vip.score
      if (target <= 0) return 100
      return Math.min(100, Math.round((this.info.score / target) * 100))
    }
  },
  onShow() {
    this.loadData()
  },
  methods: {
    loadData() {
      var that = this
      http.get('/member/user/getVipInfo').then(function(res) {
        if (res.code === 0) {
          that.info = res.data
        }
      }).catch(function(e) {
        console.error('获取VIP信息失败', e)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.page { min-height: 100vh; background: #f5f5f5; }

.score-header {
  background: linear-gradient(135deg, var(--main-color, #5AAB6E) 0%, var(--main-color-shadow, #3d8b50) 100%);
  padding: 60rpx 40rpx 50rpx;
  text-align: center;
  color: #fff;
}
.score-num { font-size: 80rpx; font-weight: 700; line-height: 1.2; }
.score-label { font-size: 26rpx; opacity: 0.85; margin-bottom: 20rpx; }
.vip-badge {
  display: inline-flex; align-items: center; gap: 8rpx;
  background: rgba(255,255,255,0.2); border-radius: 30rpx;
  padding: 8rpx 24rpx; font-size: 24rpx;
}
.vip-badge .vip-icon { font-size: 28rpx; }
.vip-badge .vip-discount { opacity: 0.85; }
.vip-none { opacity: 0.7; }

.progress-box { margin-top: 30rpx; padding: 0 20rpx; }
.progress-text { font-size: 22rpx; opacity: 0.85; margin-bottom: 12rpx; }
.top-text { font-size: 26rpx; opacity: 0.9; }
.progress-bar-bg {
  height: 16rpx; background: rgba(255,255,255,0.3);
  border-radius: 8rpx; overflow: hidden;
}
.progress-bar {
  height: 100%; background: linear-gradient(90deg, #fff, #f0c27f);
  border-radius: 8rpx; transition: width 0.5s;
}
.progress-ends {
  display: flex; justify-content: space-between;
  font-size: 20rpx; opacity: 0.7; margin-top: 6rpx;
}

.section {
  background: #fff; margin: 20rpx 24rpx; border-radius: 16rpx;
  padding: 28rpx 30rpx;
}
.section-title {
  font-size: 30rpx; font-weight: 600; color: #333;
  margin-bottom: 20rpx; padding-left: 16rpx;
  border-left: 6rpx solid var(--main-color, #5AAB6E);
}

.rule-item {
  display: flex; align-items: center; padding: 12rpx 0;
  font-size: 26rpx; color: #666;
}
.rule-dot {
  width: 10rpx; height: 10rpx; border-radius: 50%;
  background: var(--main-color, #5AAB6E); margin-right: 16rpx; flex-shrink: 0;
}

.vip-list { }
.vip-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: 24rpx 20rpx; border-radius: 12rpx; margin-bottom: 16rpx;
  background: #f8f8f8; border: 2rpx solid #eee;
}
.vip-card-active { background: #f0f7f2; border-color: #c8e6cf; }
.vip-card-current { border-color: var(--main-color, #5AAB6E); border-width: 3rpx; }
.vip-card-left { min-width: 140rpx; }
.vip-card-level { font-size: 22rpx; color: #999; }
.vip-card-active .vip-card-level { color: var(--main-color, #5AAB6E); }
.vip-card-name { font-size: 28rpx; font-weight: 600; color: #333; margin-top: 4rpx; }
.vip-card-center { flex: 1; padding: 0 20rpx; }
.vip-card-score { font-size: 26rpx; color: #666; }
.vip-card-desc { font-size: 22rpx; color: #999; margin-top: 4rpx; }
.vip-card-right { text-align: right; min-width: 100rpx; }
.vip-card-discount { font-size: 32rpx; font-weight: 700; color: #f0ad4e; }
.vip-card-active .vip-card-discount { color: var(--main-color, #5AAB6E); }
.vip-card-tag {
  font-size: 20rpx; color: #fff; background: var(--main-color, #5AAB6E);
  border-radius: 6rpx; padding: 2rpx 10rpx; margin-top: 6rpx;
  display: inline-block;
}
.vip-tag-done { background: #ccc; }
.vip-tag-lock { background: #ddd; color: #999; }

.empty { text-align: center; color: #999; font-size: 26rpx; padding: 40rpx 0; }
</style>
