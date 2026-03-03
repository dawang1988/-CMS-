/**
 * 列表页面通用 Mixin
 * 提供分页加载、下拉刷新等通用功能
 */

export default {
  data() {
    return {
      // 分页相关
      pageNo: 1,
      pageSize: 10,
      canLoadMore: true,
      isLoading: false,
      // 列表数据
      listData: []
    }
  },

  // 下拉刷新
  onPullDownRefresh() {
    this.refreshList()
    setTimeout(() => {
      uni.stopPullDownRefresh()
    }, 500)
  },

  // 触底加载更多
  onReachBottom() {
    if (this.canLoadMore && !this.isLoading) {
      this.loadMoreList()
    } else if (!this.canLoadMore) {
      uni.showToast({
        title: '没有更多了',
        icon: 'none'
      })
    }
  },

  methods: {
    /**
     * 刷新列表（重置分页）
     */
    refreshList() {
      this.pageNo = 1
      this.canLoadMore = true
      this.listData = []
      this.fetchListData('refresh')
    },

    /**
     * 加载更多
     */
    loadMoreList() {
      if (this.canLoadMore && !this.isLoading) {
        this.fetchListData()
      }
    },

    /**
     * 获取列表数据 - 需要在组件中实现
     * @param {string} type - 'refresh' 表示刷新
     */
    fetchListData(type) {
      console.warn('请在组件中实现 fetchListData 方法')
    },

    /**
     * 处理列表响应数据
     * @param {Object} res - 接口响应
     * @param {string} refreshType - 'refresh' 表示刷新
     * @param {Function} transform - 数据转换函数（可选）
     */
    handleListResponse(res, refreshType, transform) {
      if (res.code === 0) {
        const list = res.data.list || res.data || []
        const total = res.data.total || list.length
        
        // 应用数据转换
        const transformedList = transform ? list.map(transform) : list

        if (refreshType === 'refresh') {
          this.listData = transformedList
        } else {
          this.listData = this.listData.concat(transformedList)
        }

        // 更新分页状态
        if (list.length === 0 || this.listData.length >= total) {
          this.canLoadMore = false
        } else {
          this.pageNo++
        }
      } else {
        uni.showModal({
          content: res.msg || '获取数据失败',
          showCancel: false
        })
      }
    },

    /**
     * 检查登录状态
     * @returns {boolean}
     */
    checkLogin() {
      const token = uni.getStorageSync('token')
      if (!token) {
        uni.showModal({
          content: '请先登录',
          showCancel: false,
          success: () => {
            uni.navigateTo({
              url: '/pages/user/login'
            })
          }
        })
        return false
      }
      return true
    },

    /**
     * 复制文本到剪贴板
     * @param {string} text - 要复制的文本
     * @param {string} tip - 提示文字
     */
    copyText(text, tip = '已复制') {
      uni.setClipboardData({
        data: text,
        success: () => {
          uni.showToast({ title: tip })
        }
      })
    },

    /**
     * 拨打电话
     * @param {string} phone - 电话号码
     */
    callPhone(phone) {
      if (!phone) {
        uni.showToast({ title: '暂无联系电话', icon: 'none' })
        return
      }
      uni.makePhoneCall({ phoneNumber: phone })
    },

    /**
     * 打开地图导航
     * @param {Object} location - 位置信息 { latitude, longitude, name, address }
     */
    openMap(location) {
      if (!location.latitude || !location.longitude) {
        uni.showToast({ title: '暂无位置信息', icon: 'none' })
        return
      }
      uni.openLocation({
        latitude: Number(location.latitude),
        longitude: Number(location.longitude),
        name: location.name || '',
        address: location.address || '',
        scale: 18
      })
    }
  }
}
