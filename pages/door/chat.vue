<template>
  <view class="chat-page" :style="themeStyle">
    <!-- 顶部拼场信息 -->
    <view class="game-info" v-if="gameInfo.title">
      <text class="game-title">{{ gameInfo.title }}</text>
      <text class="game-meta">{{ gameInfo.store_name }} · {{ playerCount }}人</text>
    </view>

    <!-- 消息列表 -->
    <scroll-view class="msg-list" scroll-y :scroll-into-view="scrollToId" 
      :scroll-with-animation="true" @scrolltoupper="loadHistory">
      <view class="msg-item" v-for="msg in messages" :key="msg.id" :id="'msg-' + msg.id">
        <!-- 时间分割线 -->
        <view class="time-divider" v-if="msg.showTime">
          <text>{{ msg.timeLabel }}</text>
        </view>
        <!-- 自己的消息：右侧 -->
        <view class="msg-bubble right" v-if="msg.user_id == currentUserId">
          <view class="bubble-content">
            <text class="bubble-name">{{ msg.nickname || '我' }}</text>
            <view class="bubble-text mine">{{ msg.content }}</view>
          </view>
          <image class="avatar" :src="msg.avatar || '/static/logo.png'" mode="aspectFill" />
        </view>
        <!-- 别人的消息：左侧 -->
        <view class="msg-bubble left" v-else>
          <image class="avatar" :src="msg.avatar || '/static/logo.png'" mode="aspectFill" />
          <view class="bubble-content">
            <text class="bubble-name">{{ msg.nickname || '用户' }}</text>
            <view class="bubble-text other">{{ msg.content }}</view>
          </view>
        </view>
      </view>
      <view id="msg-bottom" style="height: 20rpx;"></view>
    </scroll-view>

    <!-- 底部输入栏 -->
    <view class="input-bar">
      <input class="msg-input" v-model="inputText" placeholder="说点什么..." 
        :adjust-position="true" confirm-type="send" @confirm="sendMessage" />
      <view class="send-btn" :class="{ active: inputText.trim() }" @tap="sendMessage">发送</view>
    </view>
  </view>
</template>

<script>
import http from '@/utils/http.js'

export default {
  data() {
    return {
      gameId: '',
      gameInfo: {},
      messages: [],
      inputText: '',
      currentUserId: 0,
      lastId: 0,
      scrollToId: '',
      timer: null,
      playerCount: 0
    }
  },
  onLoad(options) {
    this.gameId = options.gameId || ''
    if (options.gameInfo) {
      try {
        this.gameInfo = JSON.parse(decodeURIComponent(options.gameInfo))
        this.playerCount = (this.gameInfo.playUserList || []).length
      } catch(e) {}
    }
  },
  onShow() {
    if (this.gameId) {
      this.loadMessages()
      // 每5秒轮询一次新消息
      this.timer = setInterval(() => {
        this.pollMessages()
      }, 5000)
    }
  },
  onHide() {
    if (this.timer) {
      clearInterval(this.timer)
      this.timer = null
    }
  },
  onUnload() {
    if (this.timer) {
      clearInterval(this.timer)
      this.timer = null
    }
  },
  methods: {
    // 加载全部消息
    loadMessages() {
      http.get('/member/game/getMessages', { game_id: this.gameId })
        .then(res => {
          if (res.code === 0) {
            this.currentUserId = res.data.current_user_id
            this.messages = this.processMessages(res.data.list || [])
            if (this.messages.length > 0) {
              this.lastId = this.messages[this.messages.length - 1].id
            }
            this.$nextTick(() => {
              this.scrollToId = 'msg-bottom'
            })
          }
        })
    },
    // 轮询新消息
    pollMessages() {
      http.get('/member/game/getMessages', { game_id: this.gameId, last_id: this.lastId })
        .then(res => {
          if (res.code === 0) {
            const newList = res.data.list || []
            if (newList.length > 0) {
              const processed = this.processMessages(newList, this.messages.length > 0 ? this.messages[this.messages.length - 1] : null)
              this.messages = this.messages.concat(processed)
              this.lastId = newList[newList.length - 1].id
              this.$nextTick(() => {
                this.scrollToId = 'msg-bottom'
              })
            }
          }
        })
    },
    // 处理消息：添加时间分割
    processMessages(list, prevMsg) {
      let lastTime = prevMsg ? prevMsg.create_time : ''
      return list.map(msg => {
        let showTime = false
        let timeLabel = ''
        if (!lastTime || this.timeDiff(lastTime, msg.create_time) > 300) {
          showTime = true
          timeLabel = this.formatTime(msg.create_time)
        }
        lastTime = msg.create_time
        return { ...msg, showTime, timeLabel }
      })
    },
    // 两个时间差（秒）
    timeDiff(t1, t2) {
      return Math.abs((new Date(t2.replace(/-/g, '/')).getTime() - new Date(t1.replace(/-/g, '/')).getTime()) / 1000)
    },
    // 格式化时间显示
    formatTime(timeStr) {
      const d = new Date(timeStr.replace(/-/g, '/'))
      const now = new Date()
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
      const msgDay = new Date(d.getFullYear(), d.getMonth(), d.getDate())
      const diff = (today - msgDay) / 86400000
      const hm = `${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`
      if (diff === 0) return `今天 ${hm}`
      if (diff === 1) return `昨天 ${hm}`
      return `${d.getMonth() + 1}/${d.getDate()} ${hm}`
    },
    // 发送消息
    sendMessage() {
      const content = this.inputText.trim()
      if (!content) return

      this.inputText = ''
      http.post('/member/game/sendMessage', {
        game_id: this.gameId,
        content: content
      }).then(res => {
        if (res.code === 0) {
          // 立即拉取最新消息
          this.pollMessages()
        } else {
          uni.showToast({ title: res.msg || '发送失败', icon: 'none' })
        }
      }).catch(() => {
        uni.showToast({ title: '发送失败', icon: 'none' })
      })
    },
    // 加载历史消息（预留）
    loadHistory() {}
  }
}
</script>

<style lang="scss" scoped>
.chat-page {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #ededed;
}

.game-info {
  background: #fff;
  padding: 20rpx 30rpx;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1rpx solid #e8e8e8;
  .game-title { font-size: 28rpx; font-weight: bold; color: #333; }
  .game-meta { font-size: 24rpx; color: #999; }
}

.msg-list {
  flex: 1;
  padding: 20rpx 20rpx 0;
  overflow-y: auto;
}

.time-divider {
  text-align: center;
  padding: 20rpx 0;
  text { font-size: 22rpx; color: #999; background: #dadada; padding: 6rpx 20rpx; border-radius: 8rpx; }
}

.msg-bubble {
  display: flex;
  margin-bottom: 30rpx;
  align-items: flex-start;

  &.left { flex-direction: row; }
  &.right { flex-direction: row-reverse; }

  .avatar {
    width: 80rpx;
    height: 80rpx;
    border-radius: 8rpx;
    flex-shrink: 0;
  }

  .bubble-content {
    max-width: 60%;
    margin: 0 16rpx;
  }

  .bubble-name {
    font-size: 22rpx;
    color: #999;
    margin-bottom: 6rpx;
    display: block;
  }

  &.right .bubble-name { text-align: right; }

  .bubble-text {
    padding: 20rpx 24rpx;
    border-radius: 12rpx;
    font-size: 30rpx;
    line-height: 1.5;
    word-break: break-all;

    &.mine {
      background: #95ec69;
      color: #000;
    }
    &.other {
      background: #fff;
      color: #333;
    }
  }
}

.input-bar {
  display: flex;
  align-items: center;
  padding: 16rpx 20rpx;
  padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
  background: #f7f7f7;
  border-top: 1rpx solid #e0e0e0;

  .msg-input {
    flex: 1;
    height: 72rpx;
    background: #fff;
    border-radius: 8rpx;
    padding: 0 24rpx;
    font-size: 28rpx;
  }

  .send-btn {
    margin-left: 16rpx;
    padding: 0 30rpx;
    height: 72rpx;
    line-height: 72rpx;
    border-radius: 8rpx;
    font-size: 28rpx;
    background: #c0c0c0;
    color: #fff;

    &.active {
      background: var(--main-color, #5AAB6E);
    }
  }
}
</style>
