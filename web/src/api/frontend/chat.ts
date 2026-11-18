import createAxios from '/@/utils/axios'

export const chatUrl = '/api/chat/'

export function getHistory() {
    return createAxios({
        url: chatUrl + 'history',
        method: 'get',
    })
}

export function sendMessage(data: { content: string }) {
    return createAxios(
        {
            url: chatUrl + 'send',
            method: 'post',
            data: data,
        },
        {
            cancelDuplicateRequest: false,
            showCodeMessage: false,
        }
    )
}

export function clearHistory() {
    return createAxios(
        {
            url: chatUrl + 'clear',
            method: 'post',
        },
        {
            showCodeMessage: false,
        }
    )
}
