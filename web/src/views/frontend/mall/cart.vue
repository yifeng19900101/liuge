<template>
    <MallLayout>
        <!-- 顶部标题 -->
        <div class="cart-header">
            <div class="cart-title">购物车</div>
            <div class="cart-manage" @click="onManage">
                {{ state.managing ? '完成' : '管理' }}
            </div>
        </div>

        <!-- 收货提示 -->
        <div class="cart-tip">
            <Icon name="el-icon-Van" size="14" color="#ff2442" />
            <span>满 99 元包邮，预计 48 小时内发货</span>
        </div>

        <!-- 购物车列表 -->
        <div class="cart-list" v-if="state.items.length">
            <div class="cart-item" v-for="item in state.items" :key="item.id">
                <div class="cart-check" :class="{ 'is-checked': item.checked }" @click="item.checked = !item.checked">
                    <Icon v-if="item.checked" name="el-icon-Select" size="12" color="#ffffff" />
                </div>
                <img class="cart-image" :src="item.image" :alt="item.name" />
                <div class="cart-info">
                    <div class="cart-name">{{ item.name }}</div>
                    <div class="cart-spec">{{ item.spec }}</div>
                    <div class="cart-bottom">
                        <div class="cart-price"><small>¥</small>{{ item.price }}</div>
                        <div class="cart-stepper" v-if="!state.managing">
                            <div class="stepper-btn" @click="onCount(item, -1)">
                                <Icon name="el-icon-Minus" size="12" color="#555555" />
                            </div>
                            <span class="stepper-num">{{ item.count }}</span>
                            <div class="stepper-btn" @click="onCount(item, 1)">
                                <Icon name="el-icon-Plus" size="12" color="#555555" />
                            </div>
                        </div>
                        <div class="cart-remove" v-else @click="onRemove(item.id)">
                            <Icon name="el-icon-Delete" size="16" color="#999999" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 空状态 -->
        <div class="cart-empty" v-else>
            <Icon name="el-icon-ShoppingCart" size="48" color="#dcdcdc" />
            <div class="cart-empty-text">购物车还是空的</div>
            <el-button color="#ff2442" round @click="goHome">去逛逛</el-button>
        </div>

        <div class="cart-recommend" v-if="state.items.length">
            <div class="cart-recommend-title">— 猜你喜欢 —</div>
            <div class="cart-recommend-grid">
                <div class="recommend-card" v-for="item in recommends" :key="item.name" @click="onRecommend(item.name)">
                    <img :src="item.image" :alt="item.name" />
                    <div class="recommend-name">{{ item.name }}</div>
                    <div class="recommend-price"><small>¥</small>{{ item.price }}</div>
                </div>
            </div>
        </div>

        <!-- 结算栏 -->
        <template #footer>
            <div class="cart-checkout" v-if="state.items.length">
                <div class="cart-check-all" @click="toggleAll">
                    <div class="cart-check" :class="{ 'is-checked': checkedAll }">
                        <Icon v-if="checkedAll" name="el-icon-Select" size="12" color="#ffffff" />
                    </div>
                    <span>全选</span>
                </div>
                <div class="cart-total">
                    <div class="cart-total-price">
                        合计：<span class="cart-total-num"><small>¥</small>{{ totalPrice }}</span>
                    </div>
                    <div class="cart-total-desc">已选 {{ checkedCount }} 件</div>
                </div>
                <el-button class="cart-submit" color="#ff2442" :disabled="!checkedCount" @click="onSubmit"> 结算({{ checkedCount }}) </el-button>
            </div>
        </template>
    </MallLayout>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import goods1 from '/@/assets/mall/goods-1.svg'
import goods2 from '/@/assets/mall/goods-2.svg'
import goods4 from '/@/assets/mall/goods-4.svg'
import goods5 from '/@/assets/mall/goods-5.svg'
import goods6 from '/@/assets/mall/goods-6.svg'
import { setTitleFromRoute } from '/@/utils/common'
import MallLayout from './components/MallLayout.vue'

interface CartItem {
    id: number
    name: string
    spec: string
    image: string
    price: number
    count: number
    checked: boolean
}

const router = useRouter()

const state = reactive({
    managing: false,
    items: [
        { id: 1, name: '云南古树滇红茶礼盒 500g', spec: '500g / 礼盒装', image: goods1, price: 129, count: 1, checked: true },
        { id: 2, name: '山茶花保湿护肤套装', spec: '水乳精华三件套', image: goods2, price: 199, count: 1, checked: true },
        { id: 3, name: '无线降噪蓝牙耳机', spec: '曜石黑 / 标准版', image: goods4, price: 299, count: 2, checked: false },
    ] as CartItem[],
})

const recommends = [
    { name: '手工黄油曲奇饼干礼盒', image: goods5, price: 68 },
    { name: '香薰蜡烛礼盒 3 件装', image: goods6, price: 89 },
]

const checkedAll = computed({
    get: () => state.items.length > 0 && state.items.every((item) => item.checked),
    set: (value: boolean) => {
        state.items.forEach((item) => (item.checked = value))
    },
})

const checkedCount = computed(() => state.items.filter((item) => item.checked).reduce((sum, item) => sum + item.count, 0))

const totalPrice = computed(() => {
    const total = state.items.filter((item) => item.checked).reduce((sum, item) => sum + item.price * item.count, 0)
    return total.toFixed(2)
})

const onCount = (item: CartItem, step: number) => {
    const next = item.count + step
    if (next < 1) {
        ElMessage.info('数量不能再少了')
        return
    }
    if (next > 99) {
        ElMessage.info('单件最多购买 99 件')
        return
    }
    item.count = next
}

const onRemove = (id: number) => {
    state.items = state.items.filter((item) => item.id !== id)
    ElMessage.success('已移出购物车')
}

const toggleAll = () => {
    checkedAll.value = !checkedAll.value
}

const onManage = () => {
    state.managing = !state.managing
}

const onSubmit = () => {
    if (!checkedCount.value) {
        ElMessage.info('请先选择商品')
        return
    }
    ElMessage.success('演示页面，暂不支持下单')
}

const onRecommend = (name: string) => {
    ElMessage.info(`${name}：商品详情页建设中`)
}

const goHome = () => {
    router.push({ name: '/' })
}

onMounted(() => {
    setTitleFromRoute()
})
</script>

<style scoped lang="scss">
.cart-header {
    position: sticky;
    top: 0;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 44px;
    background: #ffffff;
    border-bottom: 1px solid #f0f0f0;

    .cart-title {
        font-size: 16px;
        font-weight: 600;
    }

    .cart-manage {
        position: absolute;
        top: 0;
        right: 12px;
        display: flex;
        align-items: center;
        height: 44px;
        font-size: 13px;
        color: #666666;
        cursor: pointer;
    }
}

.cart-tip {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 9px 12px;
    margin: 10px 10px 0;
    font-size: 12px;
    color: #ff2442;
    background: #fff3f0;
    border-radius: 8px;
}

.cart-list {
    padding: 10px;
}

.cart-item {
    display: flex;
    align-items: center;
    padding: 10px;
    margin-bottom: 8px;
    background: #ffffff;
    border-radius: 10px;
}

.cart-check {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    cursor: pointer;
    border: 1px solid #d0d0d0;
    border-radius: 50%;

    &.is-checked {
        background: #ff2442;
        border-color: #ff2442;
    }
}

.cart-image {
    flex-shrink: 0;
    width: 76px;
    height: 76px;
    margin-left: 10px;
    object-fit: cover;
    border-radius: 8px;
}

.cart-info {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: space-between;
    align-self: stretch;
    min-width: 0;
    margin-left: 10px;
}

.cart-name {
    display: -webkit-box;
    overflow: hidden;
    font-size: 13px;
    line-height: 18px;
    color: #222222;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.cart-spec {
    margin-top: 4px;
    font-size: 11px;
    color: #999999;
}

.cart-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 6px;
}

.cart-price {
    font-size: 16px;
    font-weight: 600;
    color: #ff2442;

    small {
        margin-right: 1px;
        font-size: 11px;
    }
}

.cart-stepper {
    display: flex;
    align-items: center;
    overflow: hidden;
    border: 1px solid #e8e8e8;
    border-radius: 4px;

    .stepper-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 24px;
        cursor: pointer;
        background: #f7f7f7;

        &:active {
            background: #ededed;
        }
    }

    .stepper-num {
        min-width: 32px;
        font-size: 12px;
        text-align: center;
    }
}

.cart-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 24px;
    cursor: pointer;
}

.cart-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    padding: 70px 0;

    .cart-empty-text {
        font-size: 13px;
        color: #999999;
    }
}

.cart-recommend {
    padding: 4px 10px 10px;

    .cart-recommend-title {
        padding: 8px 0 12px;
        font-size: 12px;
        color: #b8b8b8;
        text-align: center;
    }
}

.cart-recommend-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.recommend-card {
    overflow: hidden;
    cursor: pointer;
    background: #ffffff;
    border-radius: 10px;
    padding-bottom: 8px;

    img {
        display: block;
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
    }
}

.recommend-name {
    display: -webkit-box;
    height: 34px;
    padding: 6px 8px 0;
    overflow: hidden;
    font-size: 12px;
    line-height: 17px;
    color: #333333;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.recommend-price {
    padding: 2px 8px 0;
    font-size: 15px;
    font-weight: 600;
    color: #ff2442;

    small {
        font-size: 10px;
    }
}

.cart-checkout {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    height: 54px;
    padding: 0 12px;
    background: #ffffff;
    border-top: 1px solid #f0f0f0;

    .cart-check-all {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #666666;
        cursor: pointer;
    }

    .cart-total {
        flex: 1;
        padding-right: 10px;
        text-align: right;
    }

    .cart-total-price {
        font-size: 12px;
        color: #333333;
    }

    .cart-total-num {
        font-size: 18px;
        font-weight: 600;
        color: #ff2442;

        small {
            font-size: 11px;
        }
    }

    .cart-total-desc {
        font-size: 11px;
        color: #999999;
    }

    .cart-submit {
        flex-shrink: 0;
        height: 36px;
        padding: 0 20px;
        font-size: 13px;
        color: #ffffff;
        border-radius: 18px;
    }
}
</style>
