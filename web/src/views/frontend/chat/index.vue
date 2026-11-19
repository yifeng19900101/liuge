<template>
    <div class="chat-page-wrapper">
        <div class="chat-page">
            <!-- 顶部标题栏 -->
            <header class="chat-header">
                <div class="chat-header-avatar">
                    <Icon name="el-icon-Service" size="20" color="#ffffff" />
                </div>
                <div class="chat-header-title">
                    <div class="chat-header-name">{{ t('chat.Bot name') }}</div>
                    <div class="chat-header-status">{{ userTitle }}</div>
                </div>
                <div class="chat-header-side chat-header-more">
                    <el-dropdown trigger="click" @command="onCommand">
                        <span class="chat-header-more-trigger">
                            <Icon name="el-icon-MoreFilled" size="20" />
                        </span>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item command="clear">{{ t('chat.Clear history') }}</el-dropdown-item>
                                <el-dropdown-item command="logout" divided>{{ t('chat.Logout') }}</el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>
            </header>

            <!-- 聊天记录 -->
            <div ref="recordsRef" class="chat-records ba-scroll-style">
                <template v-for="(item, idx) in state.messages" :key="item.id">
                    <div v-if="showTime(idx)" class="chat-time">{{ formatTime(item.create_time) }}</div>
                    <div class="chat-item" :class="item.role">
                        <div class="chat-avatar">
                            <img v-if="item.role == 'user' && userAvatar" :src="userAvatar" alt="" draggable="false" />
                            <Icon v-else-if="item.role == 'user'" name="fa fa-user" size="18" color="#ffffff" />
                            <Icon v-else name="el-icon-Service" size="22" color="#ffffff" />
                        </div>
                        <div class="chat-bubble" v-text="item.content"></div>
                    </div>
                </template>

                <!-- 机器人正在输入 -->
                <div v-if="state.typing" class="chat-item bot">
                    <div class="chat-avatar">
                        <Icon name="el-icon-Service" size="22" color="#ffffff" />
                    </div>
                    <div class="chat-bubble chat-typing">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>

            <!-- 底部两行输入区 -->
            <div class="chat-editor">
                <textarea
                    ref="textareaRef"
                    v-model="state.content"
                    class="chat-textarea ba-scroll-style"
                    rows="2"
                    :placeholder="t('chat.Placeholder')"
                    @input="resizeTextarea"
                    @keydown="onKeydown"
                ></textarea>
                <el-button class="chat-send" color="#07c160" :disabled="!canSend" :loading="state.sending" @click="onSend">
                    {{ t('chat.Send') }}
                </el-button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, nextTick, onMounted, reactive, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { clearHistory, getHistory, sendMessage } from '/@/api/frontend/chat'
import { postLogout } from '/@/api/frontend/user/index'
import { useUserInfo } from '/@/stores/userInfo'
import { fullUrl } from '/@/utils/common'

interface MessageItem {
    id: number | string
    role: 'user' | 'bot'
    content: string
    create_time: number
}

const { t } = useI18n()
const router = useRouter()
const userInfo = useUserInfo()

// 输入框最小/最大高度：两行文本起，最多约五行
const TEXTAREA_MIN_HEIGHT = 58
const TEXTAREA_MAX_HEIGHT = 120
// 相邻消息间隔超过该秒数时显示时间
const TIME_GAP = 300

const recordsRef = useTemplateRef<HTMLDivElement>('recordsRef')
const textareaRef = useTemplateRef<HTMLTextAreaElement>('textareaRef')

const state = reactive({
    messages: [] as MessageItem[],
    content: '',
    sending: false,
    typing: false,
})

const userAvatar = computed(() => (userInfo.avatar ? fullUrl(userInfo.avatar) : ''))
const canSend = computed(() => state.content.trim() !== '' && !state.sending)

// 手机号中间四位脱敏
const maskMobile = (mobile: string) => {
    if (!mobile) return ''
    if (mobile.length < 8) return mobile
    return mobile.slice(0, 3) + '****' + mobile.slice(-4)
}

// 顶栏副标题：用户名称 + 脱敏手机号，均为空时退回「在线」
const userTitle = computed(() => {
    const name = userInfo.nickname || userInfo.username || ''
    const mobile = maskMobile(userInfo.mobile || '')
    const title = [name, mobile].filter(Boolean).join(' ')
    return title || t('chat.Online')
})

const scrollToBottom = () => {
    nextTick(() => {
        const el = recordsRef.value
        if (el) el.scrollTop = el.scrollHeight
    })
}

const resizeTextarea = () => {
    const el = textareaRef.value
    if (!el) return
    el.style.height = 'auto'
    const height = Math.min(Math.max(el.scrollHeight, TEXTAREA_MIN_HEIGHT), TEXTAREA_MAX_HEIGHT)
    el.style.height = height + 'px'
    el.style.overflowY = el.scrollHeight > TEXTAREA_MAX_HEIGHT ? 'auto' : 'hidden'
}

const pad = (num: number) => String(num).padStart(2, '0')

const formatTime = (timestamp: number) => {
    const date = new Date(timestamp * 1000)
    if (isNaN(date.getTime())) return ''
    const time = pad(date.getHours()) + ':' + pad(date.getMinutes())

    const now = new Date()
    const startOfDay = (value: Date) => new Date(value.getFullYear(), value.getMonth(), value.getDate()).getTime()
    const diffDays = Math.round((startOfDay(now) - startOfDay(date)) / 86400000)

    if (diffDays === 0) return time
    if (diffDays === 1) return t('chat.Yesterday') + ' ' + time
    return t('chat.Date format', { month: date.getMonth() + 1, day: date.getDate(), time: time })
}

const showTime = (idx: number) => {
    if (idx === 0) return true
    return state.messages[idx].create_time - state.messages[idx - 1].create_time > TIME_GAP
}

const loadHistory = () => {
    getHistory()
        .then((res) => {
            state.messages = res.data.messages
            scrollToBottom()
        })
        .catch(() => {
            // 加载失败时保持空会话，用户仍可继续发送
        })
}

const onSend = () => {
    const content = state.content.trim()
    if (!content || state.sending) return

    state.sending = true
    state.content = ''
    resizeTextarea()

    // 先本地追加，等接口返回后以服务端消息为准
    const tempId = 'temp-' + Date.now()
    state.messages.push({
        id: tempId,
        role: 'user',
        content: content,
        create_time: Math.floor(Date.now() / 1000),
    })
    state.typing = true
    scrollToBottom()

    const startedAt = Date.now()
    sendMessage({ content: content })
        .then(async (res) => {
            // 机器人回复至少展示 500ms 的输入状态
            const wait = 500 - (Date.now() - startedAt)
            if (wait > 0) await new Promise((resolve) => setTimeout(resolve, wait))

            const tempIndex = state.messages.findIndex((item) => item.id === tempId)
            if (tempIndex > -1) state.messages.splice(tempIndex, 1)
            state.messages.push(...res.data.messages)
        })
        .catch((err) => {
            const tempIndex = state.messages.findIndex((item) => item.id === tempId)
            if (tempIndex > -1) state.messages.splice(tempIndex, 1)
            state.content = content
            resizeTextarea()
            ElMessage.error(err?.msg || t('chat.Send failed'))
        })
        .finally(() => {
            state.typing = false
            state.sending = false
            scrollToBottom()
        })
}

const onKeydown = (event: KeyboardEvent) => {
    if (event.key !== 'Enter' || event.shiftKey) return
    // 中文输入法选词回车不触发发送
    if (event.isComposing || event.keyCode === 229) return
    event.preventDefault()
    onSend()
}

const onCommand = (command: string) => {
    if (command === 'logout') {
        onLogout()
        return
    }
    if (command !== 'clear') return
    ElMessageBox.confirm(t('chat.Clear confirm'), t('chat.Clear history'), {
        type: 'warning',
        confirmButtonText: t('Confirm'),
        cancelButtonText: t('Cancel'),
    })
        .then(() => {
            return clearHistory()
                .then((res) => {
                    state.messages = res.data.messages
                    scrollToBottom()
                })
                .catch((err) => {
                    ElMessage.error(err?.msg || t('chat.Clear failed'))
                })
        })
        .catch(() => {
            // 取消操作无需处理
        })
}

const onLogout = () => {
    ElMessageBox.confirm(t('chat.Logout confirm'), t('chat.Logout'), {
        type: 'warning',
        confirmButtonText: t('Confirm'),
        cancelButtonText: t('Cancel'),
    })
        .then(() => {
            // 无论接口是否成功，均清理本地登录态并回到商城首页
            postLogout()
                .catch(() => {})
                .finally(() => {
                    userInfo.$reset()
                    router.replace({ path: '/' })
                })
        })
        .catch(() => {
            // 取消操作无需处理
        })
}

onMounted(() => {
    if (!userInfo.isLogin()) {
        router.replace({ path: '/' })
        return
    }
    loadHistory()
    nextTick(() => resizeTextarea())
})
</script>

<style scoped lang="scss">
.chat-page-wrapper {
    display: flex;
    justify-content: center;
    width: 100%;
    height: 100vh;
    height: 100dvh;
    background: #e3e3e3;
    overflow: hidden;
}

.chat-page {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    background: #ededed;
    color: #111111;
    overflow: hidden;
}

.chat-header {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    height: calc(48px + env(safe-area-inset-top));
    padding: 0 6px;
    padding-top: env(safe-area-inset-top);
    background: #ededed;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    user-select: none;

    .chat-header-avatar {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        margin: 0 4px 0 8px;
        background: #07c160;
        border-radius: 6px;
    }

    .chat-header-side {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        color: #191919;
        cursor: pointer;
        border-radius: 4px;

        &:hover {
            background: rgba(0, 0, 0, 0.04);
        }
    }

    .chat-header-title {
        flex: 1;
        overflow: hidden;
        padding-left: 4px;
        text-align: center;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .chat-header-name {
        font-size: 16px;
        font-weight: 500;
        line-height: 20px;
    }

    .chat-header-status {
        overflow: hidden;
        font-size: 12px;
        line-height: 14px;
        color: #999999;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .chat-header-more-trigger {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        outline: none;
    }
}

.chat-records {
    flex: 1;
    padding: 12px 12px 4px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;

    .chat-time {
        margin: 10px 0;
        font-size: 12px;
        color: #999999;
        text-align: center;
    }
}

.chat-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 16px;

    &.user {
        flex-direction: row-reverse;
    }

    .chat-avatar {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        overflow: hidden;
        color: #ffffff;
        background: #07c160;
        border-radius: 4px;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    &.user .chat-avatar {
        background: #c8c9cc;
    }

    .chat-bubble {
        position: relative;
        max-width: 68%;
        margin: 0 10px;
        padding: 9px 12px;
        font-size: 15px;
        line-height: 1.5;
        word-break: break-word;
        white-space: pre-wrap;
        border-radius: 4px;
    }

    &.bot .chat-bubble {
        background: #ffffff;

        &::before {
            position: absolute;
            top: 13px;
            left: -6px;
            content: '';
            border: 6px solid transparent;
            border-left: 0;
            border-right-color: #ffffff;
        }
    }

    &.user .chat-bubble {
        background: #95ec69;

        &::before {
            position: absolute;
            top: 13px;
            right: -6px;
            content: '';
            border: 6px solid transparent;
            border-right: 0;
            border-left-color: #95ec69;
        }
    }
}

.chat-typing {
    display: flex;
    align-items: center;
    min-width: 44px;
    height: 36px;

    span {
        width: 6px;
        height: 6px;
        margin: 0 2px;
        background: #b2b2b2;
        border-radius: 50%;
        animation: typing-blink 1.2s infinite ease-in-out;

        &:nth-child(2) {
            animation-delay: 0.2s;
        }

        &:nth-child(3) {
            animation-delay: 0.4s;
        }
    }
}

@keyframes typing-blink {
    0%,
    60%,
    100% {
        opacity: 0.3;
        transform: translateY(0);
    }
    30% {
        opacity: 1;
        transform: translateY(-3px);
    }
}

.chat-editor {
    display: flex;
    flex-shrink: 0;
    align-items: flex-end;
    padding: 8px 10px;
    padding-bottom: calc(8px + env(safe-area-inset-bottom));
    background: #f7f7f7;
    border-top: 1px solid #e5e5e5;

    .chat-textarea {
        flex: 1;
        min-height: 58px;
        max-height: 120px;
        padding: 8px 10px;
        font-family: inherit;
        font-size: 16px;
        line-height: 22px;
        color: #111111;
        resize: none;
        background: #ffffff;
        border: none;
        border-radius: 4px;
        outline: none;
        overflow-y: hidden;

        &::placeholder {
            color: #b2b2b2;
        }
    }

    .chat-send {
        flex-shrink: 0;
        height: 36px;
        margin-bottom: 2px;
        margin-left: 8px;
    }
}

@media screen and (min-width: 768px) {
    .chat-page {
        max-width: 480px;
        box-shadow: 0 0 24px rgba(0, 0, 0, 0.12);
    }
}
</style>
