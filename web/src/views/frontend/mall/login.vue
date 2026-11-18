<template>
    <div class="login-wrapper">
        <div class="login-page">
            <header class="login-header">
                <div class="login-header-side" @click="goHome">
                    <Icon name="el-icon-ArrowLeft" size="18" color="#222222" />
                </div>
                <div class="login-header-title">会员登录</div>
                <div class="login-header-side"></div>
            </header>

            <div class="login-body">
                <div class="login-brand">
                    <div class="login-logo">
                        <Icon name="el-icon-ChatDotRound" size="26" color="#ffffff" />
                    </div>
                    <div class="login-welcome">欢迎回来</div>
                    <div class="login-sub">登录后即可进入机器人聊天</div>
                </div>

                <form class="login-form" @submit.prevent="onSubmit">
                    <div class="login-field">
                        <Icon name="el-icon-User" size="16" color="#b0b0b0" />
                        <input
                            v-model.trim="state.username"
                            class="login-input"
                            type="text"
                            placeholder="请输入账号"
                            autocomplete="username"
                            :disabled="state.loading"
                        />
                    </div>
                    <div class="login-field">
                        <Icon name="el-icon-Lock" size="16" color="#b0b0b0" />
                        <input
                            v-model="state.password"
                            class="login-input"
                            :type="state.showPassword ? 'text' : 'password'"
                            placeholder="请输入密码"
                            autocomplete="current-password"
                            :disabled="state.loading"
                        />
                        <div class="login-eye" @click="state.showPassword = !state.showPassword">
                            <Icon :name="state.showPassword ? 'el-icon-View' : 'el-icon-Hide'" size="16" color="#b0b0b0" />
                        </div>
                    </div>

                    <div class="login-options">
                        <el-checkbox v-model="state.keep" size="small">记住登录</el-checkbox>
                    </div>

                    <button class="login-submit" type="submit" :disabled="!canSubmit || state.loading">
                        {{ state.loading ? '登录中...' : '登 录' }}
                    </button>
                </form>

                <div class="login-tip">登录成功后自动进入机器人聊天</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { checkIn } from '/@/api/frontend/user/index'
import clickCaptcha from '/@/components/clickCaptcha'
import { useUserInfo } from '/@/stores/userInfo'
import { setTitleFromRoute } from '/@/utils/common'
import { uuid } from '/@/utils/random'

const router = useRouter()
const userInfo = useUserInfo()

const state = reactive({
    username: '',
    password: '',
    keep: false,
    showPassword: false,
    loading: false,
    // 是否开启登录验证码
    captchaSwitch: false,
    captchaId: uuid(),
})

const canSubmit = computed(() => state.username !== '' && state.password !== '')

const goHome = () => {
    router.replace({ path: '/' })
}

const doLogin = (captchaInfo = '') => {
    state.loading = true
    checkIn('post', {
        tab: 'login',
        username: state.username,
        password: state.password,
        keep: state.keep,
        captchaId: state.captchaId,
        captchaInfo,
    })
        .then((res) => {
            userInfo.dataFill(res.data.userInfo, false)
            router.replace({ name: 'chat' })
        })
        .catch(() => {
            // 点击验证码为一次性使用，失败后需重新获取
            state.captchaId = uuid()
        })
        .finally(() => {
            state.loading = false
        })
}

const onSubmit = () => {
    if (!canSubmit.value || state.loading) return
    if (state.captchaSwitch) {
        clickCaptcha(state.captchaId, (captchaInfo: string) => doLogin(captchaInfo))
    } else {
        doLogin()
    }
}

onMounted(() => {
    setTitleFromRoute()

    // 已登录会员无需再次登录
    if (userInfo.isLogin()) {
        router.replace({ name: 'chat' })
        return
    }

    checkIn('get').then((res) => {
        state.captchaSwitch = !!res.data.userLoginCaptchaSwitch
    })
})
</script>

<style scoped lang="scss">
.login-wrapper {
    display: flex;
    justify-content: center;
    width: 100%;
    height: 100vh;
    height: 100dvh;
    background: #e9e9e9;
    overflow: hidden;
}

.login-page {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    color: #222222;
    background: #ffffff;
    overflow: hidden;
}

.login-header {
    position: relative;
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: space-between;
    height: calc(48px + env(safe-area-inset-top));
    padding: 0 6px;
    padding-top: env(safe-area-inset-top);
    background: #ffffff;

    .login-header-side {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        cursor: pointer;
        border-radius: 4px;

        &:active {
            background: rgba(0, 0, 0, 0.04);
        }
    }

    .login-header-title {
        flex: 1;
        font-size: 16px;
        font-weight: 500;
        text-align: center;
    }
}

.login-body {
    flex: 1;
    padding: 20px 28px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.login-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 18px 0 30px;

    .login-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 62px;
        height: 62px;
        background: linear-gradient(135deg, #ff6a3d 0%, #ff2442 100%);
        border-radius: 20px;
        box-shadow: 0 8px 18px rgba(255, 36, 66, 0.28);
    }

    .login-welcome {
        margin-top: 16px;
        font-size: 21px;
        font-weight: 600;
        letter-spacing: 1px;
    }

    .login-sub {
        margin-top: 6px;
        font-size: 12px;
        color: #999999;
    }
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.login-field {
    display: flex;
    align-items: center;
    gap: 8px;
    height: 48px;
    padding: 0 14px;
    background: #f7f7f7;
    border: 1px solid transparent;
    border-radius: 12px;

    &:focus-within {
        background: #ffffff;
        border-color: #ff2442;
    }
}

.login-input {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 100%;
    padding: 0;
    font-family: inherit;
    font-size: 16px;
    color: #222222;
    background: transparent;
    border: none;
    outline: none;

    &::placeholder {
        font-size: 14px;
        color: #b0b0b0;
    }

    &:-webkit-autofill {
        -webkit-text-fill-color: #222222;
        box-shadow: 0 0 0 1000px #f7f7f7 inset;
    }
}

.login-eye {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    cursor: pointer;
}

.login-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2px 2px 4px;
}

.login-submit {
    height: 48px;
    margin-top: 6px;
    font-family: inherit;
    font-size: 16px;
    font-weight: 500;
    color: #ffffff;
    letter-spacing: 2px;
    cursor: pointer;
    background: linear-gradient(135deg, #ff6a3d 0%, #ff2442 100%);
    border: none;
    border-radius: 24px;
    transition: opacity 0.2s ease;

    &:active {
        opacity: 0.86;
    }

    &:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }
}

.login-tip {
    padding-top: 18px;
    font-size: 12px;
    color: #b8b8b8;
    text-align: center;
}

@media screen and (min-width: 768px) {
    .login-page {
        max-width: 480px;
        box-shadow: 0 0 24px rgba(0, 0, 0, 0.12);
    }
}
</style>
