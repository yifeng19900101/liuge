import createAxios from '/@/utils/axios'

const roomUrl = '/admin/chat.Room/'

/** 当天会话列表 */
export function getRoomList(params: { page?: number; limit?: number; keyword?: string } = {}) {
    return createAxios({
        url: roomUrl + 'index',
        method: 'get',
        params: params,
    })
}

/** 会话消息 */
export function getRoomMessages(conversationId: number) {
    return createAxios({
        url: roomUrl + 'messages',
        method: 'get',
        params: { conversation_id: conversationId },
    })
}

/** 发送风控/报表指令 */
export function sendRoomCommand(data: { conversation_id: number; content: string }) {
    return createAxios(
        {
            url: roomUrl + 'send',
            method: 'post',
            data: data,
        },
        {
            showCodeMessage: false,
        }
    )
}
