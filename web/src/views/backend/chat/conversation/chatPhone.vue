<template>
    <el-dialog
        class="chat-phone-dialog"
        :model-value="modelValue"
        :show-close="false"
        :close-on-click-modal="false"
        :destroy-on-close="true"
        width="440px"
        top="6vh"
        append-to-body
        @update:model-value="onVisibleChange"
        @closed="onClosed"
    >
        <div class="chat-phone">
            <!-- 顶部标题栏 -->
            <header class="phone-header">
                <div class="phone-header-side" @click="onClose">
                    <Icon name="el-icon-Close" size="18" />
                </div>
                <div class="phone-header-title">
                    <div class="phone-header-name">{{ memberName }}</div>
                    <div class="phone-header-status">{{ headerStatus }}</div>
                </div>
                <div class="phone-header-side" :class="{ spinning: state.loading }" @click="reload">
                    <Icon name="el-icon-Refresh" size="18" />
                </div>
            </header>

            <div v-if="state.conversation" class="phone-meta">
                <span>{{ t('chat.conversation.Session date') }}：{{ state.conversation.session_date }}</span>
                <span>{{ t('chat.conversation.Coefficient') }}：{{ state.conversation.coefficient }}</span>
                <span>{{ t('chat.conversation.Multiple') }}：{{ state.conversation.multiple }}</span>
                <span>{{ t('chat.conversation.Risk amount') }}：{{ state.conversation.risk_amount }}</span>
                <el-tag v-if="state.conversation.is_risk === 1" type="danger" size="small" effect="dark">
                    {{ t('chat.conversation.Risked') }}
                </el-tag>
            </div>

            <!-- 聊天记录 -->
            <div ref="recordsRef" v-loading="state.loading" class="phone-records ba-scroll-style">
                <template v-for="(item, idx) in state.messages" :key="item.id">
                    <div v-if="showTime(idx)" class="phone-time">{{ formatTime(item.create_time) }}</div>
                    <div class="phone-item" :class="item.role">
                        <div class="phone-avatar">
                            <img v-if="item.role === 'user' && userAvatar" :src="userAvatar" alt="" />
                            <Icon v-else-if="item.role === 'user'" name="fa fa-user" size="16" color="#ffffff" />
                            <Icon v-else-if="item.role === 'admin'" name="fa fa-user-secret" size="16" color="#ffffff" />
                            <Icon v-else name="el-icon-Service" size="20" color="#ffffff" />
                        </div>
                        <div class="phone-msg">
                            <div v-if="item.role === 'admin'" class="phone-msg-label">
                                {{ t('chat.conversation.Administrator') }}<span v-if="item.admin_id">#{{ item.admin_id }}</span>
                            </div>
                            <div class="phone-bubble" v-text="item.content"></div>
                        </div>
                    </div>
                </template>
                <el-empty v-if="!state.loading && !state.messages.length" :description="t('chat.conversation.No message')" :image-size="60" />
            </div>

            <!-- 底部输入区 -->
            <div class="phone-editor">
                <div class="phone-tips">
                    <span class="phone-tips-text">{{ t('chat.conversation.Only commands') }}</span>
                    <el-button size="small" @click="fillCommand('risk')">{{ t('chat.conversation.Risk control') }}</el-button>
                    <el-button size="small" @click="fillCommand('report')">{{ t('chat.conversation.Send report') }}</el-button>
                </div>
                <div class="phone-input">
                    <textarea
                        v-model="state.content"
                        class="phone-textarea ba-scroll-style"
                        rows="2"
                        :placeholder="t('chat.conversation.Placeholder')"
                        @keydown="onKeydown"
                    ></textarea>
                    <el-button class="phone-send" color="#07c160" :disabled="!state.content.trim()" :loading="state.sending" @click="onSend">
                        {{ t('chat.conversation.Send') }}
                    </el-button>
                </div>
            </div>
        </div>
    </el-dialog>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, nextTick, reactive, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { getRoomMessages, sendRoomCommand } from '/@/api/backend/chat'
import { fullUrl, timeFormat } from '/@/utils/common'

defineOptions({
    name: 'chat/conversationChatPhone',
})

interface MessageItem {
    id: number
    role: 'user' | 'bot' | 'admin'
    admin_id: number
    content: string
    create_time: number
}

const props = defineProps<{
    modelValue: boolean
    row: TableRow | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'sent'): void
}>()

const { t } = useI18n()
const recordsRef = useTemplateRef<HTMLDivElement>('recordsRef')

// 相邻消息间隔超过该秒数时显示时间
const TIME_GAP = 300

const state = reactive({
    conversation: null as anyObj | null,
    messages: [] as MessageItem[],
    loading: false,
    content: '',
    sending: false,
})

const memberName = computed(() => {
    const user = state.conversation?.user || props.row?.user
    return user?.nickname || user?.username || '#' + (state.conversation?.user_id || props.row?.user_id || '')
})

const userAvatar = computed(() => {
    const avatar = state.conversation?.user?.avatar || props.row?.user?.avatar
    return avatar ? fullUrl(avatar) : ''
})

const headerStatus = computed(() => {
    const user = state.conversation?.user || props.row?.user
    return user?.username || ''
})

/** 与会员端一致的时间显示：当天只显示时分 */
const formatTime = (timestamp: number) => {
    if (!timestamp) return ''
    const date = new Date(timestamp * 1000)
    if (isNaN(date.getTime())) return ''

    const now = new Date()
    const startOfDay = (value: Date) => new Date(value.getFullYear(), value.getMonth(), value.getDate()).getTime()
    const diffDays = Math.round((startOfDay(now) - startOfDay(date)) / 86400000)
    if (diffDays === 0) return timeFormat(timestamp, 'hh:MM')
    return timeFormat(timestamp, 'yyyy-mm-dd hh:MM')
}

const showTime = (idx: number) => {
    if (idx === 0) return true
    return state.messages[idx].create_time - state.messages[idx - 1].create_time > TIME_GAP
}

const scrollToBottom = () => {
    nextTick(() => {
        const el = recordsRef.value
        if (el) el.scrollTop = el.scrollHeight
    })
}

/** 加载会话详情与全部消息（含管理员指令留痕） */
const loadMessages = (conversationId: number) => {
    state.loading = true
    getRoomMessages(conversationId)
        .then((res) => {
            state.conversation = res.data.conversation
            state.messages = res.data.messages
            scrollToBottom()
        })
        .catch((err) => {
            state.messages = []
            ElMessage.error(err?.msg || t('chat.conversation.Send failed'))
        })
        .finally(() => {
            state.loading = false
        })
}

const reload = () => {
    const id = state.conversation?.id || props.row?.id
    if (id) loadMessages(id)
}

/** 快捷填入指令（指令内容为固定中文，不能使用界面翻译值） */
const fillCommand = (type: 'risk' | 'report') => {
    state.content = type === 'risk' ? t('chat.conversation.Risk command') : t('chat.conversation.Report command')
}

const onSend = () => {
    const content = state.content.trim()
    const conversationId = state.conversation?.id || props.row?.id
    if (!content || state.sending || !conversationId) return

    state.sending = true
    sendRoomCommand({ conversation_id: conversationId, content: content })
        .then((res) => {
            state.content = ''
            state.messages.push(...res.data.messages)
            state.conversation = { ...state.conversation, ...res.data.conversation }
            scrollToBottom()
            ElMessage.success(t('chat.conversation.Command success'))
            emit('sent')
        })
        .catch((err) => {
            ElMessage.error(err?.msg || t('chat.conversation.Send failed'))
        })
        .finally(() => {
            state.sending = false
        })
}

const onKeydown = (event: Event | KeyboardEvent) => {
    if (!(event instanceof KeyboardEvent)) return
    if (event.key !== 'Enter' || event.shiftKey) return
    // 中文输入法选词回车不触发发送
    if (event.isComposing || event.keyCode === 229) return
    event.preventDefault()
    onSend()
}

const onClose = () => {
    emit('update:modelValue', false)
}

const onVisibleChange = (value: boolean) => {
    emit('update:modelValue', value)
}

const onClosed = () => {
    state.conversation = null
    state.messages = []
    state.content = ''
}

watch(
    () => props.modelValue,
    (value) => {
        if (!value) return
        state.content = ''
        state.messages = []
        state.conversation = null
        if (props.row?.id) loadMessages(props.row.id)
    }
)
</script>

<style scoped lang="scss">
.chat-phone {
    display: flex;
    flex-direction: column;
    height: 620px;
    max-height: 76vh;
    overflow: hidden;
    background: #ededed;
    border-radius: 8px;
}

.phone-header {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    height: 48px;
    padding: 0 6px;
    background: #ededed;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    user-select: none;

    .phone-header-side {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        color: #191919;
        cursor: pointer;
        border-radius: 4px;

        &:hover {
            background: rgba(0, 0, 0, 0.04);
        }

        &.spinning :deep(svg) {
            animation: phone-rotate 1s linear infinite;
        }
    }

    .phone-header-title {
        flex: 1;
        overflow: hidden;
        padding: 0 4px;
        text-align: center;
    }

    .phone-header-name {
        overflow: hidden;
        font-size: 15px;
        font-weight: 500;
        line-height: 20px;
        color: #111111;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .phone-header-status {
        overflow: hidden;
        font-size: 11px;
        line-height: 14px;
        color: #999999;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

.phone-meta {
    display: flex;
    flex-shrink: 0;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    padding: 6px 12px;
    font-size: 11px;
    color: #999999;
    background: #f4f4f4;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.phone-records {
    flex: 1;
    padding: 12px 12px 4px;
    overflow-y: auto;
    overscroll-behavior: contain;

    .phone-time {
        margin: 8px 0;
        font-size: 12px;
        color: #999999;
        text-align: center;
    }
}

.phone-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 14px;

    &.admin {
        flex-direction: row-reverse;
    }

    .phone-avatar {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
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

    &.user .phone-avatar {
        background: #c8c9cc;
    }

    &.admin .phone-avatar {
        background: #f0a020;
    }

    .phone-msg {
        max-width: 70%;
        margin: 0 8px;
    }

    .phone-msg-label {
        margin-bottom: 3px;
        font-size: 11px;
        color: #f0a020;
        text-align: right;
    }

    .phone-bubble {
        padding: 8px 11px;
        font-size: 14px;
        line-height: 1.5;
        color: #111111;
        word-break: break-word;
        white-space: pre-wrap;
        background: #ffffff;
        border-radius: 4px;
    }

    &.admin .phone-bubble {
        background: #95ec69;
    }
}

.phone-editor {
    flex-shrink: 0;
    padding: 8px 10px 10px;
    background: #f7f7f7;
    border-top: 1px solid #e5e5e5;

    .phone-tips {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;

        .phone-tips-text {
            flex: 1;
            overflow: hidden;
            font-size: 11px;
            color: #999999;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
    }

    .phone-input {
        display: flex;
        align-items: flex-end;
        gap: 8px;

        .phone-textarea {
            flex: 1;
            min-height: 58px;
            max-height: 110px;
            padding: 8px 10px;
            font-family: inherit;
            font-size: 14px;
            line-height: 22px;
            color: #111111;
            resize: none;
            background: #ffffff;
            border: none;
            border-radius: 4px;
            outline: none;
        }

        .phone-send {
            flex-shrink: 0;
            height: 36px;
        }
    }
}

@keyframes phone-rotate {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}
</style>
