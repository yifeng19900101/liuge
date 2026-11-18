<template>
    <div class="chat-room">
        <!-- 左侧：当天会话 -->
        <aside class="room-list">
            <div class="room-list-header">
                <div class="room-list-title">
                    {{ t('chat.room.Today conversations') }}
                    <span class="room-list-count">{{ state.total }}</span>
                </div>
                <el-input
                    v-model="state.keyword"
                    size="small"
                    clearable
                    :placeholder="t('chat.room.Search member')"
                    @keyup.enter="loadList()"
                    @clear="loadList()"
                >
                    <template #prefix>
                        <Icon name="el-icon-Search" size="14" />
                    </template>
                </el-input>
            </div>
            <div v-loading="state.listLoading" class="room-list-body ba-scroll-style">
                <div
                    v-for="item in state.list"
                    :key="item.id"
                    class="room-item"
                    :class="{ active: state.active?.id === item.id, risked: item.is_risk === 1 }"
                    @click="openConversation(item)"
                >
                    <div class="room-item-avatar">
                        <img v-if="item.user?.avatar" :src="fullUrl(item.user.avatar)" alt="" />
                        <Icon v-else name="fa fa-user" size="16" />
                    </div>
                    <div class="room-item-main">
                        <div class="room-item-top">
                            <span class="room-item-name">{{ memberName(item) }}</span>
                            <span class="room-item-time">{{ formatTime(item.last_time, 'hh:MM') }}</span>
                        </div>
                        <div class="room-item-bottom">
                            <el-tag v-if="item.is_risk === 1" type="danger" size="small" effect="dark">
                                {{ t('chat.room.Risked') }}
                            </el-tag>
                            <span class="room-item-msg">{{ item.last_message || t('chat.room.No message') }}</span>
                        </div>
                    </div>
                </div>
                <el-empty v-if="!state.listLoading && !state.list.length" :description="t('chat.room.No conversation')" :image-size="60" />
            </div>
        </aside>

        <!-- 右侧：聊天窗口 -->
        <section v-if="state.active" class="room-chat">
            <header class="room-chat-header">
                <div class="room-chat-title">
                    <span class="room-chat-name">{{ memberName(state.active) }}</span>
                    <el-tag v-if="state.active.is_risk === 1" type="danger" size="small" effect="dark">
                        {{ t('chat.room.Risked') }}
                    </el-tag>
                </div>
                <div class="room-chat-meta">
                    <span>{{ t('chat.room.Session date') }}：{{ state.active.session_date }}</span>
                    <span>{{ t('chat.room.Risk amount') }}：{{ state.active.risk_amount }}</span>
                    <span>{{ t('chat.room.Coefficient') }}：{{ state.active.coefficient }}</span>
                    <span>{{ t('chat.room.Multiple') }}：{{ state.active.multiple }}</span>
                    <el-button size="small" text @click="loadMessages(state.active.id)">
                        <Icon name="el-icon-Refresh" size="14" />
                        {{ t('chat.room.Refresh') }}
                    </el-button>
                </div>
            </header>

            <div ref="recordsRef" v-loading="state.messagesLoading" class="room-chat-records ba-scroll-style">
                <template v-for="item in state.messages" :key="item.id">
                    <div class="room-msg" :class="item.role">
                        <div v-if="item.role !== 'user'" class="room-msg-avatar">
                            <Icon v-if="item.role === 'admin'" name="fa fa-user-secret" size="16" color="#ffffff" />
                            <Icon v-else name="el-icon-Service" size="18" color="#ffffff" />
                        </div>
                        <div class="room-msg-body">
                            <div v-if="item.role === 'admin'" class="room-msg-label">
                                {{ t('chat.room.Administrator') }}<span v-if="item.admin_id">#{{ item.admin_id }}</span>
                            </div>
                            <div class="room-msg-bubble" v-text="item.content"></div>
                            <div class="room-msg-time">{{ formatTime(item.create_time, 'hh:MM:ss') }}</div>
                        </div>
                    </div>
                </template>
                <el-empty v-if="!state.messagesLoading && !state.messages.length" :description="t('chat.room.No message')" :image-size="60" />
            </div>

            <div class="room-chat-editor">
                <div class="room-chat-tips">
                    <span>{{ t('chat.room.Only commands') }}</span>
                    <el-button size="small" @click="fillCommand('risk')">{{ t('chat.room.Risk control') }}</el-button>
                    <el-button size="small" @click="fillCommand('report')">{{ t('chat.room.Send report') }}</el-button>
                </div>
                <div class="room-chat-input">
                    <el-input
                        v-model="state.content"
                        type="textarea"
                        :rows="2"
                        resize="none"
                        :placeholder="t('chat.room.Placeholder')"
                        @keydown="onKeydown"
                    />
                    <el-button type="primary" :loading="state.sending" :disabled="!state.content.trim()" @click="onSend">
                        {{ t('chat.room.Send') }}
                    </el-button>
                </div>
            </div>
        </section>

        <section v-else class="room-empty">
            <el-empty :description="t('chat.room.Select conversation')" />
        </section>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { nextTick, onMounted, reactive, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { getRoomList, getRoomMessages, sendRoomCommand } from '/@/api/backend/chat'
import { fullUrl, timeFormat } from '/@/utils/common'

defineOptions({
    name: 'chat/room',
})

const { t } = useI18n()

const recordsRef = useTemplateRef<HTMLDivElement>('recordsRef')

const state = reactive({
    list: [] as anyObj[],
    total: 0,
    keyword: '',
    listLoading: false,
    messages: [] as anyObj[],
    messagesLoading: false,
    active: null as anyObj | null,
    content: '',
    sending: false,
})

const memberName = (item: anyObj) => item.user?.nickname || item.user?.username || '#' + item.user_id

const formatTime = (value: number, fmt: string) => (value ? timeFormat(value, fmt) : '')

const scrollToBottom = () => {
    nextTick(() => {
        const el = recordsRef.value
        if (el) el.scrollTop = el.scrollHeight
    })
}

/** 加载当天会话列表 */
const loadList = (autoSelect = true) => {
    state.listLoading = true
    getRoomList({ page: 1, limit: 100, keyword: state.keyword })
        .then((res) => {
            state.list = res.data.list
            state.total = res.data.total
            if (autoSelect && !state.active && state.list.length) {
                openConversation(state.list[0])
            }
        })
        .finally(() => {
            state.listLoading = false
        })
}

/** 进入某个会话的聊天 */
const openConversation = (item: anyObj) => {
    state.active = item
    state.content = ''
    loadMessages(item.id)
}

/** 加载会话消息 */
const loadMessages = (conversationId: number) => {
    state.messagesLoading = true
    getRoomMessages(conversationId)
        .then((res) => {
            state.messages = res.data.messages
            state.active = { ...state.active, ...res.data.conversation }
            scrollToBottom()
        })
        .catch(() => {
            state.messages = []
        })
        .finally(() => {
            state.messagesLoading = false
        })
}

/** 快捷填入指令（指令内容为固定中文，不能使用界面翻译值） */
const fillCommand = (type: 'risk' | 'report') => {
    state.content = type === 'risk' ? t('chat.room.Risk command') : t('chat.room.Report command')
}

const onSend = () => {
    const content = state.content.trim()
    if (!content || state.sending || !state.active) return

    state.sending = true
    sendRoomCommand({ conversation_id: state.active.id, content: content })
        .then((res) => {
            state.content = ''
            state.messages.push(...res.data.messages)
            state.active = { ...state.active, ...res.data.conversation }
            loadList(false)
            scrollToBottom()
            ElMessage.success(t('chat.room.Command success'))
        })
        .catch((err) => {
            ElMessage.error(err?.msg || t('chat.room.Send failed'))
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

onMounted(() => {
    loadList()
})
</script>

<style scoped lang="scss">
.chat-room {
    display: flex;
    height: calc(100vh - 120px);
    min-height: 480px;
    margin: var(--ba-main-space);
    overflow: hidden;
    background: var(--ba-bg-color-overlay);
    border: 1px solid var(--ba-border-color);
    border-radius: var(--el-border-radius-base);

    .room-list {
        display: flex;
        flex-direction: column;
        width: 300px;
        flex-shrink: 0;
        border-right: 1px solid var(--ba-border-color);

        .room-list-header {
            padding: 12px;
            border-bottom: 1px solid var(--ba-border-color);

            .room-list-title {
                margin-bottom: 10px;
                font-size: 15px;
                font-weight: 600;
                color: var(--el-text-color-primary);

                .room-list-count {
                    margin-left: 6px;
                    font-size: 12px;
                    font-weight: normal;
                    color: var(--el-text-color-secondary);
                }
            }
        }

        .room-list-body {
            flex: 1;
            overflow-y: auto;
        }

        .room-item {
            display: flex;
            gap: 10px;
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid var(--ba-border-color);

            &:hover {
                background: var(--el-fill-color-light);
            }

            &.active {
                background: var(--el-color-primary-light-9);
            }

            &.risked .room-item-avatar {
                background: var(--el-color-danger);
            }

            .room-item-avatar {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                flex-shrink: 0;
                overflow: hidden;
                color: #ffffff;
                background: var(--el-color-primary);
                border-radius: 6px;

                img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
            }

            .room-item-main {
                flex: 1;
                min-width: 0;

                .room-item-top {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 8px;

                    .room-item-name {
                        overflow: hidden;
                        font-size: 14px;
                        font-weight: 600;
                        color: var(--el-text-color-primary);
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }

                    .room-item-time {
                        flex-shrink: 0;
                        font-size: 12px;
                        color: var(--el-text-color-secondary);
                    }
                }

                .room-item-bottom {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    margin-top: 4px;
                    font-size: 12px;
                    color: var(--el-text-color-secondary);

                    .room-item-msg {
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }
                }
            }
        }
    }

    .room-chat {
        display: flex;
        flex: 1;
        flex-direction: column;
        min-width: 0;

        .room-chat-header {
            padding: 10px 16px;
            border-bottom: 1px solid var(--ba-border-color);

            .room-chat-title {
                display: flex;
                align-items: center;
                gap: 8px;

                .room-chat-name {
                    font-size: 15px;
                    font-weight: 600;
                    color: var(--el-text-color-primary);
                }
            }

            .room-chat-meta {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 14px;
                margin-top: 6px;
                font-size: 12px;
                color: var(--el-text-color-secondary);
            }
        }

        .room-chat-records {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: var(--el-bg-color-page);

            .room-msg {
                display: flex;
                gap: 8px;
                margin-bottom: 14px;

                &.user {
                    flex-direction: row-reverse;
                }

                .room-msg-avatar {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 34px;
                    height: 34px;
                    flex-shrink: 0;
                    color: #ffffff;
                    background: var(--el-color-primary);
                    border-radius: 6px;
                }

                &.admin .room-msg-avatar {
                    background: var(--el-color-warning);
                }

                &.user .room-msg-avatar {
                    background: var(--el-color-success);
                }

                .room-msg-body {
                    max-width: 70%;

                    .room-msg-label {
                        margin-bottom: 4px;
                        font-size: 12px;
                        color: var(--el-color-warning);
                    }

                    .room-msg-bubble {
                        padding: 9px 12px;
                        font-size: 14px;
                        line-height: 1.6;
                        color: var(--el-text-color-primary);
                        white-space: pre-wrap;
                        word-break: break-word;
                        background: var(--el-bg-color-overlay);
                        border-radius: 6px;
                        box-shadow: 0 1px 2px rgb(0 0 0 / 6%);
                    }

                    .room-msg-time {
                        margin-top: 4px;
                        font-size: 11px;
                        color: var(--el-text-color-placeholder);
                    }
                }

                &.user .room-msg-body {
                    text-align: right;

                    .room-msg-bubble {
                        display: inline-block;
                        color: #ffffff;
                        text-align: left;
                        background: var(--el-color-success);
                    }

                    .room-msg-time {
                        text-align: right;
                    }
                }

                &.admin .room-msg-bubble {
                    background: var(--el-color-warning-light-9);
                }
            }
        }

        .room-chat-editor {
            padding: 10px 16px;
            border-top: 1px solid var(--ba-border-color);

            .room-chat-tips {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 8px;
                font-size: 12px;
                color: var(--el-text-color-secondary);
            }

            .room-chat-input {
                display: flex;
                gap: 10px;

                :deep(.el-textarea) {
                    flex: 1;
                }

                .el-button {
                    align-self: flex-end;
                }
            }
        }
    }

    .room-empty {
        display: flex;
        flex: 1;
        align-items: center;
        justify-content: center;
    }
}
</style>
