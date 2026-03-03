// 全局配置文件

// 环境判断：开发环境 vs 生产环境
const isDev = process.env.NODE_ENV === 'development'

// API 基础地址
const devBaseUrl = 'http://127.0.0.1:8900/app-api'
const prodBaseUrl = 'https://your-domain.com/app-api'  // TODO: 替换为你的生产环境域名

// 图片/静态资源基础地址（不含 /app-api）
const devImageBase = 'http://127.0.0.1:8900'
const prodImageBase = 'https://your-domain.com'

const baseUrl = isDev ? devBaseUrl : prodBaseUrl
const imageBase = isDev ? devImageBase : prodImageBase

export default {
	// API 基础地址（根据环境自动切换）
	baseUrl,

	// 图片/静态资源基础地址
	imageBase,

	// 租户ID（如果是多租户系统，这里配置租户ID）
	tenantId: '88888888',

	// 应用名称（可在管理后台"系统配置"中修改 app_name）
	appName: '自助棋牌',

	// 版本号（可在管理后台"系统配置"中修改 app_version）
	version: '1.0.0',

	// 超时时间（毫秒）
	timeout: 30000,

	// 图片上传地址
	uploadUrl: baseUrl + '/infra/file/upload',

	// 默认头像
	defaultAvatar: '/static/img/default-avatar.png',

	// 客服电话（可在管理后台"系统配置"中修改 contact_phone）
	servicePhone: '',

	// 是否开启调试模式（开发环境自动开启）
	debug: isDev
}
