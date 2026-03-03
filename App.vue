<script>
import config from '@/config/index.js'

export default {
	globalData: {
		// 从统一配置读取，避免多处维护
		baseUrl: config.baseUrl,
		tenantId: config.tenantId,
		// appName 优先从缓存读取（后台配置），否则用本地默认值
		appName: uni.getStorageSync('appName') || config.appName,
		version: config.version,
		// 系统信息
		systemInfo: {},
		sysinfo: {},
		// 用户信息
		userInfo: {},
		userData: {},
		// token（兼容原生）
		token: '',
		userDatatoken: {},
		// 是否登录
		isLogin: false,
		// 是否是 iPhone X 系列
		isIPhoneX: false,
		// 是否是 iOS
		isIos: false,
		// 主题色配置
		themeColors: {
			'default': '#5AAB6E',
			'blue': '#1890ff',
			'orange': '#fa8c16',
			'purple': '#722ed1'
		},
		currentTheme: 'default'
	},
	
	onLaunch: function() {
		// 获取系统信息
		this.getSystemInfo()
		
		// 检查登录状态
		this.checkLogin()
		
		// 检查更新
		this.checkUpdate()
		
		// 检查拼场功能开关
		this.checkGameEnabled()
		
		// 获取应用名称配置
		this.fetchAppName()
		
		// 监听网络状态变化
		this.listenNetworkChange()
	},
	
	onShow: function() {
		// 每次回到前台重新检查拼场开关
		this.checkGameEnabled()
	},
	
	onHide: function() {
	},
	
	methods: {
		// 获取系统信息（使用新 API 替代废弃的 getSystemInfoSync）
		getSystemInfo() {
			// #ifdef MP-WEIXIN
			// 微信小程序使用新 API
			const deviceInfo = uni.getDeviceInfo()
			const windowInfo = uni.getWindowInfo()
			const appBaseInfo = uni.getAppBaseInfo()
			
			// 组合系统信息
			const systemInfo = {
				...deviceInfo,
				...windowInfo,
				...appBaseInfo,
				platform: deviceInfo.platform,
				screenHeight: windowInfo.screenHeight,
				screenWidth: windowInfo.screenWidth,
				statusBarHeight: windowInfo.statusBarHeight,
				safeArea: windowInfo.safeArea
			}
			this.globalData.systemInfo = systemInfo
			
			// 判断是否是 iOS
			if (deviceInfo.platform === 'ios') {
				this.globalData.isIos = true
			}
			
			// 判断是否是 iPhone X 系列
			if (windowInfo.safeArea) {
				const safeBottom = windowInfo.screenHeight - windowInfo.safeArea.bottom
				if (safeBottom === 34) {
					this.globalData.isIPhoneX = true
				}
			}
			
			// 保存状态栏高度
			uni.setStorageSync('statusBarHeight', windowInfo.statusBarHeight)
			// #endif
			
			// #ifndef MP-WEIXIN
			// 其他平台继续使用旧 API
			const systemInfo = uni.getSystemInfoSync()
			this.globalData.systemInfo = systemInfo
			
			if (systemInfo.platform === 'ios') {
				this.globalData.isIos = true
			}
			
			if (systemInfo.safeArea) {
				const safeBottom = systemInfo.screenHeight - systemInfo.safeArea.bottom
				if (safeBottom === 34) {
					this.globalData.isIPhoneX = true
				}
			}
			
			uni.setStorageSync('statusBarHeight', systemInfo.statusBarHeight)
			// #endif
		},
		
		// 检查登录状态
		checkLogin() {
			const token = uni.getStorageSync('token')
			const userDatatoken = uni.getStorageSync('userDatatoken')
			if (token || userDatatoken) {
				this.globalData.token = token || (userDatatoken && userDatatoken.access_token)
				this.globalData.isLogin = true
				if (userDatatoken) {
					this.globalData.userDatatoken = userDatatoken
				}
				// 获取用户信息
				const userInfo = uni.getStorageSync('userInfo')
				if (userInfo) {
					this.globalData.userInfo = userInfo
				}
			}
		},
		
		// 检查更新
		checkUpdate() {
			// #ifdef MP-WEIXIN
			const updateManager = uni.getUpdateManager()
			
			updateManager.onCheckForUpdate(function(res) {
			})
			
			updateManager.onUpdateReady(function() {
				uni.showModal({
					title: '更新提示',
					content: '检测到新版本，需要重启小程序完成更新',
					showCancel: false,
					success: function(res) {
						if (res.confirm) {
							updateManager.applyUpdate()
						}
					}
				})
			})
			
			updateManager.onUpdateFailed(function() {
				uni.showModal({
					title: '更新提示',
					content: '新版本下载失败，请删除小程序并重新进入',
					showCancel: false
				})
			})
			// #endif
		},
		
		// 检查拼场功能开关
		checkGameEnabled() {
			const baseUrl = config.baseUrl
			const tenantId = config.tenantId
			const token = this.globalData.token || uni.getStorageSync('token') || ''
			const header = { 'tenant-id': tenantId }
			if (token) header['Authorization'] = 'Bearer ' + token
			uni.request({
				url: baseUrl + '/system/config/get?key=game_enabled',
				method: 'GET',
				header: header,
				success: (res) => {
					const data = res.data
					if (data && data.code === 0) {
						const enabled = data.data.value !== '0'
						this.globalData.gameEnabled = enabled
					}
				}
			})
		},
		
		// 获取应用名称配置
		fetchAppName() {
			const baseUrl = config.baseUrl
			const tenantId = config.tenantId
			const header = { 'tenant-id': tenantId }
			uni.request({
				url: baseUrl + '/system/config/get?key=app_name',
				method: 'GET',
				header: header,
				success: (res) => {
					const data = res.data
					if (data && data.code === 0 && data.data && data.data.value) {
						this.globalData.appName = data.data.value
						// 缓存到本地，下次启动可以更快显示
						uni.setStorageSync('appName', data.data.value)
					}
				}
			})
		},
		
		// 设置主题色
		setThemeColor(templateKey) {
			const colors = this.globalData.themeColors
			const color = colors[templateKey] || colors['default']
			this.globalData.currentTheme = templateKey || 'default'
			// 缓存主题色
			uni.setStorageSync('themeColor', color)
			uni.setStorageSync('themeKey', templateKey || 'default')
			return color
		},
		
		// 获取当前主题色
		getThemeColor() {
			return uni.getStorageSync('themeColor') || this.globalData.themeColors['default']
		},
		
		// 监听网络状态变化
		listenNetworkChange() {
			// 初始检查网络状态
			uni.getNetworkType({
				success: (res) => {
					if (res.networkType === 'none') {
						uni.showToast({
							title: '网络连接已断开',
							icon: 'none',
							duration: 2000
						})
					}
				}
			})
			
			// 监听网络变化
			uni.onNetworkStatusChange((res) => {
				if (!res.isConnected) {
					uni.showToast({
						title: '网络连接已断开，请检查网络设置',
						icon: 'none',
						duration: 2000
					})
				} else {
					uni.showToast({
						title: '网络已恢复',
						icon: 'none',
						duration: 1500
					})
				}
			})
		}
	}
}
</script>

<style lang="scss">
/* 引入 uView 基础样式 */
@import 'uview-ui/index.scss';

/* 引入 iconfont 字体图标 */
@import '@/static/iconfont.css';

/* 全局样式 */
page {
	/* 主题色变量（与原生小程序保持一致） */
	--main-color: #5AAB6E;
	--main-color-shadow: #4b905c;
	--main-green: #6AB37C;
	--main-red: #F8525E;
	--main-blue: #5abdff;
	--main-gray: #a4a4a4;
	--main-orange: #ffbb01;
	
	background-color: #F8F8F8;
	font-size: 28rpx;
	line-height: 1.6;
}

/* 通用样式 */
.container {
	width: 100%;
	min-height: 100vh;
}

.page {
	width: 100%;
	min-height: 100vh;
	background-color: #F8F8F8;
}

/* 安全区域适配 */
.safe-area-inset-bottom {
	padding-bottom: constant(safe-area-inset-bottom);
	padding-bottom: env(safe-area-inset-bottom);
}

/* 通用按钮样式 */
.btn-primary {
	background-color: var(--main-color, #5AAB6E);
	color: #FFFFFF;
	border-radius: 8rpx;
}

.btn-default {
	background-color: #FFFFFF;
	color: #333333;
	border: 1rpx solid #E5E5E5;
	border-radius: 8rpx;
}

/* 通用文字颜色 */
.text-primary {
	color: var(--main-color, #5AAB6E);
}

.text-secondary {
	color: #999999;
}

.text-danger {
	color: #FF4D4F;
}

.text-warning {
	color: #FF9800;
}

/* 通用间距 */
.mt-10 { margin-top: 10rpx; }
.mt-20 { margin-top: 20rpx; }
.mt-30 { margin-top: 30rpx; }
.mb-10 { margin-bottom: 10rpx; }
.mb-20 { margin-bottom: 20rpx; }
.mb-30 { margin-bottom: 30rpx; }
.ml-10 { margin-left: 10rpx; }
.ml-20 { margin-left: 20rpx; }
.mr-10 { margin-right: 10rpx; }
.mr-20 { margin-right: 20rpx; }

.pt-10 { padding-top: 10rpx; }
.pt-20 { padding-top: 20rpx; }
.pt-30 { padding-top: 30rpx; }
.pb-10 { padding-bottom: 10rpx; }
.pb-20 { padding-bottom: 20rpx; }
.pb-30 { padding-bottom: 30rpx; }
.pl-10 { padding-left: 10rpx; }
.pl-20 { padding-left: 20rpx; }
.pr-10 { padding-right: 10rpx; }
.pr-20 { padding-right: 20rpx; }

/* Flex 布局 */
.flex {
	display: flex;
}

.flex-center {
	display: flex;
	align-items: center;
	justify-content: center;
}

.flex-between {
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.flex-around {
	display: flex;
	align-items: center;
	justify-content: space-around;
}

.flex-column {
	display: flex;
	flex-direction: column;
}

.flex-1 {
	flex: 1;
}

/* 文字对齐 */
.text-center {
	text-align: center;
}

.text-left {
	text-align: left;
}

.text-right {
	text-align: right;
}

/* 文字省略 */
.ellipsis {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.ellipsis-2 {
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
	text-overflow: ellipsis;
}

/* 从 app.wxss 迁移的全局样式 */
.container {
	box-sizing: border-box;
	font-size: 28rpx;
	color: #333;
}

button::after {
	display: none;
}

.color-primary { color: var(--main-color); }
.border-primary { border-color: var(--main-color); }

/* swiper 指示点 */
swiper .uni-swiper-dot { display: none; }

.color-attention { color: #ea4e4e; }
.bg-attention { background: #ea4e4e; color: #fff; }

.tag-primary {
	color: var(--main-color);
	background: #f4f7ff;
	border: 1rpx solid #d6e3ff;
	padding: 0 10rpx;
	height: 44rpx;
	line-height: 44rpx;
	font-size: 24rpx;
}

.bg-primary { background: var(--main-color, #5AAB6E) !important; color: #fff !important; }
.bg-warning { background: #ff0000 !important; color: #fff !important; }
.bg-primary1 { background: rgb(247, 127, 47) !important; color: #fff !important; }
.bg-primary2 { background: var(--main-color, #5AAB6E) !important; color: #fff !important; }

button[disabled]:not([type]) {
	background-color: #ddd !important;
	color: rgba(0, 0, 0, .3) !important;
}

.button-click { opacity: 0.6; }

.logo-top {
	top: 0; left: 0;
	padding: 0 20rpx;
	background-color: #fff;
	z-index: 1;
}

.logo-name { display: flex; align-items: center; }
.logo-name .logo { font-size: 30rpx; width: 50rpx; height: 50rpx; margin-right: 10rpx; }
.logo-name .name { font-size: 36rpx; color: #000; font-weight: 700; }

.nodata {
	text-align: center; color: #999; font-size: 24rpx;
	position: absolute; left: 50%; top: 50%;
	transform: translate(-50%, -50%);
}

/* 列表空状态（非绝对定位，用 padding 撑高度） */
.nodata-list {
	text-align: center;
	padding: 100rpx 0;
	color: #999;
	font-size: 28rpx;
}

.noteMore { text-align: center; color: #999; font-size: 24rpx; padding: 20rpx 0; }

/* 加载更多提示 */
.note-more {
	text-align: center;
	padding: 20rpx 0;
	color: #999;
	font-size: 24rpx;
}

.nologin {
	position: absolute; left: 50%; top: 50%;
	margin-top: 50rpx; margin-left: -100rpx;
}

.nologin button {
	width: 200rpx; height: 70rpx; line-height: 70rpx;
	font-size: 28rpx; background: #fff;
}

.pickerMult {
	width: 96%; min-height: 70rpx; border: 1rpx solid #eee;
	padding: 0 20rpx; box-sizing: border-box;
	position: relative; display: flex; flex-wrap: wrap;
}

.pickerMult .mult {
	display: flex; align-items: center; background: #f0f2f5;
	margin: 10rpx 20rpx 10rpx 0; border-radius: 8rpx;
	font-size: 28rpx; padding: 0 10rpx 0 20rpx;
}

.fix-iphonex-button { bottom: 68rpx !important; }

.flex-space-between { display: flex; justify-content: space-between; }
</style>
