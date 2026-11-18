<?php

namespace app\common\service\chat;

use Throwable;
use think\facade\Db;
use app\common\model\User;
use app\common\model\ChatConversation;

/**
 * 后台聊天室：当天会话列表、会话消息、管理员风控/报表指令
 */
class AdminChatService
{
    protected ConversationService $conversationService;
    protected MessageService $messageService;
    protected ReportService $reportService;
    protected TimeLimitService $timeLimitService;
    protected RiskService $riskService;

    public function __construct()
    {
        $this->conversationService = new ConversationService();
        $this->messageService      = new MessageService();
        $this->reportService       = new ReportService();
        $this->timeLimitService    = new TimeLimitService();
        $this->riskService         = new RiskService();
    }

    /**
     * 当天全部会话
     * @param string $keyword 会员昵称/用户名/ID 模糊搜索
     */
    public function listToday(int $limit = 50, int $page = 1, string $keyword = ''): array
    {
        $query = ChatConversation::where('session_date', $this->conversationService->todayDate());

        if ($keyword !== '') {
            $userIds = $this->searchUserIds($keyword);
            if (!$userIds) {
                return ['list' => [], 'total' => 0];
            }
            $query->whereIn('user_id', $userIds);
        }

        $total = (clone $query)->count();
        $list  = $query->order('last_time', 'desc')->page($page, $limit)->select()->toArray();
        $users = $this->users(array_column($list, 'user_id'));

        return [
            'list'  => array_map(function (array $item) use ($users) {
                $item['user'] = $users[(int)$item['user_id']] ?? null;
                // 未风控时展示会话冻结的风控金额，已风控时展示管理员覆盖后的金额
                $item['risk_amount'] = (float)$item['risk_amount'];
                $item['is_risk']     = (int)$item['is_risk'];
                return $item;
            }, $list),
            'total' => $total,
        ];
    }

    /**
     * 会话详情与全部消息（含管理员操作消息）
     */
    public function messages(int $conversationId): array
    {
        $conversation = ChatConversation::find($conversationId);
        if (!$conversation) {
            throw new \RuntimeException('会话不存在');
        }

        $messages = $this->messageService->listByConversation($conversationId, 200);
        return [
            'conversation' => $this->formatConversation($conversation),
            'messages'     => array_map([$this->messageService, 'format'], $messages),
        ];
    }

    /**
     * 管理员发送指令：风控+金额 或 报表
     */
    public function send(int $conversationId, int $adminId, string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            throw new \RuntimeException('请输入内容');
        }
        if (mb_strlen($content) > 100) {
            throw new \RuntimeException('内容过长');
        }

        $conversation = ChatConversation::find($conversationId);
        if (!$conversation) {
            throw new \RuntimeException('会话不存在');
        }

        $amount   = $this->riskService->parseAmount($content);
        $isReport = $this->timeLimitService->isReportCommand($content);
        if ($amount === null && !$isReport) {
            throw new \RuntimeException('仅支持发送「' . $this->riskService->command() . '金额」或「' . $this->timeLimitService->command() . '」');
        }

        Db::startTrans();
        try {
            if ($isReport) {
                $reply = $this->reportService->todayReport($conversation, $this->conversationService->settings($conversation));
            } else {
                $this->riskService->lock($conversation, (float)$amount, $adminId);
                $reply = $this->riskService->replyText((float)$amount);
            }

            $adminMessage = $this->messageService->create($conversation, 'admin', $content, $adminId);
            $botMessage   = $this->messageService->create($conversation, 'bot', $reply);
            $this->conversationService->touch($conversation, $botMessage);
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return [
            'conversation' => $this->formatConversation($conversation->refresh()),
            'messages'     => [
                $this->messageService->format($adminMessage),
                $this->messageService->format($botMessage),
            ],
        ];
    }

    /**
     * 会员搜索，返回会员ID
     */
    protected function searchUserIds(string $keyword): array
    {
        return User::whereLike('nickname|username', '%' . $keyword . '%')
            ->whereOr('id', (int)$keyword)
            ->column('id');
    }

    /**
     * 批量取会员展示信息
     * @return array<int, array>
     */
    protected function users(array $userIds): array
    {
        if (!$userIds) return [];

        $list = User::whereIn('id', array_unique($userIds))
            ->field(['id', 'username', 'nickname', 'avatar'])
            ->select()
            ->toArray();

        $result = [];
        foreach ($list as $item) {
            $result[(int)$item['id']] = $item;
        }
        return $result;
    }

    /**
     * 会话输出结构
     */
    protected function formatConversation(ChatConversation $conversation): array
    {
        $data                = $conversation->toArray();
        $data['user']        = $this->users([(int)$conversation->user_id])[(int)$conversation->user_id] ?? null;
        $data['risk_amount'] = (float)$conversation->risk_amount;
        $data['is_risk']     = (int)$conversation->is_risk;
        return $data;
    }
}
