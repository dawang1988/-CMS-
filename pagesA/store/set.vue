<template>
  <view class="page" :style="themeStyle">
    <!-- 搜索栏 -->
    <view class="search-bar">
      <view class="search-bar-inner">
        <input class="search-input" v-model="searchName" placeholder="请输入搜索关键词" @confirm="handleSearch" />
        <view class="search-btn" @click="handleSearch">搜索</view>
      </view>
    </view>

    <!-- 门店列表 -->
    <view class="store-list" v-if="storeList.length > 0">
      <view class="store-item" v-for="item in storeList" :key="item.id">
        <view class="store-top">
          <view class="store-img">
            <image :src="item.head_img" mode="aspectFill"></image>
          </view>
          <view class="store-right" @click="goManage(item)">
            <view class="store-header">
              <view class="store-name">{{ item.name }}</view>
              <view class="store-about">
                <view class="room-num">房间:{{ item.free_room_num || 0 }}</view>
              </view>
            </view>
            <view class="store-address">
              {{ item.address }}
            </view>
            <view class="store-tags">
              <text class="tag yellow">门店ID：{{ item.id }}</text>
              <text class="tag orange" v-if="item.expire_time">{{ item.expire_time }}到期</text>
            </view>
            <view class="store-status" :class="[item.status === 0 ? 'green' : item.status === 1 ? 'yellow' : 'gray']">
              {{ getStatusText(item.status) }}
            </view>
            <view class="store-btns">
              <image src="/static/icon/open.png" style="width:40rpx;height:40rpx;" @click.stop="openDoor(item.id)" />
              <image src="/static/icon/setting.png" style="width:40rpx;height:40rpx;" @click.stop="goManage(item)" />
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 空状态 -->
    <view class="empty-state" v-else>
      <text>暂无数据</text>
    </view>

    <!-- 加载更多 -->
    <view class="load-more">
      <text v-if="loadStatus === 'loading'">加载中...</text>
      <text v-else-if="loadStatus === 'nomore'">没有更多了</text>
      <text v-else>加载更多</text>
    </view>
  </view>
</template>

<script>
import { getStorePageList, openStoreDoorAdmin } from '@/api/door.js'

export default {
  data() {
    return {
      searchName: '',
      storeList: [],
      pageNo: 1,
      pageSize: 10,
      canLoadMore: true,
      loadStatus: 'loadmore'
    }
  },
  onLoad() {
    this.getListData('refresh')
  },
  onPullDownRefresh() {
    this.pageNo = 1
    this.storeList = []
    this.canLoadMore = true
    this.getListData('refresh')
    setTimeout(() => {
      uni.stopPullDownRefresh()
    }, 500)
  },
  onReachBottom() {
    if (this.canLoadMore) {
      this.getListData()
    }
  },
  methods: {
    // 获取列表数据
    async getListData(type) {
      if (type === 'refresh') {
        this.pageNo = 1
        this.storeList = []
        this.loadStatus = 'loading'
      }

      try {
        const res = await getStorePageList({
          pageNo: this.pageNo,
          pageSize: this.pageSize,
          name: this.searchName
        })

        const data = res.data || res
        if (data.list && data.list.length > 0) {
          this.storeList = [...this.storeList, ...data.list]
          this.pageNo++
          this.canLoadMore = this.storeList.length < data.total
          this.loadStatus = this.canLoadMore ? 'loadmore' : 'nomore'
        } else {
          this.canLoadMore = false
          this.loadStatus = 'nomore'
        }
      } catch (e) {
        this.loadStatus = 'loadmore'
      }
    },

    // 搜索
    handleSearch() {
      this.getListData('refresh')
    },

    // 获取状态类名
    getStatusClass(status) {
      const map = {
        0: 'green',
        1: 'yellow',
        2: 'gray'
      }
      return map[status] || 'gray'
    },

    // 获取状态文本
    getStatusText(status) {
      const map = {
        0: '开业',
        1: '审核中',
        2: '已到期'
      }
      return map[status] || '未知'
    },

    // 打开大门
    async openDoor(storeId) {
      try {
        await openStoreDoorAdmin(storeId)
        uni.showToast({
          title: '开门成功',
          icon: 'success'
        })
      } catch (e) {
        uni.showToast({
          title: e.msg || '开门失败',
          icon: 'none'
        })
      }
    },

    // 跳转到门店管理
    goManage(item) {
      uni.navigateTo({
        url: `/pagesA/door/manage?storeInfo=${encodeURIComponent(JSON.stringify(item))}`
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 20rpx;
}

.search-bar {
  background: #fff;
  padding: 20rpx;
}

.store-list {
  padding: 20rpx;
}

.store-item {
  background: #fff;
  border-radius: 16rpx;
  padding: 24rpx;
  margin-bottom: 20rpx;
}

.store-top {
  display: flex;
}

.store-img {
  width: 160rpx;
  height: 160rpx;
  border-radius: 12rpx;
  overflow: hidden;
  flex-shrink: 0;
  margin-right: 20rpx;

  image {
    width: 100%;
    height: 100%;
  }
}

.store-right {
  flex: 1;
  position: relative;
}

.store-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12rpx;
}

.store-name {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
}

.store-about {
  .room-num {
    font-size: 24rpx;
    color: #999;
  }
}

.store-address {
  font-size: 26rpx;
  color: #666;
  margin-bottom: 12rpx;
  line-height: 1.5;
}

.store-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-bottom: 12rpx;

  .tag {
    padding: 4rpx 12rpx;
    border-radius: 6rpx;
    font-size: 22rpx;

    &.yellow {
      background: #fff7e6;
      color: #fa8c16;
    }

    &.orange {
      background: #fff2e8;
      color: #ff6b00;
    }
  }
}

.store-status {
  position: absolute;
  top: 0;
  right: 0;
  padding: 6rpx 16rpx;
  border-radius: 6rpx;
  font-size: 24rpx;

  &.green {
    background: #f6ffed;
    color: #52c41a;
  }

  &.yellow {
    background: #fff7e6;
    color: #fa8c16;
  }

  &.gray {
    background: #f5f5f5;
    color: #999;
  }
}

.store-btns {
  display: flex;
  gap: 24rpx;
  margin-top: 12rpx;
}

.search-bar-inner { display: flex; align-items: center; background: #fff; padding: 16rpx 20rpx; }
.search-input { flex: 1; height: 64rpx; background: #f5f5f5; border-radius: 32rpx; padding: 0 24rpx; font-size: 26rpx; }
.search-btn { margin-left: 16rpx; padding: 0 24rpx; height: 64rpx; line-height: 64rpx; background: var(--main-color, #5AAB6E); color: #fff; border-radius: 32rpx; font-size: 26rpx; }

.empty-state { text-align: center; padding: 120rpx 0; color: #999; font-size: 28rpx; }
.load-more { text-align: center; padding: 20rpx; font-size: 26rpx; color: #999; }
</style>
